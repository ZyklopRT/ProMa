<?php

namespace jjansen\Security;

use jjansen\Entity\Invitation;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InvitationVoter extends Voter
{
    const IS_INVITE_TARGET = 'isTeamMember';
    const IS_INVITE_OWNER = 'isTeamAdmin';

    protected function supports(string $attribute, $subject)
    {
        if (!in_array($attribute, [
            self::IS_INVITE_TARGET,
            self::IS_INVITE_OWNER
        ])) {
            return false;
        }
        return $subject instanceof Invitation;
    }

    /**
     * @param string $attribute
     * @param Invitation $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        return match ($attribute) {
            self::IS_INVITE_TARGET => $subject->getTarget()->getId() === $token->getUser()->getId(),
            self::IS_INVITE_OWNER => $subject->getOwner()->getId() === $token->getUser()->getId(),
            default => false,
        };
    }
}