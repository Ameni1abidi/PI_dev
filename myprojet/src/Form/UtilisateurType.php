<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('telephone', TextType::class, [
                'required' => false,
                'label' => 'Telephone (E.164, ex: +21612345678)',
            ])
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
            ->add('parent', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Aucun parent',
                'label' => 'Parent',
                'query_builder' => static fn (UtilisateurRepository $repository) => $repository->createQueryBuilder('u')
                    ->andWhere('u.role = :role')
                    ->setParameter('role', 'ROLE_PARENT')
                    ->orderBy('u.nom', 'ASC'),
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
