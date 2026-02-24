<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * @param string[] $roles
     * @return string[]
     */
    public function findEmailsByRoles(array $roles): array
    {
        if ($roles === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('u')
            ->select('u.email')
            ->andWhere('u.role IN (:roles)')
            ->setParameter('roles', $roles)
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
    public function findEmailsByRole(string $role): array
    {
        return $this->findEmailsByRoles([$role]);
    }

    /**
     * @param string[] $roles
     * @return string[]
     */
    public function findPhonesByRoles(array $roles): array
    {
        if ($roles === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('u')
            ->select('u.telephone')
            ->andWhere('u.role IN (:roles)')
            ->andWhere('u.telephone IS NOT NULL')
            ->andWhere("u.telephone <> ''")
            ->setParameter('roles', $roles)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_unique(array_map(
            static fn (array $row): string => trim((string) ($row['telephone'] ?? '')),
            $rows
        ))));
    }

    //    /**
    //     * @return Utilisateur[] Returns an array of Utilisateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Utilisateur
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
