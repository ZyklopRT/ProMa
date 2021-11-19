<?php

namespace jjansen\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use jjansen\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;
use jjansen\Service\BeautifyService;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 * @ORM\Table(name="project")
 * @ORM\HasLifecycleCallbacks()
 */
class Project extends AbstractBase
{

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\User")
     * @Assert\NotBlank()
     */
    private $owner;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $quickid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;


    /**
     * @var Team|null
     *
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\Team", inversedBy="projects")
     * @Assert\NotBlank()
     */
    private $team;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\PositiveOrZero()
     */
    private $visibility;

    /**
     * @var array|ArrayCollection|Feature[]
     *
     * @ORM\OneToMany(targetEntity="jjansen\Entity\Feature", mappedBy="project", cascade={"persist", "refresh", "remove" })
     */
    private $features;


    public function __construct()
    {
        parent::__construct();
        $this->features = new ArrayCollection();
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

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     * @return Project
     */
    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getQuickid(): ?string
    {
        return $this->quickid;
    }

    public function setQuickid(string $quickid): self
    {
        $this->quickid = $quickid;

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
     * @return Team|null
     */
    public function getTeam(): ?Team
    {
        return $this->team;
    }

    /**
     * @param Team|null $team
     * @return Project
     */
    public function setTeam(?Team $team): Project
    {
        $this->team = $team;
        return $this;
    }


    public function getVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return array|ArrayCollection|Feature[]
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @param array|ArrayCollection|Feature[] $features
     * @return Project
     */
    public function addFeature(Feature $feature): self
    {
        if ($this->features->isEmpty() || !$this->features->contains($feature)) {
            $feature->setProject($this);
            $this->features[] = $feature;
        }
        $this->renewUpdate();
        return $this;
    }

    public function removeFeature(Feature $feature): self
    {

        $this->features->removeElement($feature);
        $this->renewUpdate();
        return $this;
    }

    public function isTeamMember(User $user): bool
    {
        $members = $this->getTeam()->getMembers();
        foreach ($members as $member) {
            if ($member->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }
}
