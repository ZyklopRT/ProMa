<?php

namespace jjansen\Service;

use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Feature;
use jjansen\Entity\Vote;
use jjansen\Repository\FeatureRepository;
use jjansen\Repository\VoteRepository;
use Symfony\Component\Security\Core\Security;

class VoteService
{

    private VoteRepository $voteRepository;
    private ProjectService $pService;
    private BeautifyService $bService;
    private Security $security;
    private EntityManagerInterface $entityManager;
    private FeatureRepository $featureRepository;

    public function __construct(
        ProjectService $projectService,
        BeautifyService $beautifyService,
        Security $security,
        VoteRepository $voteRepository,
        EntityManagerInterface $entityManager,
        FeatureRepository $featureRepository
    ) {
        $this->voteRepository = $voteRepository;
        $this->pService = $projectService;
        $this->bService = $beautifyService;
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->featureRepository = $featureRepository;
    }

    public function addVoteToFeature(Feature $feature, float $time, int $priority): bool
    {
        // adds new votes to Voting of Feature in database
        // ## returns true = success, returns false = error occured

        $user = $this->security->getUser();

        // insert into database
        $vote = new Vote();
        $vote->setFeature($feature);
        $vote->setTime($time);
        $vote->setPriority($priority);
        $vote->setOwner($user);

        $this->entityManager->persist($vote);
        $this->entityManager->flush();
        return true;
    }

    public function updateVote(Vote $vote, float $time, int $priority): bool
    {
        $vote->setPriority($priority);
        $vote->setTime($time);
        $vote->setOwner($this->security->getUser());
        $vote->renewUpdate();

        $this->entityManager->flush();
        return true;
    }

    public function loadVotesByFeature(int $feature_id): array
    {
        // loads all information about the vote with same id
        // init & prepare stmt
        return $votes = $this->voteRepository->findBy([
            'feature' => $feature_id
        ]);
    }

}