<?php

namespace jjansen\Security;

use jjansen\Entity\Team;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TeamVoter extends Voter
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
        return $subject instanceof Team;
    }

    /**
     * @param string $attribute
     * @param Team $subject
     * @param TokenInterface $token
     * @return bool|mixed
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): mixed
    {
        return match ($attribute) {
            self::IS_TEAM_MEMBER => $subject->isTeamMember($token->getUser()),
            self::IS_TEAM_ADMIN => $subject->getAdmin()->getId() === $token->getUser()->getId(),
            default => false,
        };
    }
}