<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JwtRedirectHandler implements AuthenticationSuccessHandlerInterface
{
    /** @var JWTTokenManagerInterface */
    private $jwtTokenManager;
    /** @var FrontendUriHandler */
    private $frontendUriHandler;

    /**
     * JwtRedirectHandler constructor.
     *
     * @param JWTTokenManagerInterface $jwtTokenManager
     * @param FrontendUriHandler       $frontendUriHandler
     */
    public function __construct(JWTTokenManagerInterface $jwtTokenManager, FrontendUriHandler $frontendUriHandler)
    {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->frontendUriHandler = $frontendUriHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $jwt = $this->jwtTokenManager->create($user);
        $url = $this->generateFrontendUri($request, $jwt);

        return new RedirectResponse($url);
    }

    /**
     * @param Request $request
     * @param string  $jwt
     * @param User    $user
     *
     * @return string
     */
    private function generateFrontendUri(Request $request, string $jwt): string
    {
        $uri = $this->frontendUriHandler->extractUri($request);

        return \sprintf('%s?jwt=%s', $uri, $jwt);
    }
}
