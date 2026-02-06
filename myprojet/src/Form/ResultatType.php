<?php

namespace App\Form;

use App\Entity\Examen;
use App\Entity\Resultat;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResultatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', NumberType::class, [
                'scale' => 2,
            ])
            ->add('appreciation', TextareaType::class, [
                'required' => false,
            ])
            ->add('eleveId', IntegerType::class, [
                'label' => 'Élève ID',
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
