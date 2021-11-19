<?php

namespace jjansen\Controller;

use jjansen\Repository\FeatureRepository;
use jjansen\Repository\ProjectRepository;
use jjansen\Repository\VoteRepository;
use jjansen\Security\ProjectVoter;
use jjansen\Service\SecurityService;
use jjansen\Service\TemplateService;
use jjansen\Service\VoteService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VoteController extends AbstractController
{
    private VoteService $voteService;
    private TemplateService $templating;
    private FeatureRepository $featureRepository;
    private ProjectRepository $projectRepository;
    private SecurityService $securityService;
    private VoteRepository $voteRepository;

    public function __construct(
        TemplateService $templating,
        VoteService $voteService,
        FeatureRepository $featureRepository,
        ProjectRepository $projectRepository,
        SecurityService $securityService,
        VoteRepository $voteRepository
    ) {

        $this->templating = $templating;
        $this->voteService = $voteService;
        $this->featureRepository = $featureRepository;
        $this->projectRepository = $projectRepository;
        $this->securityService = $securityService;
        $this->voteRepository = $voteRepository;
    }

    /**
     * creates a vote and adds it to a feature with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request): Response
    {
        $project = $this->projectRepository->find($request->attributes->get('project_id'));
        $feature = $this->featureRepository->find($request->attributes->get('feature_id'));

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $time = $request->get('time');
            $timeFilter = $request->get('timeFilter');
            $priority = $request->get('priority');
            if (empty($priority)) {
                $priority = 1;
            }
            $errors = $this->initVoteFeature($time, $priority, $timeFilter);
            $this->denyAccessUnlessGranted(ProjectVoter::IS_TEAM_MEMBER, $project);

            $voteTime = $time;
            if ($timeFilter === "2") {
                $voteTime = $time * 8;
            }
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return new RedirectResponse($this->generateUrl('dashboard_project',
                    ['project_id' => $project->getId()]));
            }
            $userVotes = $this->getUser()->getVotes();
            foreach ($userVotes as $userVote) {
                if ($userVote->getFeature()->getId() === $feature->getId()) {
                    $this->voteService->updateVote($userVote, $voteTime, $priority);
                    $this->addFlash('success', 'Die Bewertung wurde erfolgreich bearbeitet');
                    return new RedirectResponse($this->generateUrl('dashboard_project',
                        ['project_id' => $project->getId()]));
                }
            }
            $this->voteService->addVoteToFeature($feature, $voteTime, $priority);
            $this->addFlash('success', 'Die Bewertung wurde erfolgreich hinzugefügt');
            return new RedirectResponse($this->generateUrl('dashboard_project', ['project_id' => $project->getId()]));
        }
        $content = $this->templating->render('Vote/create.html.twig', [
            'request' => $request,
            'project' => $feature->getProject(),
            'feature' => $feature
        ]);
        return new Response($content);
    }


    // ## PRIVATE FUNCTIONS ##

    /**
     * Validation: validate a vote with given Request
     *
     * @param Request $request
     * @return array $errors
     */
    private function initVoteFeature(float $time, int $priority, string $timeFilter): array
    {

        $errors = [];
        // check inputs
        if ($this->securityService->checkInputNumber($time) != true) {
            $errors['time'] = "Die Eingabe ist nicht erlaubt";
        }
        if ($this->securityService->checkInputNumber($priority) != true) {
            $errors['priority'] = "Die Eingabe ist nicht erlaubt";
        }
        if ($this->securityService->checkInputNumber($timeFilter) != true) {
            $errors['time'] = "Die Eingabe ist nicht erlaubt";
        }

        return $errors;
    }
}