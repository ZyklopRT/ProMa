<?php

namespace jjansen\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\JoinTable;
use jjansen\Repository\TeamRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use function PHPUnit\Framework\isEmpty;

/**
 * @ORM\Entity(repositoryClass=TeamRepository::class)
 * @ORM\Table(name="team")
 * @UniqueEntity("name", "quickid", message="Dieses Team existiert bereits")
 */
class Team extends AbstractBase
{
    /**
     * @var ?string
     * @Assert\Regex(pattern="/^#\d{5}[a-zA-Z]{5}/", message="Bitte halte das Format ein (#12345abcde)")
     * @ORM\Column(type="string", length=255)
     */
    private ?string $quickid = null;

    /**
     * @var string
     * @Assert\NotBlank(message="Bitte fülle das Feld aus")
     * @Assert\Length(min=5, minMessage="Der Name ist zu kurz (min. 5)")
     * @Assert\Regex(pattern="/[\w]/", message="Sonderzeichen sind nicht erlaubt")
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @var ?string
     * @Assert\Length(max=200, maxMessage="Die Beschreibung ist zu lang (max. 200)")
     * @Assert\Regex(pattern="/[\w\s!?.,;:#&äöüÄÖÜ]/", message="Nur Groß-, Kleinbuchstaben, Zahlen und einige Sonderzeichen erlaubt")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $description = null;

    /**
     * @var User
     * @Assert\NotBlank(message="Bitte fülle das Feld aus")
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\User")
     */
    private User $admin;

    /**
     * @var array|ArrayCollection|User[]
     *
     * @ORM\ManyToMany(targetEntity="jjansen\Entity\User", mappedBy="teams", cascade={"persist", "refresh"})
     */
    private $members;

    /**
     * @var bool
     * @Assert\PositiveOrZero(message="Bitte fülle das Feld aus")
     * @ORM\Column(type="boolean")
     */
    private bool $invitation;
    /**
     * @var array|ArrayCollection|Project[]
     *
     * @ORM\OneToMany(targetEntity="jjansen\Entity\Project", mappedBy="team", cascade={"persist", "refresh", "remove" })
     */
    private $projects;

    /**
     * @var array|ArrayCollection|Invitation[]
     * @ORM\OneToMany(targetEntity="jjansen\Entity\Invitation", mappedBy="team")
     */
    private $invitations;

    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
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

    /**
     * @return array|ArrayCollection|Project[]
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @param Project $project
     * @return Team
     */
    public function addProject(Project $project): Team
    {
        if ($this->projects->isEmpty() || !$this->projects->contains($project)) {
            $project->setTeam($this);
            $this->projects[] = $project;
            $this->renewUpdate();
        }
        return $this;
    }

    public function removeProject(Project $project)
    {
        $this->projects->removeElement($project);
        $this->renewUpdate();
        return $this;
    }

    /**
     * @return array|ArrayCollection|User[]
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param User member
     * @return Team
     */
    public function addMember(User $member): Team
    {
        if ($this->members->isEmpty() || !$this->members->contains($member)) {
            $member->addTeam($this);
            $this->members[] = $member;
            $this->renewUpdate();
        }
        return $this;
    }

    /**
     * @param User member
     * @return Team
     */
    public function removeMember(UserInterface $member): self
    {
        $this->members->removeElement($member);
        $this->renewUpdate();
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
     * @return Team
     */
    public function setAdmin(User $admin): self
    {
        $this->admin = $admin;
        return $this;
    }

    public function getInvitation(): ?bool
    {
        return $this->invitation;
    }

    public function setInvitation(bool $invitation): self
    {
        $this->invitation = $invitation;

        return $this;
    }

    public function isTeamMember(User $user): bool
    {
        $members = $this->getMembers();
        foreach ($members as $member) {
            if ($member->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array|ArrayCollection|Invitation[]
     */
    public function getInvitations()
    {
        return $this->invitations;
    }

    /**
     * @param Invitation
     * @return Team
     */
    public function addInvitations(Invitation $invitation): Team
    {
        if (empty($this->invitations) || !$this->invitations->contains($invitation)) {
            $this->invitations[] = $invitation;
        }
        return $this;
    }

    /**
     * @param Invitation $invitation
     * @return Team
     */
    public function removeInvitation(Invitation $invitation): Team
    {
        $this->invitations->removeElement($invitation);
        return $this;
    }
}
