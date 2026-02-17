<?php

namespace App\Repository;

use App\Entity\Resultat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\ArrayParameterType;

/**
 * @extends ServiceEntityRepository<Resultat>
 */
class ResultatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resultat::class);
    }

    /**
     * @param int[] $eleveIds
     * @return Resultat[]
     */
    public function findByEleveIds(array $eleveIds): array
    {
        if ($eleveIds === []) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->andWhere('IDENTITY(r.etudiant) IN (:ids)')
            ->setParameter('ids', $eleveIds, ArrayParameterType::INTEGER)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
