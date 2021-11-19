<?php

namespace jjansen\Service;

use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Feature;
use jjansen\Repository\FeatureRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class FeatureService
{

    private ProjectService $pService;
    private FeatureRepository $featureRepository;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private BeautifyService $bService;

    public function __construct(
        ProjectService $projectService,
        FeatureRepository $featureRepository,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        BeautifyService $bService
    ) {
        $this->pService = $projectService;
        $this->featureRepository = $featureRepository;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->bService = $bService;
    }

    public function loadFeatures(int $project_id): ?array
    {
        // ## returns array = features, returns null = no features
        // loads all features of a project
        // get all projects
        $features = $this->featureRepository->findBy([
            'project' => $project_id
        ]);
        foreach ($features as $feature) {
            $feature->setAdmin_text($this->bService->decodeUserID($feature->getAdmin()));
        }
        return $features;
    }

    public function loadFeature(int $feature_id): ?Feature
    {
        // ## returns array = features, returns null = no features
        // get all projects
        $feature = $this->featureRepository->find($feature_id);
        if ($feature != null) {
            $feature->setAdmin_text($this->bService->decodeUserID($feature->getAdmin()));
        }
        return $feature;
    }

    public function addFeature(string $name, string $desc, int $project_id): bool
    {
        // adds a new feature to the project
        $feature = new Feature();
        $date = strftime('%d.%m.%G um %H:%M');

        $feature->setName($name);
        $feature->setDescription($desc);
        $feature->setAdmin($this->requestStack->getSession()->get('member_id'));
        $feature->setCreation($date);
        $feature->setProject($project_id);

        // change Update Time of Project
        if ($this->pService->updateProjectById($project_id) != true) {
            return false;
        }
        return true;
    }

    public function deleteFeature(Feature $feature): bool
    {

        $project = $feature->getProject();
        $project->removeFeature($feature);
        // delete
        $this->entityManager->remove($feature);
        $this->entityManager->flush();

        return true;
    }
}