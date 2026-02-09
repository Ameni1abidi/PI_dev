<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Security\SecurityControllerAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // récupérer le mot de passe plain
            $plainPassword = $form->get('plainPassword')->getData();

            // hash du mot de passe et assignation
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // rôle par défaut
            $user->setRole('ROLE_USER');

            // enregistrer l'utilisateur
            $entityManager->persist($user);
            $entityManager->flush();

            // optionnel: envoyer un email de confirmation si EmailVerifier est configuré
            /*
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('mailer@your-domain.com', 'Confirmation Mail'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            */

            // connecter automatiquement l'utilisateur
            return $security->login($user, SecurityControllerAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
