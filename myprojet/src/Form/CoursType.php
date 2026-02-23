<?php

namespace App\Form;

use App\Entity\Cours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
     ->add('badge', ChoiceType::class, [
        'choices' => [
            'Nouveau' => 'nouveau',
            'Populaire' => 'populaire',
            'À la une' => 'a_la_une'
        ],
        'required' => false,
        'placeholder' => 'Choisir un badge'
    ])
     ->add('niveau', TextType::class, [
        'required' => false,
    ])
    ->add('dateCreation', DateType::class, [
        'widget' => 'single_text',
        'required' => false,
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}
