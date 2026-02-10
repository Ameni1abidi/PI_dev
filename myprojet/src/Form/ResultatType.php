<?php

namespace App\Form;

use App\Entity\Examen;
use App\Entity\Resultat;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResultatType extends AbstractType
{    
    private UtilisateurRepository $utilisateurRepository;

    public function __construct(UtilisateurRepository $utilisateurRepository)
    {
        $this->utilisateurRepository = $utilisateurRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {  $etudiants = array_filter(
            $this->utilisateurRepository->findAll(),
            fn($u) => in_array('ROLE_ETUDIANT', $u->getRoles())
        );
        $builder
            ->add('note', NumberType::class, [
                'scale' => 2,
            ])
            ->add('appreciation', TextareaType::class, [
                'required' => false,
            ])
            ->add('eleveId', EntityType::class, [
    'class' => Utilisateur::class,
    'choices' => array_filter(
        $this->utilisateurRepository->findAll(),
        fn($u) => in_array('ROLE_ETUDIANT', $u->getRoles())
    ),
    'choice_label' => 'nom',
    'placeholder' => 'Choisir un étudiant',
    'label' => 'Élève',
])
            ->add('examen', EntityType::class, [
                'class' => Examen::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un examen',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resultat::class,
        ]);
    }
}
