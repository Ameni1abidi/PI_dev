<?php

namespace App\Service;

use App\Entity\Ressource;

class ScoreCalculatorService
{
    private const LIKE_WEIGHT = 3;
    private const FAVORI_WEIGHT = 2;
    private const VUE_WEIGHT = 1;

    public function recalculate(Ressource $ressource): void
    {
        $score = ($ressource->getNbLikes() * self::LIKE_WEIGHT)
            + ($ressource->getNbFavoris() * self::FAVORI_WEIGHT)
            + ($ressource->getNbVues() * self::VUE_WEIGHT);

        $ressource->setScore($score);
    }

    public function applyRelativeBadge(Ressource $ressource, int $maxScore): void
    {
        $score = $ressource->getScore();
        if ($maxScore <= 0 || $score <= 0) {
            $ressource->setBadge('Moyen');

            return;
        }

        if ($score >= $maxScore) {
            $ressource->setBadge('Excellent');

            return;
        }

        if ($score >= (int) ceil($maxScore * 0.5)) {
            $ressource->setBadge('Bon');

            return;
        }

        $ressource->setBadge('Moyen');
    }

}
