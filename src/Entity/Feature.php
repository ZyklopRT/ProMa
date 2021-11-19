<?php

namespace jjansen\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use jjansen\Repository\FeatureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=FeatureRepository::class)
 * @ORM\Table(name="feature");
 * @UniqueEntity("name", message="Dieses Feature existiert bereits")
 */
class Feature extends AbstractBase
{

    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\Project", inversedBy="features")
     */
    private $project;

    /**
     * @Assert\NotBlank(message="Bitte fülle das Feld aus")
     * @Assert\Length(min=5, minMessage="Der Name ist zu kurz (min. 5)")
     * @Assert\Regex(pattern="/[\w]/", message="Sonderzeichen sind nicht erlaubt")
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max=200, maxMessage="Die Beschreibung ist zu lang (max. 200)")
     * @Assert\Regex(pattern="/[\w\s!?.,;:#&äöüÄÖÜ]/", message="Nur Groß-, Kleinbuchstaben, Zahlen und einige Sonderzeichen erlaubt")
     * @ORM\Column(type="string", length=255, nullable="true")
     */
    private $description;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\User", inversedBy="features")
     */
    private $admin;

    /**
     * @var array|ArrayCollection|Vote[]
     * @ORM\OneToMany(targetEntity="jjansen\Entity\Vote", mappedBy="feature", cascade={"persist", "refresh", "remove" })
     */
    private $votes;

    /**
     * @var int
     * @Assert\NotBlank
     * @ORM\Column(type="integer", nullable="false")
     */
    private int $status;

    public function __construct()
    {
        parent::__construct();
        $this->votes = new ArrayCollection();
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return User
     */
    public function getAdmin(): User
    {
        return $this->admin;
    }

    /**
     * @param User $admin
     * @return Feature
     */
    public function setAdmin(User $admin): Feature
    {
        $this->admin = $admin;
        return $this;
    }

    /**
     * @return array|ArrayCollection|Vote[]
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @param Vote $vote
     * @return Feature
     */
    public function addVote(Vote $vote): Feature
    {
        if ($this->votes->isEmpty() || !$this->votes->contains($vote)) {
            $this->votes[] = $vote;
        }
        return $this;
    }

    /**
     * @param Vote $vote
     * @return Feature
     */
    public function removeVote(Vote $vote): self
    {
        $this->votes->removeElement($vote);
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return Feature
     */
    public function setStatus(int $status): Feature
    {
        $this->status = $status;
        return $this;
    }

    public function getAveragePriority(): ?int
    {
        $sum = 0;
        foreach ($this->votes as $vote) {
            $sum += $vote->getPriority();
        }
        if ($sum === 0) {
            return null;
        }
        return round($sum / count($this->votes), 0);
    }

    public function getAverageTime(): ?float
    {
        $sum = 0;
        foreach ($this->votes as $vote) {
            $sum += $vote->getTime();
        }
        if ($sum === 0) {
            return null;
        }
        return round($sum / count($this->votes), 2);
    }

    public function hasVoted(User $user)
    {
        foreach ($this->getVotes() as $vote) {
            if ($vote->getOwner() === $user) {
                return true;
            }
        }
        return false;
    }

}
