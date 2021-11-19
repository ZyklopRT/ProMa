<?php

namespace jjansen\Service;

use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Project;
use jjansen\Repository\ProjectRepository;
use jjansen\Repository\TeamRepository;
use Symfony\Component\Security\Core\Security;

class ProjectService
{
    private TeamService $tService;
    private BeautifyService $bService;
    private EntityManagerInterface $entityManager;
    private ProjectRepository $projectRepository;
    private Security $security;
    private TeamRepository $teamRepository;

    public function __construct(
        TeamService $teamService,
        BeautifyService $beautifyService,
        ProjectRepository $projectRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        TeamRepository $teamRepository
    ) {
        $this->tService = $teamService;
        $this->bService = $beautifyService;
        $this->projectRepository = $projectRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->teamRepository = $teamRepository;
    }

    public function hasUserProjects(int $user_id): bool
    {
        // checks if user has Projects
        // returns true = user has projects, returns false = user has no projects
        if ($this->loadProjectsByOwner($user_id) == null) {
            return false;
        }
        return true;
    }

    public function removeProjectById(int $project_id): bool
    {

        $project = $this->projectRepository->find($project_id);
        $member = $this->security->getUser();
        if (!$project) {
            return false;
        }
        // check if user, who requested remove, is owner of project
        if ($project->getOwner() !== $member) {
            return false;
        }
        // finally remove project
        if ($this->deleteProject($project) != true) {
            return false;
        }
        return true;
    }

    public function loadProjectsByOwner(int $user_id): ?array
    {
        // loads all projects of user
        if ($user_id == 0) {
            return null;
        }
        // ## returns array =  with all projects , returns false = failed to find projects
        $projects = $this->projectRepository->findBy([
            'owner' => $user_id
        ]);
        foreach ($projects as $project) {
            if ($project != null) {
                $project = $this->loadExtraDataProject($project);
            }
        }
        return $projects;
    }

    public function loadProjectById(int $project_id): ?Project
    {
        // loads project with spefic project id
        if ($project_id == 0) {
            return null;
        }
        // ## returns array =  with all projects , returns false = failed to find projects
        $project = $this->projectRepository->find($project_id);
        if ($project != null) {
            $project = $this->loadExtraDataProject($project);
        }

        return $project;
    }

    public function loadProjectByName(string $project_name): ?Project
    {
        // loads project with spefic project id
        if ($project_name == null) {
            return null;
        }
        // ## returns array =  with all projects , returns false = failed to find projects
        $project = $this->projectRepository->findOneBy([
            'name' => $project_name
        ]);
        if ($project != null) {
            $project = $this->loadExtraDataProject($project);
        }

        return $project;
    }

    public function loadProjectsByTeam(int $team_id): ?array
    {
        // loads all projects of team
        if ($team_id == 0) {
            return null;
        }
        // ## returns array =  with all projects , returns false = failed to find projects
        $projects = $this->projectRepository->findBy([
            'team' => $team_id
        ]);
        foreach ($projects as $project) {
            if ($project != null) {
                $project = $this->loadExtraDataProject($project);
            }
        }
        return $projects;
    }

    public function isALlowedAddFeature(int $project_id, int $member_id): bool
    {
        // user if Member is an actually Member of the Project
        // check if project exists
        $project = $this->loadProjectById($project_id);
        if (!$project) {
            return false;
        }

        // check if team exists
        $team = $project->getTeam();
        if (!$team) {
            return false;
        }
        // check if user is member of this team
        $members_id = explode(",", $team->getMembers());
        foreach ($members_id as $id) {
            if ($id == $member_id) {
                return true;
            }
        }
        return false;
    }

    public function updateProjectById(int $project_id): bool
    {
        // Resets the Project Update time
        $project = $this->projectRepository->find($project_id);

        // get current date
        $project->renewUpdate();

        $this->entityManager->flush();
        return true;
    }

    public function createProject(string $name, string $quick_id, bool $visible, string $desc, int $team_id): bool
    {
        // ## returns true = project was created, returns false = creating failure
        // init & prepare
        $team = $this->teamRepository->find($team_id);
        $project = new Project();
        $member_id = $this->security->getUser()->getId();
        // get current date
        $date = strftime('%d.%m.%G um %H:%M');

        $project->setOwner($member_id);
        $project->setName($name);
        $project->setQuickid($quick_id);
        $project->setVisibility($visible);
        $project->setDescription($desc);
        $project->setTeam($team);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return true;
    }

    private function deleteProject(Project $project): bool
    {
        // deletes a project by project_id from the database
        // ## returns true = project deleted, returns false == failed

        if (!$project) {
            return false;
        }
        $this->entityManager->remove($project);
        $this->entityManager->flush();
        return true;
    }

    private function loadExtraDataProject(Project $project): Project
    {
        $project = $this->projectRepository->find($project->getId());

        return $project;
    }
}
