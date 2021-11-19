<?php

namespace jjansen\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use jjansen\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="user")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=250)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=250)
     */
    private $last_name;

    /**
     * @ORM\Column(type="string", length=250, unique=true)
     * @Assert\Unique(message="Die Email ist bereits vergeben")
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;
    /**
     * @var array|ArrayCollection|Team[]
     *
     * @ORM\ManyToMany(targetEntity="jjansen\Entity\Team", inversedBy="members")
     * @JoinTable(name="user_team")
     */
    private $teams;

    /**
     * @var array|ArrayCollection|Vote[]
     * @ORM\OneToMany(targetEntity="jjansen\Entity\Vote", mappedBy="owner")
     */
    private $votes;
    /**
     * @var array|ArrayCollection|Feature[]
     * @ORM\OneToMany(targetEntity="jjansen\Entity\Feature", mappedBy="admin")
     */
    private $features;

    /**
     * @var array|ArrayCollection|Invitation[]
     * @ORM\OneToMany(targetEntity="jjansen\Entity\Invitation", mappedBy="owner")
     */
    private $invitations;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->features = new ArrayCollection();
        $this->invitations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return array|ArrayCollection|Team[]
     */
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * @param array|ArrayCollection|Team[] $teams
     * @return User
     */
    public function addTeam(Team $team): self
    {
        if (!$this->teams->contains($team) || $this->teams->isEmpty()) {
            $this->teams[] = $team;
        }
        return $this;
    }

    public function removeTeam(Team $team): self
    {
        $this->teams->removeElement($team);

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->uuid;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string)$this->uuid;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param mixed $last_name
     * @return User
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
     * @return User
     */
    public function addVote(Vote $vote): User
    {
        if ($this->votes->isEmpty() || !$this->votes->contains($vote)) {
            $this->votes[] = $vote;
        }
        return $this;
    }

    /**
     * @param Vote $vote
     * @return Vote
     */
    public function removeVote(Vote $vote): self
    {
        $this->votes->removeElement($vote);
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
     * @param Feature $features
     * @return User
     */
    public function addFeature(Feature $feature): User
    {
        if (empty($this->features) || !$this->features->contains($feature)) {
            $this->features[] = $feature;
        }
        return $this;
    }

    /**
     * @param Feature $feature
     * @return User
     */
    public function removeFeature(Feature $feature): self
    {
        $this->features->removeElement($feature);
        return $this;
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
     * @return User
     */
    public function addInvitations(Invitation $invitation): User
    {
        if (empty($this->invitations) || !$this->invitations->contains($invitation)) {
            $this->invitations[] = $invitation;
        }
        return $this;
    }

    /**
     * @param Invitation $invitation
     * @return User
     */
    public function removeInvitation(Invitation $invitation): User
    {
        $this->invitations->removeElement($invitation);
        return $this;
    }

}
