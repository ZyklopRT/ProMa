<?php

namespace jjansen\Service;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use jjansen\Entity\Team;
use jjansen\Repository\FeatureRepository;
use jjansen\Repository\TeamRepository;
use jjansen\Repository\UserRepository;

class BeautifyService
{
    private UserRepository $userRepository;
    private TeamRepository $teamRepository;
    private EntityManager $entityManager;
    private FeatureRepository $featureRepository;

    public function __construct(
        UserRepository $userRepository,
        TeamRepository $teamRepository,
        EntityManagerInterface $entityManager,
        FeatureRepository $featureRepository
    ) {
        $this->userRepository = $userRepository;
        $this->teamRepository = $teamRepository;
        $this->featureRepository = $featureRepository;
        $this->entityManager = $entityManager;
    }

    public function decodeVisibleProject(bool $visible): ?string
    {
        // returns Öffentlich = if bool true, returns Privat = if bool false, returns null = error
        // translates bool into string
        if ($visible === true) {
            return "Öffentlich";
        } else {
            if ($visible === false) {
                return "Privat";
            }
        }
        return null;
    }

    public function decodeInviteTeam(bool $visible): ?string
    {
        // returns Offen = if bool true, returns Geschlossen = if bool false, returns null = error
        // translates bool into string
        if ($visible === true) {
            return "Offen";
        } else {
            if ($visible === false) {
                return "Geschlossen";
            }
        }
        return null;
    }

    public function decodeUserID(int $user_id): ?string
    {
        // translates int userID into String name
        if (!is_numeric($user_id)) {
            return null;
        }
        // get user
        $user = $this->userRepository->find($user_id);
        // catch if user not found
        if (!$user) {
            throw new \Exception("User not found for this id: " . $user_id);
        }
        return $user->getUuid();

    }

    public function decodeTeamID(int $team_id): ?string
    {
        // translates int userID into String name
        if (!is_numeric($team_id)) {
            return null;
        }
        // get user
        $team = $this->teamRepository->find($team_id);
        // catch if user not found
        if (!$team) {
            throw new \Exception("Team not found for this id: " . $team_id);
        }
        // get team
        return $team->getName();
    }

    public function decodeTeamMembers(string $team_members): array
    {
        // returns array = names of members, returns null = no member found
        // translates int team_members into real team_member names
        $members = [];
        $members_id = explode(",", $team_members);
        foreach ($members_id as $member_id) {
            // init & prepare
            $user = $this->userRepository->find($member_id);
            // catch if not found
            if (!$user) {
                throw new \Exception("User not found for this id: " . $member_id);
            }
            array_push($members, $user->getUuid());
        }
        return $members;
    }

    public function countMembersTeam(int $team_id): ?int
    {
        // returns int = count of members, returns null = no members found
        // get team
        $team = $this->teamRepository->find($team_id);
        // catch if team not found
        if (!$team) {
            throw new \Exception("Team not found for this id: " . $team_id);
        }

        $member_count = 0;
        foreach ($team->getMembers() as $member) {
            // get user
            $user = $this->userRepository->find($member->getId());
            // catch if user not found
            if ($user != null) {
                $member_count += 1;
            }
        }
        return $member_count;
    }

    public function countFeaturesByProject(int $project_id): ?int
    {

        $features = $this->featureRepository->findBy(
            ['project' => $project_id]
        );
        return count($features);
    }

    public function decodeUpdatedProject(string $project_updated): string
    {
        // translates Project Update Time into a quick-date name (today,last year etc.)


        $today = new DateTime("today"); // This object represents current date/time with time set to midnight
        $match_date = DateTime::createFromFormat("d.m.Y \u\m H:i", $project_updated);
        $match_date->setTime(0, 0, 0); // set time part to midnight, in order to prevent partial comparison
        $diff = $today->diff($match_date);
        $diffDays = (integer)$diff->format("%R%a"); // Extract days count in interval

        // return past time decoded as string
        // days
        if ($diffDays == 0) {
            return "Heute";
        } elseif ($diffDays == -1) {
            return "Gestern";
        } elseif ($diffDays < -2 && $diffDays > -7) {
            return "vor paar Tagen";
        } elseif ($diffDays <= -7 && $diffDays > -14) {
            return "vor einer Woche";
        }
        // more weeks
        if (abs($diffDays / 7) >= 2 && abs($diffDays / 7) < 4) {
            return "vor " . round(abs($diffDays / 7), 0) . " Wochen";
        }
        // months
        if (abs($diffDays / 30) >= 1 && abs($diffDays / 30) < 13) {
            return "vor " . round(abs($diffDays / 30), 0) . " Monaten";
        }
        return "In a Galaxy far away..";
    }

    public function calcMedian(string $values): float
    {
        // calculates Median out of string with Format: "1,2,3,4,5,6"

        $numbers = explode(",", $values);

        // first: sort numbers by order small to large
        sort($numbers);
        // get middle number if numbers count is odd
        if (count($numbers) % 2 != 0) {
            // median is equal to middle
            return ($numbers[count($numbers) / 2]);
        } else {
            $number1 = (integer)$numbers[count($numbers) / 2 - 1];
            $number2 = (integer)$numbers[count($numbers) / 2];
            return ($number1 + $number2) / 2;
        }
    }

    public function calcAverage(string $values): float
    {
        // calculates Median out of string with Format: "1,2,3,4,5,6"

        $numbers = explode(",", $values);

        // first: sort numbers by order small to large
        sort($numbers);
        $length = count($numbers);
        $result = 0;
        // add all numbers together
        foreach ($numbers as $number) {
            $result += (integer)$number;
        }
        // divide
        return $result / $length;
    }

}