<?php

namespace jjansen\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Team;
use jjansen\Repository\InvitationRepository;
use jjansen\Security\InvitationVoter;
use jjansen\Service\TeamService;
use jjansen\Service\TemplateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends AbstractController
{
    private TemplateService $templateService;
    private InvitationRepository $invitationRepository;
    private EntityManager $entityManager;
    private TeamService $teamService;

    public function __construct(
        TemplateService $templateService,
        InvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager,
        TeamService $teamService
    ) {
        $this->templateService = $templateService;
        $this->invitationRepository = $invitationRepository;
        $this->entityManager = $entityManager;
        $this->teamService = $teamService;
    }

    /**
     * loads home page with given Request
     *
     *
     * @param Request $request
     * @return Response
     */
    public function loadAction(Request $request): Response
    {
        return $this->render('Notification/main.html.twig');
    }

    public function inviteAction(Request $request): Response
    {
        $invitation = $this->invitationRepository->find($this->$request->attributes->get('invite_id'));
        $this->denyAccessUnlessGranted(InvitationVoter::IS_INVITE_TARGET);

        $status = false;
        if ($request->attributes->get('status') === 'accept') {
            $status = true;
        }
        $this->teamService->handleInvite($invitation, $status);
    }
}