<?php

namespace App\Service;

use App\Entity\Ressource;

class RessourceManager
{
    /**
     * Types de ressources valides
     */
    private const VALID_TYPES = ['video', 'audio', 'lien', 'image', 'pdf'];

    /**
     * Valeurs de badges valides
     */
    public const VALID_BADGES = ['Faible', 'Moyen', 'Bon', 'Excellent'];

    /**
     * Valide une ressource selon les règles métier
     * 
     * @throws \InvalidArgumentException si la ressource est invalide
     */
    public function validate(Ressource $ressource): bool
    {
        // Règle 1: Le titre est obligatoire (min 3 caractères)
        $titre = $ressource->getTitre();
        if (empty($titre) || strlen($titre) < 3) {
            throw new \InvalidArgumentException('Le titre est obligatoire et doit contenir au moins 3 caractères.');
        }

        // Règle 2: Le type doit être valide
        $type = $ressource->getType();
        if ($type !== null && !in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('Le type de ressource doit être: video, audio, lien, image ou pdf.');
        }

        // Règle 3: La catégorie est obligatoire
        if ($ressource->getCategorie() === null) {
            throw new \InvalidArgumentException('La catégorie est obligatoire.');
        }

        // Règle 4: Le chapitre est obligatoire
        if ($ressource->getChapitre() === null) {
            throw new \InvalidArgumentException('Le chapitre est obligatoire.');
        }

        // Règle 5: Le contenu ne peut pas être vide
        $contenu = $ressource->getContenu();
        if (empty($contenu)) {
            throw new \InvalidArgumentException('Le contenu ne peut pas être vide.');
        }

        return true;
    }

    /**
     * Calcule le score d'une ressource
     * Formule: likes * 3 + favoris * 2 + vues * 1
     */
    public function calculateScore(Ressource $ressource): int
    {
        $score = ($ressource->getNbLikes() * 3) 
               + ($ressource->getNbFavoris() * 2) 
               + $ressource->getNbVues();

        return max(0, $score);
    }

    /**
     * Détermine le badge selon le score
     */
    public function determineBadge(int $score): string
    {
        return match (true) {
            $score >= 100 => 'Excellent',
            $score >= 50 => 'Bon',
            $score >= 20 => 'Moyen',
            default => 'Faible',
        };
    }

    /**
     * Met à jour le score et le badge d'une ressource
     */
    public function updateScoreAndBadge(Ressource $ressource): void
    {
        $score = $this->calculateScore($ressource);
        $ressource->setScore($score);
        $ressource->setBadge($this->determineBadge($score));
    }

    /**
     * Vérifie si une ressource peut être publiée
     * Elle doit avoir un contenu et être validée
     */
    public function canBePublished(Ressource $ressource): bool
    {
        $contenu = $ressource->getContenu();
        
        // Le contenu ne doit pas être vide
        if (empty($contenu)) {
            return false;
        }

        // La ressource doit avoir une catégorie et un chapitre
        if ($ressource->getCategorie() === null || $ressource->getChapitre() === null) {
            return false;
        }

        return true;
    }
}
