<?php

namespace jjansen\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Project;
use jjansen\Form\Type\ProjectCreateType;
use jjansen\Repository\ProjectRepository;
use jjansen\Repository\TeamRepository;
use jjansen\Security\TeamVoter;
use jjansen\Service\SecurityService;
use jjansen\Service\TeamService;
use jjansen\Service\TemplateService;
use jjansen\Service\ProjectService;
use jjansen\Service\BeautifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectController extends AbstractController
{

    private TemplateService $templating;
    private TeamService $tService;
    private SecurityService $sService;
    private ProjectService $pService;
    private Security $security;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private TeamRepository $teamRepository;
    private ProjectRepository $projectRepository;

    /**
     * @param TemplateService $templating
     */
    public function __construct(
        TemplateService $templating,
        TeamService $tService,
        SecurityService $sService,
        ProjectService $pService,
        Security $security,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TeamRepository $teamRepository,
        ProjectRepository $projectRepository
    ) {
        $this->templating = $templating;
        $this->tService = $tService;
        $this->sService = $sService;
        $this->pService = $pService;
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->teamRepository = $teamRepository;
        $this->projectRepository = $projectRepository;
    }

    /**
     * Load a project page with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function loadProjectAction(Request $request): Response
    {
        $project = $this->projectRepository->findOneBy([
            'id' => $request->get('project_id')
        ]);
        if (!$project->isTeamMember($this->getUser())) {
            $this->addFlash('error', "Zugriff verweigert - Du bist kein Mitglied von diesem Projekt");
            return new RedirectResponse($this->generateUrl('dashboard_home'));
        }
        if (!$project) {
            $this->addFlash('error', "Project konnte nicht gefunden");
            return new RedirectResponse($this->generateUrl('dashboard_home'));
        }
        $content = $this->templating->render('Project/view.html.twig', [
            'request' => $request,
            'project' => $project
        ]);
        return new Response($content);
    }

    /**
     * Load a projects page with given Request
     *
     * @param Request $request
     * @return Response
     * @TODO: DOESNT WORK, NEEDS FIX
     */
    public function loadProjectsAction(Request $request): Response
    {
        $errors = [];
        $user = $this->security->getUser();
        // load all projects
        $projects_all = new ArrayCollection();
        $teams = $user->getTeams();
        foreach ($teams as $team) {
            $projects = $team->getProjects();
            $projects_all[] = $projects;
        }
        if (count($errors) > 0) {
            return new RedirectResponse($this->generateUrl('dashboard_home', ['errors' => $errors]));
        }
        $content = $this->templating->render('Project/view_all.html.twig', [
            'request' => $request,
            'projects' => $projects_all
        ]);
        return new Response($content);
    }

    /**
     * removes a project with given Request
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function removeAction(Request $request): Response
    {
        $project = $this->projectRepository->find($request->attributes->get('project_id'));
        $team = $project->getTeam();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $user = $this->getUser();
            if ($project->getOwner() == $user || $project->getTeam()->getAdmin() == $user) {
                $this->entityManager->remove($project);
                $this->entityManager->flush();
                $this->addFlash('success', 'Das Projekt wurde erfolgreich entfernt.');
                return new RedirectResponse($this->generateUrl('dashboard_team', ['team_id' => $team->getId()]));
            }
            $this->addFlash('error', 'Der Zugriff wurde verweigert.');
            return new RedirectResponse($this->generateUrl('dashboard_team', ['team_id' => $team->getId()]));
        }
        $content = $this->templating->render('Project/delete.html.twig', [
            'request' => $request,
            'team' => $project->getTeam(),
            'project' => $project
        ]);
        return new Response($content);

    }

    /**
     * creates a project with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request): Response
    {

        $project = new Project();

        $user = $this->security->getUser();
        // ONLY ALLOWED IF HE HAS TEAM
        if ($request->get('team_id', false)) {
            $team = $this->teamRepository->find($request->get('team_id'));
            $this->denyAccessUnlessGranted(TeamVoter::IS_TEAM_ADMIN, $team);
            $project->setTeam($team);
        }
        $project->setOwner($user);

        $form = $this->createForm(ProjectCreateType::class, $project, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $errors = $this->validator->validate($project);

            if (count($errors) > 0) {
                return $this->render('Project/create.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }
            // check if quickid is given and valid, if not -> create
            $project->setQuickid($this->sService->checkInputQuickID($project->getQuickid()));
            $this->entityManager->persist($project);
            $this->entityManager->flush();


            return new RedirectResponse($this->generateUrl('dashboard_projects'));
        }
        return $this->render('Project/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    // ######################### PRIVATE FUNCTIONS ########################

    /**
     * Validation: validates a project with given Request
     *
     * @param Request $request
     * @return array
     */
    private function initProject(Request $request): array
    {
        // ## whole function for create project system
        // create new error array
        $errors = [];
        // filter variables
        $name = $this->sService->filterInput($request->get('name'));
        $visible = $this->sService->filterInput($request->get('visible'));
        $desc = $this->sService->filterInput($request->get('desc'));
        $team = $this->sService->filterInput($request->get('team'));

        // check if inputs are valid
        if ($this->sService->checkInput($name) != true) {
            $errors['name'] = "Der Projekt-Name darf keine Sonderzeichen enthalten";
        }
        $allowed = [
            '0' => 0,
            '1' => 1
        ];
        if ($this->sService->checkSelect($visible, $allowed) != true) {
            $errors['visible'] = "Bitte wähle eine Sichtbarkeit aus";
        }
        // ###### TODO add checkSelect for teams ##################
        if ($this->sService->checkInputOptional($desc) != true) {
            $errors['desc'] = "Die Beschreibung darf keine Sonderzeichen enthalten";
        }
        //check if project with name or quickid already exists
        if ($this->pService->loadProjectByName($name) != null) {
            $errors['project'] = "Das Projekt existiert bereits";
        }
        return $errors;
    }


}