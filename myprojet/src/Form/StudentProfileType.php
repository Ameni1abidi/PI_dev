<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class StudentProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'Nom complet',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre nom.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre email.'),
                    new Email(message: 'Email invalide.'),
                ],
            ])
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Mot de passe actuel',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                ],
                'constraints' => [
                    new Length(min: 4, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caracteres.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
