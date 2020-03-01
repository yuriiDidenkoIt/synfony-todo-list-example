<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserFixtures
 */
class UserFixture extends Fixture
{
    public const VALID_TOKEN = 'VALID_TOKEN';
    public const VALID_EMAIL = 'valid@gmail.com';
    public const EXPIRED_TOKEN = 'EXPIRED_TOKEN';
    public const EXPIRED_EMAIL = 'expired@gmail.com';
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * UserFixtures constructor.
     *
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setEmail('yurii.didenko.it@gmail.com');
        $password = $this->encoder->encodePassword($user, '123456');
        $user->setPassword($password);
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::VALID_EMAIL);
        $password = $this->encoder->encodePassword($user, '123456');
        $user->setPassword($password);
        $date = (new \DateTime())->modify('+ 100 year');
        $user->setToken('VALID_TOKEN');
        $user->setTokenValidTill($date);
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::EXPIRED_EMAIL);
        $password = $this->encoder->encodePassword($user, '123456');
        $user->setPassword($password);
        $date = (new \DateTime())->modify('- 1 hour');
        $user->setToken('EXPIRED_TOKEN');
        $user->setTokenValidTill($date);
        $manager->persist($user);

        $manager->flush();
    }
}