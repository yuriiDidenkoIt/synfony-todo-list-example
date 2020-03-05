<?php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Security\LoginAuthenticator;
use App\Security\UserProvider;
use App\Service\UserTokenService;
use PhpParser\JsonDecoder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class LoginAuthenticator
 */
class LoginAuthenticatorTest extends WebTestCase
{
    /**
     * @var JsonDecoder
     */
    private $jsonDecoder;

    /**
     * @var LoginAuthenticator
     */
    private $loginAuthenticator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->jsonDecoder = new JsonDecoder();
    }

    /**
     * @inheritDoc
     */
    public function testSuccessLogin(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            ['email' => 'yurii.didenko.it@gmail.com', 'password' => '123456']
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @inheritDoc
     */
    public function testInvalidLogin(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            ['email' => 'yurii.didenko.it@gmail.com', 'password' => '1234564']
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $expected = ['message' => 'Wrong Credentials'];
        $actual = $this->jsonDecoder->decode($client->getResponse()->getContent());
        $this->assertEquals($expected, $actual);

        $client->request(
            'GET',
            '/api/login'
        );
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testStart(): void
    {
        $this->setLoginAuthenticator();
        $actual = $this->loginAuthenticator->start(new Request());
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $actual->getStatusCode());

        $expected = ['message' => 'Wrong Credentials'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testSupports(): void
    {
        $this->setLoginAuthenticator();
        $request = new Request([], [], ['_route' => 'api_login']);
        $actual = $this->loginAuthenticator->supports($request);
        $this->assertTrue($actual);

        $request = new Request([], [], ['_route' => 'api_todo']);
        $actual = $this->loginAuthenticator->supports($request);
        $this->assertFalse($actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetCredentials(): void
    {
        $this->setLoginAuthenticator();
        $data = ['email'=> 'test@email.com', 'password' => '12345'];
        $request = new Request([], $data);
        $actual = $this->loginAuthenticator->getCredentials($request);
        $this->assertEquals($data, $actual);

        $request = new Request();
        $actual = $this->loginAuthenticator->getCredentials($request);
        $this->assertEquals(['email'=> '', 'password' => ''], $actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetUser(): void
    {
        $this->setLoginAuthenticator();
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setPassword('123456');
        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects($this->any())->method('loadUserByUsername')->willReturn($user);
        $this->assertEquals($user, $this->loginAuthenticator->getUser(['email' => 'test@gmail.com'], $userProvider));

        $provider = $this->createMock(UserProviderInterface::class);
        $this->assertNull($this->loginAuthenticator->getUser([], $provider));
    }

    /**
     * @throws \Exception
     */
    public function testCheckCredentials(): void
    {
        $this->setLoginAuthenticator();
        $user = new User();

        $actual = $this->loginAuthenticator->checkCredentials(['password' => '12345'], $user);
        $this->assertTrue($actual);

        $this->setLoginAuthenticator(false);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->loginAuthenticator->checkCredentials(['password' => '12345'], $user);

        $this->expectExceptionMessage('Wrong Credentials');
        $this->loginAuthenticator->checkCredentials(['password' => '12345'], $user);
    }

    /**
     * @throws \Exception
     */
    public function testOnAuthenticationFailure(): void
    {
        $this->setLoginAuthenticator();
        $exception = $this->createMock(AuthenticationException::class);
        $exception->expects($this->any())
            ->method('getMessageKey')
            ->willReturn('An authentication exception occurred.');
        $exception->expects($this->any())->method('getMessageData')->willReturn([]);

        $actual = $this->loginAuthenticator->onAuthenticationFailure(new Request(), $exception);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $actual->getStatusCode());

        $expected = ['message' => 'An authentication exception occurred.'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testOnAuthenticationSuccess(): void
    {
        $this->setLoginAuthenticator();
        $token = $this->createMock(TokenInterface::class);
        $user = new User();
        $user->setEmail('yurii@email.com');
        $user->setToken('some_token');
        $token->expects($this->any())->method('getUsername')->willReturn($user->getUsername());
        $token->expects($this->any())->method('getUser')->willReturn($user);

        $actual = $this->loginAuthenticator->onAuthenticationSuccess(new Request(), $token, 'key');
        $this->assertEquals(Response::HTTP_OK, $actual->getStatusCode());

        $expected = ['token' => 'some_token'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testSupportsRememberMe(): void
    {
        $this->setLoginAuthenticator();
        $this->assertFalse($this->loginAuthenticator->supportsRememberMe());
    }

    /**
     * @param bool $isPasswordValid
     *
     * @throws \Exception
     */
    private function setLoginAuthenticator(bool $isPasswordValid = true)
    {
        $passwordEncoder = $this->createMock(UserPasswordEncoderInterface::class);
        $passwordEncoder->expects($this->any())->method('isPasswordValid')->willReturn($isPasswordValid);
        $tokenService = $this->createMock(UserTokenService::class);
        $tokenService->expects($this->any())->method('update')->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->any())->method('info')->willReturn(true);
        $logger->expects($this->any())->method('warning')->willReturn(true);
        $this->loginAuthenticator = new LoginAuthenticator($passwordEncoder, $tokenService, $logger);
    }
}