<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityControllerAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    // 🔐 AUTHENTIFICATION (EMAIL + PASSWORD)
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');

        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password')),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->request->get('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    // REDIRECTION SELON LE RÔLE
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $user = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_PARENT', $roles)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('parent_dashboard')
            );
        }

        if (in_array('ROLE_ETUDIANT', $roles)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('student_dashboard')
            );
        }

        if (in_array('ROLE_PROF', $roles)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_enseignant_dashboard')
            );
        }

        // fallback
        return new RedirectResponse(
            $this->urlGenerator->generate('app_home')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
