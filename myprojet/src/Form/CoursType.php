<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       $builder
    ->add('titre', TextType::class, [
        'required' => false,
    ])
    ->add('description', TextType::class, [
        'required' => false,
    ])
    ->add('niveau', TextType::class, [
        'required' => false,
    ])
    ->add('dateCreation', DateType::class, [
        'widget' => 'single_text',
        'required' => false,
    ])
    ->add('enseignant', EntityType::class, [
        'class' => Utilisateur::class,
        'choice_label' => 'nom',
        'required' => false,
        'placeholder' => 'Sélectionner un enseignant',
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}
