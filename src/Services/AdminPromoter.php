<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserTeam;
use App\Repository\UserRepository;

final class AdminPromoter
{
    /**
     * @var TeamManager
     */
    private $teamManager;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(TeamManager $teamManager, UserRepository $userRepository)
    {
        $this->teamManager = $teamManager;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws \Exception
     */
    public function addTeamToAdmins(Team $team, ?User $excludedUser = null): void
    {
        $users = $this->userRepository->findAdmins();
        $users = array_merge($users, $this->userRepository->findAdmins(User::ROLE_SUPER_ADMIN));
        $users = array_unique($users);
        foreach ($users as $user) {
            if ($excludedUser === $user) {
                continue;
            }
            $this->teamManager->addTeamToUser($user, UserTeam::USER_ROLE_ADMIN, $team);
        }
    }
}
