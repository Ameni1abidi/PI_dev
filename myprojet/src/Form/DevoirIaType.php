<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\DevoirIa;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DevoirIaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => true,
                'trim' => true,
                'label' => 'Titre du devoir',
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un cours',
                'required' => true,
            ])
            ->add('niveauDifficulte', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Facile' => 'facile',
                    'Moyen' => 'moyen',
                    'Difficile' => 'difficile',
                ],
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Duree (minutes)',
                'attr' => [
                    'min' => 5,
                    'max' => 180,
                ],
            ])
            ->add('dateEcheance', DateType::class, [
                'label' => 'Date limite (optionnelle)',
                'widget' => 'single_text',
                'required' => false,
                'html5' => true,
            ])
            ->add('nbQcm', IntegerType::class, [
                'label' => 'Nombre de QCM',
                'attr' => [
                    'min' => 0,
                    'max' => 20,
                ],
            ])
            ->add('nbVraiFaux', IntegerType::class, [
                'label' => 'Nombre de Vrai/Faux',
                'attr' => [
                    'min' => 0,
                    'max' => 20,
                ],
            ])
            ->add('nbReponseCourte', IntegerType::class, [
                'label' => 'Nombre de Reponses courtes',
                'attr' => [
                    'min' => 0,
                    'max' => 20,
                ],
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Consignes (optionnelles)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ex: Repondre clairement. Justifier pour les reponses courtes.',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DevoirIa::class,
        ]);
    }
}
