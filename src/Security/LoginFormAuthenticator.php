<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_connexion';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');

        $request->getSession()->set(
            '_security.last_username',
            $email
        );

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials(
                $request->getPayload()->getString('password')
            ),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->getPayload()->getString('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): RedirectResponse {
        // ✅ MÉCANISME SYMFONY OFFICIEL
        $targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        );

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        // fallback propre
        return new RedirectResponse(
            $this->urlGenerator->generate('app_profil')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
