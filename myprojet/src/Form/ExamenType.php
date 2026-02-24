<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Examen;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class ExamenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => true,
                'trim' => true,
            ])
            ->add('contenuFile', FileType::class, [
                'label' => 'Fichier d examen',
                'mapped' => false,
                'required' => !$options['is_edit'],
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Le fichier de contenu est obligatoire.',
                        groups: $options['is_edit'] ? ['edit'] : ['Default']
                    ),
                    new File(
                        maxSize: '10M',
                        maxSizeMessage: 'Le fichier ne doit pas depasser 10 Mo.',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'text/plain',
                        ],
                        mimeTypesMessage: 'Formats autorises: PDF, DOC, DOCX, TXT.'
                    ),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Quiz' => 'quiz',
                    'Devoir' => 'devoir',
                    'Examen' => 'examen',
                ],
                'placeholder' => 'Choisir un type',
                'required' => true,
            ])
            ->add('dateExamen', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'html5' => true,
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Duree (minutes)',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'max' => 600,
                ],
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un cours',
                'label' => 'Cours',
                'required' => true,
            ])
            ->add('enseignant', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'nom',
                'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('u')
                    ->andWhere('u.role IN (:roles)')
                    ->setParameter('roles', ['ROLE_PROF', 'ROLE_ENSEIGNANT'])
                    ->orderBy('u.nom', 'ASC'),
                'placeholder' => 'Choisir un enseignant',
                'label' => 'Enseignant',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Examen::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
