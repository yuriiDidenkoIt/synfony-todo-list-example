<?php

namespace App\Tests\Functional\Security;

use App\DataFixtures\UserFixture;
use App\Entity\User;
use App\Security\UserProvider;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserProviderTest
 */
class UserProviderTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserProvider
     */
    private $provider;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->provider = new UserProvider($this->entityManager->getRepository(User::class));
    }

    public function testLoadUserByUsernameSuccess(): void
    {
        $user = $this->provider->loadUserByUsername(UserFixture::VALID_EMAIL);

        $this->assertEquals($user->getUsername(), UserFixture::VALID_EMAIL);
    }

    public function testLoadUserByUsernameNull(): void
    {
        $this->assertNull($this->provider->loadUserByUsername('bedEmail'));
    }

    public function testLoadUserByTokenSuccess(): void
    {
        $user = $this->provider->loadUserByToken(UserFixture::VALID_TOKEN);
        $this->assertEquals($user->getToken(), UserFixture::VALID_TOKEN);
    }

    public function testLoadUserByTokenNull(): void
    {
        $this->assertNull($this->provider->loadUserByToken('-'));
    }

    public function testRefreshUser(): void
    {
        $user = new User();
        $user->setEmail('someEmail');
        $user->setPassword('1233456');

        $actual = $this->provider->refreshUser($user);

        $this->assertSame($user, $actual);
    }

    public function testSupportsClass(): void
    {
        $this->assertTrue($this->provider->supportsClass(User::class));
        $this->assertFalse($this->provider->supportsClass(UserInterface::class));
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