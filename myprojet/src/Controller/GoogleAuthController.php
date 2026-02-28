<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Security\SecurityControllerAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google_main')
            ->redirect(['openid', 'profile', 'email'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(
        Request $request,
        ClientRegistry $clientRegistry,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        LoggerInterface $logger
    ): Response {
        $this->configureCaBundleForOauth();

        try {
            $googleUser = $clientRegistry->getClient('google_main')->fetchUser();
        } catch (\Throwable $exception) {
            $logger->error('Google OAuth fetchUser failed', [
                'message' => $exception->getMessage(),
                'class' => $exception::class,
            ]);
            $this->addFlash('error', 'Connexion Google impossible: '.$exception->getMessage());
            return $this->redirectToRoute('app_login');
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        if ($email === '') {
            $this->addFlash('error', 'Google n a pas renvoye d adresse email.');
            return $this->redirectToRoute('app_login');
        }

        $user = $utilisateurRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $displayName = trim((string) $googleUser->getName());
            if ($displayName === '') {
                $displayName = explode('@', $email)[0];
            }

            $user = new Utilisateur();
            $user->setEmail($email);
            $user->setNom($displayName);
            $user->setRole('ROLE_USER');
            $user->setIsVerified(true);
            $user->setPassword($userPasswordHasher->hashPassword($user, bin2hex(random_bytes(24))));

            $entityManager->persist($user);
            $entityManager->flush();
        } elseif (!$user->isVerified()) {
            $user->setIsVerified(true);
            $entityManager->flush();
        }

        $response = $security->login($user, SecurityControllerAuthenticator::class, 'main');
        if ($response instanceof Response) {
            return $response;
        }

        return $this->redirectToRoute($this->getTargetRouteByRole($user));
    }

    private function getTargetRouteByRole(Utilisateur $user): string
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_PARENT', $roles, true)) {
            return 'app_parent_dashboard';
        }
        if (in_array('ROLE_ETUDIANT', $roles, true)) {
            return 'app_student_dashboard';
        }
        if (in_array('ROLE_PROF', $roles, true)) {
            return 'app_enseignant_dashboard';
        }
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'app_admin';
        }

        return 'app_home';
    }

    private function configureCaBundleForOauth(): void
    {
        $configured = (string) ini_get('curl.cainfo');
        if ($configured !== '' && is_file($configured)) {
            return;
        }

        $candidates = [
            'C:\\php\\extras\\ssl\\cacert.pem',
            __DIR__.'/../../var/cacert.pem',
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            ini_set('curl.cainfo', $path);
            ini_set('openssl.cafile', $path);
            putenv('SSL_CERT_FILE='.$path);
            putenv('CURL_CA_BUNDLE='.$path);
            break;
        }
    }
}
