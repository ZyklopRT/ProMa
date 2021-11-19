<?php

namespace jjansen\Controller;

use jjansen\Entity\Task;
use jjansen\Form\TaskType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class TaskController extends AbstractController
{

    public function new(Request $request): Response
    {
        $task = new Task();

        return $this->handleForm($task, $request, function () {
            $this->addFlash('success', 'Der Task wurde erstellt');
        }, function () {
            $this->addFlash('error', 'Es ist ein Fehler aufgetreten');
        });
    }

    public function edit(Request $request): Response
    {
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $request->get('id')]);
        return $this->handleForm($task, $request, function ($form, Task $task) {
            $this->addFlash("Die task " . $task->getName() . " wurde bearbeitet");
        });
    }

    /**
     * @param mixed $task
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    private function handleForm(
        mixed $task,
        Request $request,
        callable $successCallback = null,
        callable $errorCallback = null
    ): Response|\Symfony\Component\HttpFoundation\RedirectResponse {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get('custom')->getData();
            var_dump($data);
            exit;
            // $entityManager = $this->getDoctrine()->getManager();
            // $entityManager->persist($task);
            //$entityManager->flush();

            if ($successCallback) {
                $successCallback($form, $task);
            }

            return $this->redirect($this->generateUrl('dashboard_project'));
        }

        if ($errorCallback) {
            $errorCallback($form, $task);
        }

        return $this->render('task/form.html.twig', [
            'form' => $form->createView(),
            'entity' => $task

        ]);
    }


}