<?php

namespace App\Tests\Functional\Security;

use App\DataFixtures\UserFixture;
use App\Entity\User;
use App\Security\TokenAuthenticator;
use App\Security\UserProvider;
use App\Service\DateTimeService;
use App\Service\UserTokenService;
use PhpParser\JsonDecoder;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class TokenAuthenticatorTest
 */
class TokenAuthenticatorTest extends WebTestCase
{

    /**
     * @var JsonDecoder
     */
    private $jsonDecoder;

    protected function setUp(): void
    {
        $this->jsonDecoder = new JsonDecoder();
    }

    /**
     * @inheritDoc
     */
    public function testSuccessRequest(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/todos',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => UserFixture::VALID_TOKEN,
            ]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @inheritDoc
     */
    public function testErrorRequest(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/todos',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => UserFixture::EXPIRED_TOKEN,
            ]
        );

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testStart(): void
    {
        $tokenAuthenticator = $this->getTokenAuthenticator();
        $actual = $tokenAuthenticator->start(new Request());
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $actual->getStatusCode());

        $expected = ['message' => 'Authentication token required'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testSupports(): void
    {
        $tokenAuthenticator = $this->getTokenAuthenticator();
        $request = new Request([], [], ['_route' => 'api_not_login']);
        $actual = $tokenAuthenticator->supports($request);
        $this->assertTrue($actual);

        $request = new Request([], [], ['_route' => 'api_login']);
        $actual = $tokenAuthenticator->supports($request);
        $this->assertFalse($actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetCredentials(): void
    {
        $tokenAuthenticator = $this->getTokenAuthenticator();
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => UserFixture::EXPIRED_TOKEN,
            ]);
        $actual = $tokenAuthenticator->getCredentials($request);
        $this->assertEquals(UserFixture::EXPIRED_TOKEN, $actual);

        $request = new Request();
        $actual = $tokenAuthenticator->getCredentials($request);
        $this->assertEquals('', $actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetUser(): void
    {
        $tokenAuthenticator = $this->getTokenAuthenticator();
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setPassword('123456');
        $user->setToken(UserFixture::VALID_TOKEN);
        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects($this->any())->method('loadUserByToken')->willReturn($user);
        $this->assertEquals($user, $tokenAuthenticator->getUser(UserFixture::VALID_TOKEN, $userProvider));

        $provider = $this->createMock(UserProviderInterface::class);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Wrong Credentials');
        $tokenAuthenticator->getUser('', $provider);
    }

    /**
     * @throws \Exception
     */
    public function testOnAuthenticationFailure(): void
    {
        $tokenAuthenticator = $this->getTokenAuthenticator();
        $exception = $this->createMock(AuthenticationException::class);
        $exception->expects($this->any())
            ->method('getMessageKey')
            ->willReturn('An authentication exception occurred.');
        $exception->expects($this->any())->method('getMessageData')->willReturn([]);

        $actual = $tokenAuthenticator->onAuthenticationFailure(new Request(), $exception);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $actual->getStatusCode());

        $expected = ['message' => 'An authentication exception occurred.'];
        $this->assertEquals($expected, $this->jsonDecoder->decode($actual->getContent()));
    }

    /**
     * @throws \Exception
     */
    public function testOnAuthenticationSuccess(): void
    {
        $tokenAuthenticator = $this->getTokenAuthenticator();
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())->method('getUser')->willReturn(new User());
        $actual = $tokenAuthenticator->onAuthenticationSuccess(new Request(), $token, 'key');
        $this->assertNull($actual);
    }

    /**
     * @throws \Exception
     */
    public function testSupportsRememberMe(): void
    {
        $tokenAuthenticator = $this->getTokenAuthenticator();
        $this->assertFalse($tokenAuthenticator->supportsRememberMe());
    }

    /**
     * @param bool $isTokenValid
     *
     * @return TokenAuthenticator
     */
    private function getTokenAuthenticator(bool $isTokenValid = true): TokenAuthenticator
    {
        $tokenService = $this->createMock(UserTokenService::class);
        $tokenService->expects($this->any())->method('update')->willReturn(true);
        $tokenService->expects($this->any())->method('prolong')->willReturn(true);

        $dateTimeService = $this->createMock(DateTimeService::class);
        $dateTimeService->expects($this->any())->method('isPast')->willReturn($isTokenValid);

        return new TokenAuthenticator(new NullLogger(), $dateTimeService, $tokenService);
    }
}