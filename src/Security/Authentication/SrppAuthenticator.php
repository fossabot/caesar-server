<?php

declare(strict_types=1);

namespace App\Security\Authentication;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class SrppAuthenticator extends AbstractGuardAuthenticator
{
    public const SERVER_SESSION_KEY_FIELD = 'serverSessionKey';
    private const CLIENT_SESSION_KEY_FIELD = 'clientSessionKey';
    private const EMAIL_FIELD = 'email';
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(EntityManagerInterface $em, RouterInterface $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('_login'));
    }

    public function supports(Request $request): bool
    {
        return 'srp_login_confirm' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function getCredentials(Request $request): array
    {
        $parsedRequest = json_decode($request->getContent(), true);

        return [
            self::SERVER_SESSION_KEY_FIELD => $request->getSession()->get(self::SERVER_SESSION_KEY_FIELD),
            self::CLIENT_SESSION_KEY_FIELD => $parsedRequest[self::CLIENT_SESSION_KEY_FIELD],
            self::EMAIL_FIELD => $parsedRequest[self::EMAIL_FIELD],
        ];
    }

    /**
     * @param mixed $credentials
     *
     * @throws NonUniqueResultException
     *
     * @return User|UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if (!$credentials[self::EMAIL_FIELD]) {
            return null;
        }

        return $this->em->getRepository(User::class)->findOneByEmail($credentials[self::EMAIL_FIELD]);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $credentials[self::SERVER_SESSION_KEY_FIELD] === $credentials[self::CLIENT_SESSION_KEY_FIELD];
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new AccessDeniedException('Authentication Failure');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): JsonResponse
    {
        return new JsonResponse([
            'redirect' => $this->router->generate('easyadmin', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
