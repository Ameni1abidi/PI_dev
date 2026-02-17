<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $loginEmailError = null;
        $loginPasswordError = null;
        $globalError = $error;

        if ($error) {
            $message = $error->getMessageKey();

            if ($message === 'Email obligatoire.') {
                $loginEmailError = $message;
                $globalError = null;
            } elseif ($message === 'Mot de passe obligatoire.') {
                $loginPasswordError = $message;
                $globalError = null;
            } elseif ($message === 'Email et mot de passe sont obligatoires.') {
                $loginEmailError = 'Email obligatoire.';
                $loginPasswordError = 'Mot de passe obligatoire.';
                $globalError = null;
            }
        }

        return $this->render('home/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $globalError,
            'login_email_error' => $loginEmailError,
            'login_password_error' => $loginPasswordError,
            'focus_login' => true,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
