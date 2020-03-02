<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * TokenAuthenticator constructor.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param string $email
     *
     * @return User|null
     */
    public function loadUserByUsername(string $email): ?User
    {
        return $this
            ->userRepository
            ->findOneBy(['email' => $email]);
    }

    /**
     * @param string $token
     *
     * @return User|null
     */
    public function loadUserByToken(string $token): ?User
    {
        return $this
            ->userRepository
            ->findOneBy(['token' => $token]);
    }

    /**
     * @param UserInterface $user
     *
     * @return User
     */
    public function refreshUser(UserInterface $user): User
    {
        return $user;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}