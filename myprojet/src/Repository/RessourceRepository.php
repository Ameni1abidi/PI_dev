<?php

namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressource>
 */
class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    /**
     * @return Ressource[]
     */
    public function findByCategorieNom(string $categorieNom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.categorie', 'c')
            ->andWhere('LOWER(c.nom) = LOWER(:nom)')
            ->setParameter('nom', trim($categorieNom))
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ressource[]
     */
    public function findByChapitreId(int $chapitreId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('ch.id = :id')
            ->setParameter('id', $chapitreId)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Ressource[] Returns an array of Ressource objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Ressource
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
