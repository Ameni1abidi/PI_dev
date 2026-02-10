<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
             ->add('nom', null, [
        'label' => 'Nom',
        'constraints' => [
            new NotBlank(message: 'Veuillez entrer votre nom'),
        ],
    ])
            ->add('email')
           ->add('role', ChoiceType::class, [
        'choices' => [
            'Élève' => 'ROLE_STUDENT',
            'Professeur' => 'ROLE_PROF',
            'Parent' => 'ROLE_PARENT',
        ],
        'label' => 'Rôle',
    
])
            ->add('password', PasswordType::class, [
        'label' => 'Mot de passe',
        
    ])
            

            
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
    
}
