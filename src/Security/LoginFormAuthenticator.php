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
    private const REDIRECT_SESSION_KEY = 'login_redirect_to';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function authenticate(Request $request): Passport
    {
        // ✅ Si on arrive sur /connexion?redirect=..., on mémorise en session
        // (utile quand la redirection vers login est faite "à la main" ailleurs)
        $redirect = (string) ($request->request->get('redirect') ?? $request->query->get('redirect', ''));
        if ($redirect !== '' && $this->isSafeRelativePath($redirect)) {
            $request->getSession()->set(self::REDIRECT_SESSION_KEY, $redirect);
        }

        $email = $request->getPayload()->getString('email');
        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): RedirectResponse {

        // 1) ✅ redirect explicite (le plus fiable)
        $sessionRedirect = (string) $request->getSession()->get(self::REDIRECT_SESSION_KEY, '');
        if ($sessionRedirect !== '' && $this->isSafeRelativePath($sessionRedirect)) {
            $request->getSession()->remove(self::REDIRECT_SESSION_KEY);
            return new RedirectResponse($sessionRedirect);
        }

        // 2) ✅ target path Symfony (si stocké automatiquement)
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        if ($targetPath && !preg_match('#/index\.php#', $targetPath)) {
            return new RedirectResponse($targetPath);
        }

        // 3) fallback
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * Sécurité: on accepte uniquement un chemin relatif interne ("/...") pour éviter open redirect.
     */
    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || $path[0] !== '/') {
            return false;
        }
        if (str_starts_with($path, '//')) {
            return false;
        }
        // bloquer URL absolues déguisées
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $path)) {
            return false;
        }
        return true;
    }
}
