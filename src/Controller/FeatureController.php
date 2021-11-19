<?php

namespace jjansen\Controller;

use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Feature;
use jjansen\Form\Type\FeatureCreateType;
use jjansen\Repository\FeatureRepository;
use jjansen\Repository\ProjectRepository;
use jjansen\Repository\TeamRepository;
use jjansen\Security\ProjectVoter;
use jjansen\Service\VoteService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use jjansen\Service\FeatureService;
use jjansen\Service\ProjectService;
use jjansen\Service\SecurityService;
use jjansen\Service\TemplateService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FeatureController extends AbstractController
{
    private TemplateService $templating;
    private FeatureService $fService;
    private ProjectService $pService;
    private SecurityService $sService;
    private VoteService $vService;
    private Security $security;
    private FeatureRepository $featureRepository;
    private ProjectRepository $projectRepository;
    private TeamRepository $teamRepository;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;


    public function __construct(
        TemplateService $templating,
        FeatureService $fService,
        ProjectService $pService,
        SecurityService $sService,
        VoteService $vService,
        Security $security,
        FeatureRepository $featureRepository,
        ProjectRepository $projectRepository,
        TeamRepository $teamRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $this->templating = $templating;
        $this->fService = $fService;
        $this->pService = $pService;
        $this->sService = $sService;
        $this->vService = $vService;
        $this->security = $security;
        $this->featureRepository = $featureRepository;
        $this->projectRepository = $projectRepository;
        $this->teamRepository = $teamRepository;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    /**
     * creates a feature and adds it to project with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request): Response
    {
        $feature = new Feature();

        $user = $this->getUser();
        $project_id = $request->attributes->get('project_id');

        $project = $this->projectRepository->find($project_id);
        $team = $project->getTeam();

        if ($team->isTeamMember($user) != true) {
            $this->addFlash('error', 'Der Zugriff wurde verweigert');
            return new RedirectResponse($this->generateUrl('dashboard_home'));
        }

        $feature->setAdmin($user);
        $feature->setStatus(0);
        $feature->setProject($project);
        $form = $this->createForm(FeatureCreateType::class, $feature, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->validator->validate($project);
            if (count($errors) > 0) {
                return $this->render('Project/create.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }
            $feature->setAdmin($user);
            $project->addFeature($feature);
            $this->entityManager->persist($feature);
            $this->entityManager->flush();
            return new RedirectResponse($this->generateUrl('dashboard_project', ['project_id' => $project->getId()]));
        }
        return $this->render('Feature/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * removes a feature from a project with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function removeAction(Request $request): Response
    {
        $feature_id = $request->attributes->get('feature_id');
        $feature = $this->featureRepository->find($feature_id);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $project = $feature->getProject();
            $user = $this->getUser();

            if ($project->getOwner() == $user || $feature->getAdmin() == $user) {

                $project->removeFeature($feature);
                $project->renewUpdate();
                $this->entityManager->remove($feature);
                $this->entityManager->flush();
                $this->addFlash('success', 'Das Feature wurde erfolgreich entfernt');
                return new RedirectResponse($this->generateUrl('dashboard_project',
                    ['project_id' => $project->getId()]));
            }
            $this->addFlash('error', 'Der Zugriff wurde verweigert');
            return new RedirectResponse($this->generateUrl('dashboard_project', ['project_id' => $project->getId()]));
        }
        $content = $this->templating->render('Feature/delete.html.twig', [
            'request' => $request,
            'project' => $feature->getProject(),
            'feature' => $feature
        ]);
        return new Response($content);
    }

    /**
     * create an info page from vote with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function infoAction(Request $request): Response
    {
        $feature = $this->featureRepository->find($request->attributes->get('feature_id'));

        $content = $this->templating->render('Feature/info.html.twig', [
            'request' => $request,
            'feature' => $feature
        ]);
        return new Response($content);
    }

}