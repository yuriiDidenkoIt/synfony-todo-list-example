<?php

namespace App\Tests\Functional\Repository;

use App\DataFixtures\UserFixture;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserRepositoryTest
 */
class UserRepositoryTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testSearchByEmail(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => UserFixture::VALID_EMAIL]);

        $this->assertSame(UserFixture::VALID_EMAIL, $user->getUsername());
    }

    public function testSearchByToken(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['token' => UserFixture::VALID_TOKEN]);

        $this->assertSame(UserFixture::VALID_TOKEN, $user->getToken());
    }

    public function testSetToken(): void
    {
        $email = 'yurii.didenko.it@gmail.com';
        $token = md5((new \DateTime())->getTimestamp() . random_bytes(7) . random_int(1, 1000));
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        $this->assertNotEquals($token, $user->getToken());

        $repo = $this->entityManager->getRepository(User::class);
        $repo->setToken($user, $token, (new \DateTime()));
        $this->assertEquals($token, $user->getToken());
    }

    public function testUpdateTokenValidTill(): void
    {
        $email = 'yurii.didenko.it@gmail.com';
        $tokenValidTill = (new \DateTime())->modify('+1 hour');
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        $this->assertNotEquals($tokenValidTill, $user->getTokenValidTill());

        $repo = $this->entityManager->getRepository(User::class);
        $repo->updateTokenValidTill($user, $tokenValidTill);
        $this->assertEquals($tokenValidTill, $user->getTokenValidTill());
    }

    public function testResetToken(): void
    {
        $email = 'yurii.didenko.it@gmail.com';
        $token = 'someToken';
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        $repo = $this->entityManager->getRepository(User::class);
        $repo->setToken($user, $token, (new \DateTime()));
        $this->assertNotNull($user->getToken());
        $this->assertNotNull($user->getTokenValidTill());

        $repo->resetToken($user);
        $this->assertNull($user->getToken());
        $this->assertNull($user->getTokenValidTill());
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