<?php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Security\LoginAuthenticator;
use App\Security\UserProvider;
use App\Service\UserTokenService;
use PhpParser\JsonDecoder;
use Psr\Log\NullLogger;
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
        $loginAuthenticator = $this->getLoginAuthenticator();
        $actual = $loginAuthenticator->start(new Request());
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $actual->getStatusCode());

        $expected = ['message' => 'Wrong Credentials'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testSupports(): void
    {
        $loginAuthenticator = $this->getLoginAuthenticator();
        $request = new Request([], [], ['_route' => 'api_login']);
        $actual = $loginAuthenticator->supports($request);
        $this->assertTrue($actual);

        $request = new Request([], [], ['_route' => 'api_todo']);
        $actual = $loginAuthenticator->supports($request);
        $this->assertFalse($actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetCredentials(): void
    {
        $loginAuthenticator = $this->getLoginAuthenticator();
        $data = ['email'=> 'test@email.com', 'password' => '12345'];
        $request = new Request([], $data);
        $actual = $loginAuthenticator->getCredentials($request);
        $this->assertEquals($data, $actual);

        $request = new Request();
        $actual = $loginAuthenticator->getCredentials($request);
        $this->assertEquals(['email'=> '', 'password' => ''], $actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetUser(): void
    {
        $loginAuthenticator = $this->getLoginAuthenticator();
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setPassword('123456');
        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects($this->any())->method('loadUserByUsername')->willReturn($user);
        $this->assertEquals($user, $loginAuthenticator->getUser(['email' => 'test@gmail.com'], $userProvider));

        $provider = $this->createMock(UserProviderInterface::class);
        $this->assertNull($loginAuthenticator->getUser([], $provider));
    }

    /**
     * @throws \Exception
     */
    public function testCheckCredentials(): void
    {
        $loginAuthenticator = $this->getLoginAuthenticator();
        $user = new User();

        $actual = $loginAuthenticator->checkCredentials(['password' => '12345'], $user);
        $this->assertTrue($actual);

        $loginAuthenticator = $this->getLoginAuthenticator(false);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $loginAuthenticator->checkCredentials(['password' => '12345'], $user);

        $this->expectExceptionMessage('Wrong Credentials');
        $loginAuthenticator->checkCredentials(['password' => '12345'], $user);
    }

    /**
     * @throws \Exception
     */
    public function testOnAuthenticationFailure(): void
    {
        $loginAuthenticator = $this->getLoginAuthenticator();
        $exception = $this->createMock(AuthenticationException::class);
        $exception->expects($this->any())
            ->method('getMessageKey')
            ->willReturn('An authentication exception occurred.');
        $exception->expects($this->any())->method('getMessageData')->willReturn([]);

        $actual = $loginAuthenticator->onAuthenticationFailure(new Request(), $exception);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $actual->getStatusCode());

        $expected = ['message' => 'An authentication exception occurred.'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testOnAuthenticationSuccess(): void
    {
        $loginAuthenticator = $this->getLoginAuthenticator();
        $token = $this->createMock(TokenInterface::class);
        $user = new User();
        $user->setEmail('yurii@email.com');
        $user->setToken('some_token');
        $token->expects($this->any())->method('getUsername')->willReturn($user->getUsername());
        $token->expects($this->any())->method('getUser')->willReturn($user);

        $actual = $loginAuthenticator->onAuthenticationSuccess(new Request(), $token, 'key');
        $this->assertEquals(Response::HTTP_OK, $actual->getStatusCode());

        $expected = ['token' => 'some_token'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testSupportsRememberMe(): void
    {
        $loginAuthenticator = $this->getLoginAuthenticator();
        $this->assertFalse($loginAuthenticator->supportsRememberMe());
    }

    /**
     * @param bool $isPasswordValid
     *
     * @throws \Exception
     */
    private function getLoginAuthenticator(bool $isPasswordValid = true): LoginAuthenticator
    {
        $passwordEncoder = $this->createMock(UserPasswordEncoderInterface::class);
        $passwordEncoder->expects($this->any())->method('isPasswordValid')->willReturn($isPasswordValid);
        $tokenService = $this->createMock(UserTokenService::class);
        $tokenService->expects($this->any())->method('update')->willReturn(true);

        return new LoginAuthenticator($passwordEncoder, $tokenService, new NullLogger());
    }
}