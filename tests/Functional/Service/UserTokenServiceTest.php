<?php

namespace App\Tests\Functional\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DateTimeService;
use App\Service\UserTokenService;
use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserTokenServiceTest
 */
class UserTokenServiceTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserTokenService
     */
    private $service;

    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(User::class);
        $this->service = new UserTokenService($this->repository, new DateTimeService(new NullLogger()));
    }

    public function testUpdate(): void
    {
        $email = 'yurii.didenko.it@gmail.com';
        $user = $this->repository->findOneBy(['email' => $email]);
        $currentToken = $user->getToken();
        $currentTokenValidTill = $user->getTokenValidTill();
        $this->service->update($user);
        $updatedToken = $user->getToken();
        $updatedTokenValidTill = $user->getTokenValidTill();
        $this->assertNotEmpty($updatedToken);
        $this->assertNotEquals($currentToken, $updatedToken);
        $this->assertNotEmpty($updatedTokenValidTill);
        $this->assertNotEquals($currentTokenValidTill->getTimestamp(), $updatedTokenValidTill->getTimestamp());
    }

    public function testProlong(): void
    {
        $email = 'yurii.didenko.it@gmail.com';
        $user = $this->repository->findOneBy(['email' => $email]);
        $currentToken = $user->getToken();
        $currentTokenValidTill = $user->getTokenValidTill();
        $this->service->prolong($user, DateTimeService::SECONDS_IN_HOUR * 5);
        $updatedToken = $user->getToken();
        $updatedTokenValidTill = $user->getTokenValidTill();
        $this->assertNotEmpty($updatedToken);
        $this->assertEquals($currentToken, $updatedToken);
        $this->assertNotEmpty($updatedTokenValidTill);
        $this->assertNotEquals($currentTokenValidTill->getTimestamp(), $updatedTokenValidTill->getTimestamp());
    }

    public function testGenerateToken()
    {
        $method = new ReflectionMethod(UserTokenService::class, 'generateToken');
        $method->setAccessible(true);
        $token1 = $method->invoke($this->service);
        $token2 = $method->invoke($this->service);
        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertNotEquals($token1, $token2);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}