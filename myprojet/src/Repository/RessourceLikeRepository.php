<?php

namespace App\Repository;

use App\Entity\Ressource;
use App\Entity\RessourceLike;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RessourceLike>
 */
class RessourceLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RessourceLike::class);
    }

    public function findOneByRessourceAndUtilisateur(Ressource $ressource, Utilisateur $utilisateur): ?RessourceLike
    {
        return $this->createQueryBuilder('rl')
            ->andWhere('rl.ressource = :ressource')
            ->andWhere('rl.utilisateur = :utilisateur')
            ->setParameter('ressource', $ressource)
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return int[]
     */
    public function findLikedRessourceIdsByUtilisateurAndChapitre(Utilisateur $utilisateur, int $chapitreId): array
    {
        $rows = $this->createQueryBuilder('rl')
            ->select('IDENTITY(rl.ressource) AS ressourceId')
            ->innerJoin('rl.ressource', 'r')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('rl.utilisateur = :utilisateur')
            ->andWhere('ch.id = :chapitreId')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('chapitreId', $chapitreId)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['ressourceId'], $rows);
    }
}
