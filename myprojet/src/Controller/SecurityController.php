<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route(path: '/choisir-role', name: 'app_choose_role', methods: ['GET', 'POST'])]
    public function chooseRole(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getRole') || !method_exists($user, 'setRole')) {
            return $this->redirectToRoute('app_login');
        }

        $allowedRoles = [
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_PARENT' => 'Parent',
            'ROLE_ETUDIANT' => 'Eleve',
            'ROLE_PROF' => 'Enseignant',
        ];

        $currentRole = (string) $user->getRole();
        if ($currentRole !== '' && $currentRole !== 'ROLE_USER' && isset($allowedRoles[$currentRole])) {
            return $this->redirectToRoute($this->getTargetRouteByRole($currentRole));
        }

        if ($request->isMethod('POST')) {
            $submittedToken = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('choose_role', $submittedToken)) {
                $this->addFlash('error', 'Session invalide, veuillez reessayer.');
                return $this->redirectToRoute('app_choose_role');
            }

            $selectedRole = (string) $request->request->get('role');
            if (!isset($allowedRoles[$selectedRole])) {
                $this->addFlash('error', 'Veuillez choisir un role valide.');
                return $this->redirectToRoute('app_choose_role');
            }

            $user->setRole($selectedRole);
            $entityManager->flush();

            $this->addFlash('success', 'Role enregistre avec succes.');

            return $this->redirectToRoute($this->getTargetRouteByRole($selectedRole));
        }

        return $this->render('security/choose_role.html.twig', [
            'current_role' => $currentRole,
            'allowed_roles' => $allowedRoles,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function getTargetRouteByRole(string $role): string
    {
        return match ($role) {
            'ROLE_PARENT' => 'app_parent_dashboard',
            'ROLE_ETUDIANT' => 'app_student_dashboard',
            'ROLE_PROF' => 'app_enseignant_dashboard',
            'ROLE_ADMIN' => 'app_admin',
            default => 'app_home',
        };
    }
}