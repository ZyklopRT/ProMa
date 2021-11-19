<?php

namespace jjansen\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use jjansen\Repository\VoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=VoteRepository::class)
 * @ORM\Table(name="vote")
 */
class Vote extends AbstractBase
{

    /**
     * @var Feature $feature
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\Feature", inversedBy="votes")
     */
    private Feature $feature;

    /**
     * @ORM\Column(type="float")
     */
    private float $time;

    /**
     * @var int $priority
     * @ORM\Column(type="integer")
     */
    private int $priority;
    /**
     * @var User $owner
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\User", inversedBy="votes")
     */
    private User $owner;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Feature
     */
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    /**
     * @param Feature $feature
     * @return Vote
     */
    public function setFeature(Feature $feature): Vote
    {
        $this->feature = $feature;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTime(): float
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     * @return Vote
     */
    public function setTime(float $time): self
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return Vote
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     * @return Vote
     */
    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
//        $owner->addVote($this);
        return $this;
    }
}
