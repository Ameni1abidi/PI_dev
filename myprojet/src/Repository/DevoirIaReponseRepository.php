<?php

namespace App\Repository;

use App\Entity\DevoirIa;
use App\Entity\DevoirIaReponse;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DevoirIaReponse>
 */
class DevoirIaReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DevoirIaReponse::class);
    }

    public function findOneByEleveAndDevoir(Utilisateur $eleve, DevoirIa $devoir): ?DevoirIaReponse
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.eleve = :eleve')
            ->andWhere('r.devoir = :devoir')
            ->setParameter('eleve', $eleve)
            ->setParameter('devoir', $devoir)
            ->orderBy('r.dateSoumission', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<DevoirIaReponse>
     */
    public function findByEleveAndDevoir(Utilisateur $eleve, DevoirIa $devoir, int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.eleve = :eleve')
            ->andWhere('r.devoir = :devoir')
            ->setParameter('eleve', $eleve)
            ->setParameter('devoir', $devoir)
            ->orderBy('r.dateSoumission', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
