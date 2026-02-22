<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Chapitre;
use App\Entity\Ressource;
use App\Repository\CategorieRepository;
use App\Repository\ChapitreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => true,
                'trim' => true,
            ])
            ->add('availableAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de disponibilite',
            ])
            ->add('videoUrl', UrlType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Video URL',
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^$|^https:\\/\\//i',
                        message: 'L URL video doit commencer par https://'
                    ),
                ],
            ])
            ->add('videoFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '100M'
                    ),
                ],
            ])
            ->add('audioUrl', UrlType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Audio URL',
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^$|^https:\\/\\//i',
                        message: 'L URL audio doit commencer par https://'
                    ),
                ],
            ])
            ->add('audioFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '50M'
                    ),
                ],
            ])
            ->add('lienUrl', UrlType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Lien externe',
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^$|^https:\\/\\//i',
                        message: 'Le lien doit commencer par https://'
                    ),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '5M'
                    ),
                ],
            ])
            ->add('documentFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Document PDF',
                'constraints' => [
                    new Assert\File(
                        maxSize: '20M'
                    ),
                ],
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'choice_attr' => static function (?Categorie $categorie): array {
                    $nom = strtolower((string) ($categorie?->getNom() ?? ''));

                    return ['data-kind' => $nom];
                },
                'query_builder' => static function (CategorieRepository $repository) {
                    return $repository->createQueryBuilder('c')
                        ->andWhere('LOWER(c.nom) IN (:noms)')
                        ->setParameter('noms', ['video', 'audio', 'lien', 'image', 'pdf'])
                        ->orderBy('c.nom', 'ASC');
                },
                'placeholder' => 'Choisir une categorie',
                'required' => true,
            ])
            ->add('chapitre', EntityType::class, [
                'class' => Chapitre::class,
                'choice_label' => static function (Chapitre $chapitre): string {
                    $coursTitre = $chapitre->getCours()?->getTitre();
                    $chapitreTitre = (string) ($chapitre->getTitre() ?? '');

                    return $coursTitre ? $coursTitre.' - '.$chapitreTitre : $chapitreTitre;
                },
                'query_builder' => static function (ChapitreRepository $repository) {
                    return $repository->createQueryBuilder('ch')
                        ->leftJoin('ch.cours', 'c')
                        ->addSelect('c')
                        ->orderBy('c.titre', 'ASC')
                        ->addOrderBy('ch.ordre', 'ASC')
                        ->addOrderBy('ch.titre', 'ASC');
                },
                'placeholder' => 'Choisir un chapitre',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ressource::class,
        ]);
    }
}
