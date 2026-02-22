<?php

namespace App\Security;

use App\Repository\UtilisateurRepository;
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
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityControllerAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UtilisateurRepository $utilisateurRepository
    ) {}

    // AUTHENTIFICATION (EMAIL + PASSWORD)
    public function authenticate(Request $request): Passport
    {
        $email = trim((string) $request->request->get('email', ''));
        $password = (string) $request->request->get('password', '');

        if ($email === '' && $password === '') {
            throw new CustomUserMessageAuthenticationException('Email et mot de passe sont obligatoires.');
        }

        if ($email === '') {
            throw new CustomUserMessageAuthenticationException('Email obligatoire.');
        }

        if ($password === '') {
            throw new CustomUserMessageAuthenticationException('Mot de passe obligatoire.');
        }

        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            new UserBadge($email, function (string $userIdentifier) {
                $user = $this->utilisateurRepository->findOneBy([
                    'email' => $userIdentifier,
                    'isVerified' => true,
                ]);

                if ($user) {
                    return $user;
                }

                $user = $this->utilisateurRepository->findOneBy(['email' => $userIdentifier]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
                }

                if (method_exists($user, 'isVerified') && !$user->isVerified()) {
                    throw new CustomUserMessageAuthenticationException('Veuillez confirmer votre adresse email avant de vous connecter.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->request->get('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    // REDIRECTION SELON LE ROLE
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $user = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_PARENT', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_parent_dashboard')
            );
        }

        if (in_array('ROLE_ETUDIANT', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_student_dashboard')
            );
        }

        if (in_array('ROLE_PROF', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_enseignant_dashboard')
            );
        }

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_admin')
            );
        }

        if (in_array('ROLE_USER', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_choose_role')
            );
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('app_home')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}