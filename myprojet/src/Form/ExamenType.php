<?php

namespace App\Form;

use App\Entity\Examen;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Quiz' => 'quiz',
                    'Devoir' => 'devoir',
                    'Examen' => 'examen',
                ],
                'placeholder' => 'Choisir un type',
            ])
            ->add('dateExamen', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
            ])
            ->add('coursId', IntegerType::class, [
                'label' => 'Cours ID',
            ])
            ->add('enseignantId', IntegerType::class, [
                'label' => 'Enseignant ID',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Examen::class,
        ]);
    }
}
