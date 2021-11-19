<?php

namespace jjansen\Entity;

use jjansen\Repository\InvitationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvitationRepository::class)
 * @ORM\Table(name="invitation")
 */
class Invitation extends AbstractBase
{

    /**
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\User", inversedBy="invitations")
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\User")
     */
    private $target = null;

    /**
     * @ORM\ManyToOne(targetEntity="jjansen\Entity\Team", inversedBy="invitations")
     */
    private $team;

    /**
     * @return mixed
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     * @return Invitation
     */
    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        $owner->addInvitations($this);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTarget(): ?User
    {
        return $this->target;
    }

    /**
     * @param mixed $target
     * @return Invitation
     */
    public function setTarget($target): self
    {
        $this->target = $target;
        $target->addInvitations($this);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTeam(): Team
    {
        return $this->team;
    }

    /**
     * @param mixed $team
     * @return Invitation
     */
    public function setTeam($team): self
    {
        $this->team = $team;
        return $this;
    }
}
