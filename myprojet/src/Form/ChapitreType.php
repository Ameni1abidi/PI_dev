<?php

namespace App\Form;

use App\Entity\Chapitre;
use App\Entity\Cours;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChapitreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $contentChoices = [
            'Fichier' => 'fichier',
            'Video' => 'video',
            'Devoir' => 'devoir',
            'Exercice corrige' => 'exercice_corrige',
        ];

        if ($options['allow_text_type']) {
            $contentChoices = ['Texte' => 'texte'] + $contentChoices;
        }

        $builder
            ->add('titre')
            ->add('ordre')
            ->add('typeContenu', ChoiceType::class, [
                'choices' => $contentChoices,
                'required' => false,
            ]);

        if ($options['show_text_field']) {
            $builder->add('contenuTexte', null, [
                'required' => false,
            ]);
        }

        $builder
            ->add('contenuFichier', FileType::class, [
                'label' => 'Fichier PDF',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => '.pdf,.doc,.docx,.txt',
                ],
            ])
            ->add('videoUrl', null, [
                'required' => false,
            ])
            ->add('dureeEstimee', null, [
                'required' => false,
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un cours',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chapitre::class,
            'show_text_field' => true,
            'allow_text_type' => true,
        ]);
    }
}
