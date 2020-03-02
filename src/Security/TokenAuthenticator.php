<?php

namespace App\Security;

use App\Entity\User;
use App\Service\DateTimeService;
use App\Service\UserTokenService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class TokenAuthenticator
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    public const TOKEN_HEADER = 'X-AUTH-TOKEN';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DateTimeService
     */
    private $dateTimeService;

    /**
     * @var UserTokenService
     */
    private $tokenService;

    /**
     * TokenAuthenticator constructor.
     *
     * @param LoggerInterface $logger
     * @param DateTimeService $dateTimeService
     * @param UserTokenService $tokenService
     */
    public function __construct(
        LoggerInterface $logger,
        DateTimeService $dateTimeService,
        UserTokenService $tokenService
    )
    {
        $this->logger = $logger;
        $this->dateTimeService = $dateTimeService;
        $this->tokenService = $tokenService;
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return JsonResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(
            ['message' => 'Authentication token required'],
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') !== 'api_login';
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    public function getCredentials(Request $request): ?string
    {
        return $request->headers->get(self::TOKEN_HEADER, '');
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return User
     */
    public function getUser($credentials, UserProviderInterface $userProvider): User
    {
        if (
            !trim($credentials)
            || !$userProvider instanceof UserProvider
            || !$user = $userProvider->loadUserByToken($credentials)
        ) {
            throw new CustomUserMessageAuthenticationException('Wrong credentials');
        }

        return $user;
    }

    /**
     * @param mixed $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $tokenValidTill = $user->getTokenValidTill();
        if ($tokenValidTill === null || $this->dateTimeService->isPast($tokenValidTill)) {
            throw new CustomUserMessageAuthenticationException('Token was expired. Try to login again');
        }

        return true;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        $this->logger->warning($message, $exception->getTrace());

        return new JsonResponse(
            ['message' => $message],
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     *
     * @return null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        try {
            $this->tokenService->prolong($token->getUser());
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }
        $this->logger->info(
            "Success request by token for user : " . $token->getUsername() . '. URL: ' . $request->getRequestUri()
        );

        return null;
    }

    /**
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }
}