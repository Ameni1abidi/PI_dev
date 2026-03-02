<?php

namespace App\Repository;

use App\Entity\Examen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Examen>
 */
class ExamenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Examen::class);
    }

    /**
     * Retourne uniquement les examens ayant des relations valides.
     * Evite les erreurs Twig quand des IDs orphelins existent en base.
     *
     * @return Examen[]
     */
    public function findAllWithExistingRelations(): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.cours', 'c')
            ->addSelect('c')
            ->innerJoin('e.enseignant', 'u')
            ->addSelect('u')
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
