<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Class UserTokenService
 */
class UserTokenService
{

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var DateTimeService
     */
    private $dateTimeService;

    /**
     * UserTokenService constructor.
     *
     * @param UserRepository $userRepository
     * @param DateTimeService $dateTimeService
     */
    public function __construct(UserRepository $userRepository, DateTimeService $dateTimeService)
    {
        $this->userRepository = $userRepository;
        $this->dateTimeService = $dateTimeService;
    }

    /**
     * @param User $user
     * @param int $validTillInSeconds
     *
     * @throws \Exception
     */
    public function update(User $user, int $validTillInSeconds = DateTimeService::SECONDS_IN_HOUR): void
    {
        $this->userRepository->setToken(
            $user,
            $this->generateToken(),
            $this->dateTimeService->nowDateTime()->modify('+ ' . $validTillInSeconds . ' seconds')
        );
    }

    /**
     * @param User $user
     * @param int $validTillInSeconds
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function prolong(User $user, int $validTillInSeconds = DateTimeService::SECONDS_IN_HOUR): void
    {
        $this->userRepository->updateTokenValidTill(
            $user,
            $this->dateTimeService->nowDateTime()->modify('+ ' . $validTillInSeconds . ' seconds')
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function generateToken(): string
    {
        return md5($this->dateTimeService->now() . random_bytes(7) . random_int(1, 1000));
    }
}