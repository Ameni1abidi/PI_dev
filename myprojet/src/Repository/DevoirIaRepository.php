<?php

namespace App\Repository;

use App\Entity\DevoirIa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DevoirIa>
 */
class DevoirIaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DevoirIa::class);
    }

    /**
     * @return list<DevoirIa>
     */
    public function findForTeacher(int $enseignantId): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.enseignant', 'ens')
            ->leftJoin('d.cours', 'c')
            ->leftJoin('c.enseignant', 'cEns')
            ->andWhere('ens.id = :enseignantId OR cEns.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->orderBy('d.dateCreation', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<DevoirIa>
     */
    public function findPublishedForStudents(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->setParameter('statut', 'publie')
            ->orderBy('d.dateEcheance', 'ASC')
            ->addOrderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
