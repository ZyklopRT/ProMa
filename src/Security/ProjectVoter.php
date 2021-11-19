<?php

namespace jjansen\Security;

use jjansen\Entity\Project;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    const IS_TEAM_MEMBER = 'isTeamMember';
    const IS_TEAM_ADMIN = 'isTeamAdmin';

    protected function supports(string $attribute, $subject)
    {
        if (!in_array($attribute, [
            self::IS_TEAM_MEMBER,
            self::IS_TEAM_ADMIN
        ])) {
            return false;
        }
        return $subject instanceof Project;
    }

    /**
     * @param string $attribute
     * @param Project $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        return match ($attribute) {
            self::IS_TEAM_MEMBER => $subject->isTeamMember($token->getUser()),
            self::IS_TEAM_ADMIN => $subject->getOwner()->getId() === $token->getUser()->getId(),
            default => false,
        };
    }
}