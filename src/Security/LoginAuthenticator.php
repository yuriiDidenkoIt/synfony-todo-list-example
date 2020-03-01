<?php

namespace App\Security;

use App\Entity\User;
use App\Service\UserTokenService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class TokenAuthenticator
 */
class LoginAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var UserTokenService
     */
    private $tokenService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LoginAuthenticator constructor.
     *
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UserTokenService $tokenService
     * @param LoggerInterface $logger
     */
    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        UserTokenService $tokenService,
        LoggerInterface $logger
    )
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return JsonResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(['message' => 'Wrong Credentials'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'api_login';
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        return [
            'email' => $request->request->get('email', ''),
            'password' => $request->request->get('password', '')
        ];
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return User|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        if (!$userProvider instanceof UserProvider) {
            return null;
        }

        return $userProvider->loadUserByUsername($credentials['email']);
    }

    /**
     * @param mixed $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        if (!$this->passwordEncoder->isPasswordValid($user, $credentials['password'])) {
            throw new CustomUserMessageAuthenticationException('Wrong Credentials');
        }
        try {
            $this->tokenService->update($user);
        } catch (Exception $exception) {
            throw new CustomUserMessageAuthenticationException('Something went wrong. Try again later');
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
     * @return JsonResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): JsonResponse
    {
        $this->logger->info("Success Authentication: " . $token->getUsername());

        return new JsonResponse(
            ['token' => $token->getUser()->getToken()],
            Response::HTTP_OK
        );
    }

    /**
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }
}