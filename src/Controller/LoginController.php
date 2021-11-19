<?php

namespace jjansen\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    private AuthenticationUtils $aUtils;

    public function __construct(AuthenticationUtils $aUtils)
    {
        $this->aUtils = $aUtils;
    }

    public function indexAction(Request $request): Response
    {
        $error = $this->aUtils->getLastAuthenticationError();

        $last_username = $this->aUtils->getLastUsername();

        $content = $this->render('Account/login.html.twig', [
            'request' => $request,
            'controller_name' => 'LoginController',
            'last_username' => $last_username,
            'error' => $error
        ]);
        return new Response($content);
    }
}
