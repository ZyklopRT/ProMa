<?php

namespace jjansen\Controller;

use jjansen\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use jjansen\Service\TemplateService;

class DefaultController extends AbstractController
{
    private TemplateService $templating;

    public function __construct(TemplateService $templating)
    {
        $this->templating = $templating;
    }

    public function indexAction(Request $request): Response
    {
        $content = $this->render('Mainsite/mainsite.html.twig', [
        ]);
        return new Response($content);
    }

}