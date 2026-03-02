<?php

namespace App\Service;

use App\Entity\Resultat;

class ResultatManager
{
    private const NOTE_MIN = 0;
    private const NOTE_MAX = 20;
    private const APPRECIATION_MAX_LENGTH = 1000;

    /**
     * Valide un résultat selon les règles métier:
     * - La note doit être comprise entre 0 et 20
     * - L'appréciation ne doit pas dépasser 1000 caractères
     * 
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée
     */
    public function validate(Resultat $resultat): bool
    {
        // Validation de la note entre 0 et 20
        $note = $resultat->getNote();
        
        if ($note === null) {
            throw new \InvalidArgumentException('La note est obligatoire');
        }

        $noteValue = (float) $note;
        
        if ($noteValue < self::NOTE_MIN || $noteValue > self::NOTE_MAX) {
            throw new \InvalidArgumentException('La note doit être comprise entre ' . self::NOTE_MIN . ' et ' . self::NOTE_MAX);
        }

        // Validation de la longueur de l'appréciation
        $appreciation = $resultat->getAppreciation();
        
        if ($appreciation !== null && strlen($appreciation) > self::APPRECIATION_MAX_LENGTH) {
            throw new \InvalidArgumentException('L\'appréciation ne doit pas dépasser ' . self::APPRECIATION_MAX_LENGTH . ' caractères');
        }

        return true;
    }

    /**
     * Calcule la mention basée sur la note
     */
    public function getMention(float $note): string
    {
        if ($note >= 16) {
            return 'Excellent';
        }
        
        if ($note >= 14) {
            return 'Très Bien';
        }
        
        if ($note >= 12) {
            return 'Bien';
        }
        
        if ($note >= 10) {
            return 'Passable';
        }
        
        return 'Insuffisant';
    }

    /**
     * Vérifie si l'étudiant a réussi (note >= 10)
     */
    public function hasPassed(Resultat $resultat): bool
    {
        $note = (float) $resultat->getNote();
        return $note >= 10;
    }
}
