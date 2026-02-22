<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Repository\UtilisateurRepository;
use App\Security\EmailVerifier;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private HttpClientInterface $httpClient,
        #[Autowire('%env(bool:RECAPTCHA2_ENABLED)%')]
        private bool $recaptchaEnabled,
        #[Autowire('%env(string:RECAPTCHA2_SITE_KEY)%')]
        private string $recaptchaSiteKey,
        #[Autowire('%env(string:RECAPTCHA2_SECRET)%')]
        private string $recaptchaSecret
    )
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isRecaptchaV2Valid($request)) {
                $this->addFlash('error', 'Veuillez confirmer que vous n etes pas un robot.');

                return $this->render('home/index.html.twig', [
                    'registrationForm' => $form->createView(),
                    'focus_login' => true,
                    'auth_mode' => 'register',
                    'recaptcha_enabled' => $this->recaptchaEnabled,
                    'recaptcha_site_key' => $this->recaptchaSiteKey,
                ]);
            }

            $email = strtolower(trim((string) $user->getEmail()));
            $existingUser = $utilisateurRepository->findOneBy(['email' => $email]);

            if ($existingUser) {
                if ($existingUser->isVerified()) {
                    $this->addFlash('error', 'Un compte existe deja avec cet email.');
                    return $this->redirectToRoute('app_register');
                }

                try {
                    $this->emailVerifier->sendEmailConfirmation(
                        'app_verify_email',
                        $existingUser,
                        (new TemplatedEmail())
                            ->from(new Address('no-reply@eduflex.tn', 'Eduflex'))
                            ->to((string) $existingUser->getEmail())
                            ->subject('Confirmez votre compte Eduflex')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                    );
                    $this->addFlash('success', 'Un compte non confirme existe deja. Un nouveau lien de confirmation vient d etre envoye.');
                } catch (TransportExceptionInterface|\Throwable $exception) {
                    $this->addFlash('error', 'Compte existant non confirme, mais l envoi du lien a echoue. Reessayez plus tard.');
                }

                return $this->redirectToRoute('app_login');
            }

            $user->setEmail($email);
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('no-reply@eduflex.tn', 'Eduflex'))
                        ->to((string) $user->getEmail())
                        ->subject('Confirmez votre compte Eduflex')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );
                $this->addFlash('success', 'Compte cree. Verifiez votre boite mail pour confirmer votre compte.');
            } catch (TransportExceptionInterface|\Throwable $exception) {
                $this->addFlash('error', 'Compte cree, mais le mail de confirmation n\'a pas pu etre envoye pour le moment.');
            }

            return $this->redirectToRoute('app_login');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs. Merci de verifier les champs.');
        }

        return $this->render('home/index.html.twig', [
            'registrationForm' => $form->createView(),
            'focus_login' => true,
            'auth_mode' => 'register',
            'recaptcha_enabled' => $this->recaptchaEnabled,
            'recaptcha_site_key' => $this->recaptchaSiteKey,
        ]);
    }

    private function isRecaptchaV2Valid(Request $request): bool
    {
        if (!$this->recaptchaEnabled) {
            return true;
        }

        $token = (string) $request->request->get('g-recaptcha-response', '');
        if ($token === '' || $this->recaptchaSecret === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->recaptchaSecret,
                    'response' => $token,
                    'remoteip' => (string) $request->getClientIp(),
                ],
            ]);

            $data = $response->toArray(false);
            return (bool) ($data['success'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerService $mailer
    ): Response
    {
        $id = $request->query->get('id');
        $user = $id ? $entityManager->getRepository(Utilisateur::class)->find($id) : null;

        if (!$user) {
            $this->addFlash('verify_email_error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_register');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (\Throwable $exception) {
            $this->addFlash('verify_email_error', 'Lien invalide ou expire.');
            return $this->redirectToRoute('app_register');
        }

        $htmlContent = '<h2>Bienvenue '.$user->getNom().'</h2>
                        <p>Votre compte a ete confirme avec succes sur la plateforme Eduflex.</p>';
        $mailer->sendEmail((string) $user->getEmail(), 'Bienvenue sur Eduflex', $htmlContent);

        $this->addFlash('success', 'Votre email est confirme. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}
