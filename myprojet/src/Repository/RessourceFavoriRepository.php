<?php

namespace App\Repository;

use App\Entity\Ressource;
use App\Entity\RessourceFavori;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RessourceFavori>
 */
class RessourceFavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RessourceFavori::class);
    }

    public function findOneByRessourceAndUtilisateur(Ressource $ressource, Utilisateur $utilisateur): ?RessourceFavori
    {
        return $this->createQueryBuilder('rf')
            ->andWhere('rf.ressource = :ressource')
            ->andWhere('rf.utilisateur = :utilisateur')
            ->setParameter('ressource', $ressource)
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return int[]
     */
    public function findFavoriRessourceIdsByUtilisateurAndChapitre(Utilisateur $utilisateur, int $chapitreId): array
    {
        $rows = $this->createQueryBuilder('rf')
            ->select('IDENTITY(rf.ressource) AS ressourceId')
            ->innerJoin('rf.ressource', 'r')
            ->innerJoin('r.chapitre', 'ch')
            ->andWhere('rf.utilisateur = :utilisateur')
            ->andWhere('ch.id = :chapitreId')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('chapitreId', $chapitreId)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['ressourceId'], $rows);
    }
}
