<?php

namespace jjansen\Controller;

use Cassandra\Type\UserType;
use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Team;
use jjansen\Entity\User;
use jjansen\Form\Type\MemberCreateType;
use jjansen\Form\Type\TeamJoinType;
use jjansen\Form\Type\TeamType;
use jjansen\Repository\TeamRepository;
use jjansen\Repository\UserRepository;
use jjansen\Security\TeamVoter;
use jjansen\Service\SecurityService;
use jjansen\Service\TemplateService;
use jjansen\Service\TeamService;
use jjansen\Service\BeautifyService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TeamController extends AbstractController
{
    private TemplateService $templating;
    private TeamService $tService;
    private SecurityService $sService;
    private Security $security;
    private EntityManagerInterface $entityManager;
    private TeamRepository $teamRepository;
    private UserRepository $userRepository;

    public function __construct(
        TemplateService $templating,
        TeamService $tService,
        SecurityService $sService,
        Security $security,
        EntityManagerInterface $entityManager,
        TeamRepository $teamRepository,
        ValidatorInterface $validator,
        UserRepository $userRepository
    ) {
        $this->templating = $templating;
        $this->tService = $tService;
        $this->sService = $sService;
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->teamRepository = $teamRepository;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
    }

    /**
     * creates a team with given Request
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function createAction(Request $request): Response
    {
        $team = new Team();
        $user = $this->getUser();

        $team->setAdmin($user);

        $form = $this->createForm(TeamType::class, $team, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($team);
            if (count($errors) > 0) {
                return $this->render('Team/create.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }
            $team->addMember($user);
            // check if quickid is given and valid, if not -> create
            $team->setQuickid($this->sService->checkInputQuickID($team->getQuickid()));
            $this->entityManager->persist($team);
            $this->entityManager->flush();
            return new RedirectResponse($this->generateUrl('dashboard_team', ['team_id' => $team->getId()]));
        }
        return $this->render('Team/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Loads a join page & handles a join with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function joinAction(Request $request): Response
    {
        $form = $this->createForm(TeamJoinType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $team_uuid = $form['name']->getData();
            $team_quickid = $form['quickid']->getData();

            $team_uuid = $this->sService->filterInput($team_uuid);

            $qb = $this->entityManager->createQueryBuilder('t')
                ->select('t')
                ->from(Team::class, 't')
                ->where('t.name = :name')
                ->setParameter('name', $team_uuid)
                ->andWhere('t.invitation = 0')
                ->orderBy('t.name', 'ASC');

            if (!empty($team_quickid)) {
                $qb->orWhere('t.quickid = :quickid')
                    ->setParameter('quickid', $team_quickid);
            }
            $team = $qb->getQuery()->getResult();

            if (empty($team)) {
                $this->addFlash('error', 'Es wurde kein öffentliches Team gefunden.');
                return new RedirectResponse($this->generateUrl('team_join'));
            }
            // send join request to team
            if ($this->tService->sendInvitation($team[0]) != true) {
                $this->addFlash('error', 'Es existiert bereits eine Beitritts-Anfrage.');
                return new RedirectResponse($this->generateUrl('team_join'));
            }
            $this->addFlash('success', 'Beitritts-Anfrage erfolgreich an das Team versendet.');
            return new RedirectResponse($this->generateUrl('dashboard_teams'));
        }
        return $this->render('Team/join.html.twig', [
            'form' => $form->createView()
        ]);

    }

    /**
     * Load a Teams page with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function loadTeamsAction(Request $request): Response
    {
        $errors = [];
        $user = $this->getUser();
        $filter = $request->attributes->get('filter');

        $teams = $user->getTeams();
        if ($filter == 'self') {
            $teams = $this->teamRepository->findBy([
                'admin' => $user
            ]);
        }


        if (count($errors) > 0) {
            return new RedirectResponse($this->generateUrl('dashboard_home', ['errors' => $errors]));
        }
        $content = $this->templating->render('Team/view_all.html.twig', [
            'request' => $request,
            'teams' => $teams
        ]);
        return new Response($content);
    }

    /**
     * Load a team page with given Request
     *
     * @param Request $request
     * @return Response|null
     */
    public function loadTeamAction(Request $request): ?Response
    {

        $errors = [];
        $member = $this->security->getUser();
        $team_id = $request->attributes->get('team_id');
        // load all team infos
        if (($team = $this->teamRepository->find($team_id)) == null) {
            $errors['status'] = "Es wurde kein Team gefunden";
        }
        $this->denyAccessUnlessGranted(TeamVoter::IS_TEAM_MEMBER, $team);
        if (count($errors) > 0) {
            return new RedirectResponse($this->generateUrl('dashboard_home', ['errors' => $errors]));
        }
        $content = $this->templating->render('Team/view.html.twig', [
            'request' => $request,
            'team' => $team,
            'errors' => $errors
        ]);
        return new Response($content);
    }

    /**
     * removes a Team with given Request
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function removeAction(Request $request): Response
    {
        $team = $this->teamRepository->find($request->attributes->get('team_id'));
        $teams = $this->getUser()->getTeams();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->denyAccessUnlessGranted(TeamVoter::IS_TEAM_ADMIN, $team);

            $this->entityManager->remove($team);
            $this->entityManager->flush();
            $this->addFlash('success', 'Das Team wurde erfolgreich entfernt.');
            return new RedirectResponse($this->generateUrl('dashboard_teams'));
        }
        $content = $this->templating->render('Team/delete.html.twig', [
            'request' => $request,
            'teams' => $teams,
            'team' => $team
        ]);
        return new Response($content);
    }

    /**
     * sends an Team invite Request to an user with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function inviteAction(Request $request): Response
    {

        $user = new User();
        $form = $this->createForm(MemberCreateType::class, $user, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $errors = $this->validator->validate($user);

            if (count($errors) > 0) {
                return $this->render('Team/invite.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }
            $team_id = $request->attributes->get('team_id');
            $team = $this->teamRepository->find($team_id);
            $user_uuid = $form['uuid']->getData();
            $user_name = $form['name']->getData();
            $user_last = $form['lastName']->getData();

            $qb = $this->entityManager->createQueryBuilder('u')
                ->select('u')
                ->from(User::class, 'u')
                ->where('u.uuid = :uuid')
                ->setParameter('uuid', $user_uuid);
            if (!empty($user_name)) {
                $qb->orWhere('u.name = :name')
                    ->setParameter('name', $user_name);
            }
            if (!empty($user_last)) {
                $qb->orWhere('u.last_name = :last')
                    ->setParameter('last', $user_last);
            }
            $user = $qb->getQuery()->getResult();
            if ($team->getMembers()->contains($user)) {
                $this->addFlash('error', 'Nutzer ist bereits Mitglied vom Team.');
                return new RedirectResponse($this->generateUrl('team_invite', ['team_id' => $team->getId()]));
            }
            $this->denyAccessUnlessGranted(TeamVoter::IS_TEAM_ADMIN, $team);

            if (!$user) {
                $this->addFlash('error', 'Der Nutzer konnte nicht gefunden werden.');
                return new RedirectResponse($this->generateUrl('team_invite', ['team_id' => $team->getId()]));
            }
            if ($this->tService->sendInvitation($team, $user[0]) != true) {
                $this->addFlash('error', 'Es wurde bereits eine Einladung verschickt.');
                return new RedirectResponse($this->generateUrl('team_invite', ['team_id' => $team->getId()]));
            }
            $this->addFlash('success', 'Die Einladung wurde an ' . $user[0]->getUuid() . " verschickt.");
            return new RedirectResponse($this->generateUrl('dashboard_team', ['team_id' => $team->getId()]));
        }
        return $this->render('Team/invite.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Validation: validates a team with given Request
     *
     * @param Request $request
     * @return array
     */
    private function initTeam(Request $request): array
    {
        // ## whole function for create team system
        // create new error array
        $errors = [];

        // filter variables
        $name = $this->sService->filterInput($request->get('name'));
        $visible = $this->sService->filterInput($request->get('visible'));
        $admin = $this->sService->filterInput($request->get('admin'));
        $desc = $this->sService->filterInput($request->get('desc'));

        // check if inputs are valid
        if ($this->sService->checkInput($name) != true) {
            $errors['name'] = "Der Team-Name darf keine Sonderzeichen enthalten";
        }
        // check if selected value is in range of allowed selected values (protection)
        $allowed = [
            '0' => 0,
            '1' => 1
        ];
        if ($this->sService->checkSelect($visible, $allowed) != true) {
            $errors['visible'] = "Bitte wähle eine Sichtbarkeit aus";
        }
        // check if selected value is in range of allowed selected values (protection)
        $allowed = [
            '1' => 1
        ];
        if ($this->sService->checkSelect($admin, $allowed) != true) {
            $errors['admin'] = "Bitte wähle einen Admin aus";
        }
        if ($this->sService->checkInputOptional($desc) != true) {
            $errors['desc'] = "Die Beschreibung darf keine Sonderzeichen enthalten";
        }
        //check if team with name or quickid already exists
        if ($this->tService->loadTeamByName($name) != null) {
            $errors['team'] = "Das Projekt existiert bereits";
        }
        return $errors;
    }

    /**
     * Creates a team with given Request
     *
     * @param string $name
     * @param string $quick_id
     * @param bool $invite
     * @param string $desc
     * @return bool
     */
    private function createTeam(string $name, string $quick_id, bool $invite, string $desc): bool
    {
        // ## returns true = team was created, returns false = creating failure

        $user = $this->getUser();

        $team = new Team();

        $team->setName($name);
        $team->setQuickid($quick_id);
        $team->setInvitation($invite);
        $team->setDescription($desc);
        $team->setAdmin($user);
        $team->addMember($user);

        $this->entityManager->persist($team);
        $this->entityManager->flush();

        return true;
    }

}