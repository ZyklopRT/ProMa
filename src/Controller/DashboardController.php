<?php

namespace jjansen\Controller;

use jjansen\Entity\Team;
use jjansen\Repository\ProjectRepository;
use jjansen\Repository\TeamRepository;
use jjansen\Service\SecurityService;
use jjansen\Service\ProjectService;
use jjansen\Service\TemplateService;
use jjansen\Service\FeatureService;
use jjansen\Service\TeamService;
use jjansen\Service\BeautifyService;
use jjansen\Service\VoteService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class DashboardController extends AbstractController
{
    private TemplateService $templating;
    private Security $security;
    private TeamRepository $teamRepository;
    private ProjectRepository $projectRepository;

    public function __construct(
        TemplateService $templating,
        Security $security,
        ProjectRepository $projectRepository,
        TeamRepository $teamRepository
    ) {
        $this->templating = $templating;
        $this->security = $security;
        $this->teamRepository = $teamRepository;
        $this->projectRepository = $projectRepository;
    }
    // ## NORMAL METHODS ##

    /**
     * Load a home page with given Request
     * @param Request $request
     * @return Response
     */
    public function loadHomeAction(Request $request): Response
    {
        $user = $this->security->getUser();
        // load all teams
        $teams = $user->getTeams();
        if (!$teams) {
            $this->addFlash('error', 'Es wurden keine Teams gefunden.');
        }
        $content = $this->templating->render('Dashboard/home.html.twig', [
            'request' => $request,
            'teams' => $teams
        ]);
        return new Response($content);
    }


}