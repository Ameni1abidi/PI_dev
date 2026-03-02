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

    /**
     * @return string[]
     */
    public function findEtudiantEmailsByExamenId(int $examenId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('u.email')
            ->innerJoin('r.etudiant', 'u')
            ->andWhere('IDENTITY(r.examen) = :examenId')
            ->setParameter('examenId', $examenId)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_unique(array_map(
            static fn (array $row): string => (string) ($row['email'] ?? ''),
            $rows
        ))));
    }

    /**
     * @return string[]
     */
    public function findEtudiantPhonesByExamenId(int $examenId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('u.telephone')
            ->innerJoin('r.etudiant', 'u')
            ->andWhere('IDENTITY(r.examen) = :examenId')
            ->andWhere('u.telephone IS NOT NULL')
            ->andWhere("u.telephone <> ''")
            ->setParameter('examenId', $examenId)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_unique(array_map(
            static fn (array $row): string => trim((string) ($row['telephone'] ?? '')),
            $rows
        ))));
    }

    /**
     * @return string[]
     */
    public function findLinkedParentPhonesByExamenId(int $examenId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('p.telephone')
            ->innerJoin('r.etudiant', 'u')
            ->innerJoin('u.parent', 'p')
            ->andWhere('IDENTITY(r.examen) = :examenId')
            ->andWhere('p.telephone IS NOT NULL')
            ->andWhere("p.telephone <> ''")
            ->setParameter('examenId', $examenId)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_unique(array_map(
            static fn (array $row): string => trim((string) ($row['telephone'] ?? '')),
            $rows
        ))));
    }
}
