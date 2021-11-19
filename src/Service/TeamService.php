<?php

namespace jjansen\Service;

use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Invitation;
use jjansen\Entity\Team;
use jjansen\Entity\User;
use jjansen\Repository\InvitationRepository;
use jjansen\Repository\TeamRepository;
use Symfony\Component\Security\Core\Security;

class TeamService
{
    private BeautifyService $bService;
    private EntityManagerInterface $entityManager;
    private TeamRepository $teamRepository;
    private Security $security;
    private InvitationRepository $invitationRepository;

    public function __construct(
        BeautifyService $beautifyService,
        EntityManagerInterface $entityManager,
        TeamRepository $teamRepository,
        Security $security,
        InvitationRepository $invitationRepository
    ) {
        $this->bService = $beautifyService;
        $this->entityManager = $entityManager;
        $this->teamRepository = $teamRepository;
        $this->security = $security;
        $this->invitationRepository = $invitationRepository;
    }

    public function loadTeamsByOwner(int $user_id): array
    { // returns array =  all teams from member , returns null = no team found
        $teams = $this->teamRepository->findBy([
            'admin' => $user_id
        ]);
        return $teams;
    }

    public function loadTeamByName(string $team_name): ?Team
    { // returns array =  all teams from member , returns null = no team found
        $team = $this->teamRepository->findOneBy([
            'name' => $team_name
        ]);
        return $team;
    }

    public function loadTeamById(int $team_id): Team
    { // returns array =  all teams from member , returns null = no team found
        return $team = $this->teamRepository->find($team_id);
    }

    public function removeTeamById(int $team_id): bool
    {

        $team = $this->teamRepository->find($team_id);
        $user = $this->security->getUser();
        if (!$team) {
            return false;
        }
        // check if user, who requested remove, is owner of project
        if ($team->getAdmin() !== $user) {
            return false;
        }
        // finally remove project
        if ($this->deleteProject($team) != true) {
            return false;
        }
        return true;
    }

    /**
     * creates an invitation request to User/Team
     *
     * @param Team $team
     * @param User $target if not set = Invitation to Team | else = Invitation From Team to User
     * @return bool
     */
    public function sendInvitation(Team $team, User $target = null): bool
    {
        $invitation = new Invitation();
        $invitation->setOwner($this->security->getUser());
        $invitation->setTarget($team->getAdmin());
        if (!empty($target)) {
            $invitation->setTarget($target);
        }
        $invitation->setTeam($team);
        $data = $this->invitationRepository->findBy([
                'owner' => $invitation->getOwner(),
                'team' => $invitation->getTeam(),
                'target' => $invitation->getTarget()
            ]
        );
        if (!empty($data)) {
            return false;
        }
        $this->entityManager->persist($invitation);
        $this->entityManager->flush();
        return true;
    }

    private function deleteProject(Team $team): bool
    {
        // deletes a project by project_id from the database
        // ## returns true = project deleted, returns false == failed

        $this->entityManager->remove($team);
        $this->entityManager->flush();
        return true;
    }

    /**
     * handles a team invitation and removes all with given Invitation
     *
     * @param Invitation $invitation
     * @param bool $status > true = accept, false = decline
     * @return bool
     */
    public function handleInvite(Invitation $invitation, bool $status): bool
    {
        // accept invitation
        if ($status === true) {
            $team = $invitation->getTeam();
            if (!$team) {
                return false;
            }
            $team->addMember($invitation->getTarget());
        }
        $invitations = $this->invitationRepository->findBy([
            'owner' => $invitation->getTarget(),
            'target' => $invitation->getOwner(),
            'team' => $invitation->getTarget()
        ]);
        foreach ($invitations as $invite) {
            $this->entityManager->remove($invite);
        }
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();
        return true;
    }
}