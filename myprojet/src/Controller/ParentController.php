<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\ResultatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParentController extends AbstractController
{
    #[Route('/parent/dashboard', name: 'app_parent_dashboard', methods: ['GET'])]
    public function dashboard(ResultatRepository $resultatRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Authentification requise.');
        }

        $enfants = $user->getEnfants()->toArray();
        $eleveIds = array_map(static fn (Utilisateur $enfant): int => (int) $enfant->getId(), $enfants);
        $resultats = $resultatRepository->findByEleveIds($eleveIds);
        $resultatsParEnfant = [];

        foreach ($enfants as $enfant) {
            $resultatsParEnfant[(int) $enfant->getId()] = [
                'enfant' => $enfant,
                'resultats' => [],
                'moyenne' => null,
            ];
        }

        foreach ($resultats as $resultat) {
            $enfant = $resultat->getEtudiant();
            if (!$enfant) {
                continue;
            }

            $enfantId = (int) $enfant->getId();
            if (!isset($resultatsParEnfant[$enfantId])) {
                $resultatsParEnfant[$enfantId] = [
                    'enfant' => $enfant,
                    'resultats' => [],
                    'moyenne' => null,
                ];
            }
            $resultatsParEnfant[$enfantId]['resultats'][] = $resultat;
        }

        foreach ($resultatsParEnfant as $enfantId => $pack) {
            $notes = array_map(
                static fn ($r): float => (float) $r->getNote(),
                $pack['resultats']
            );
            if ($notes !== []) {
                $resultatsParEnfant[$enfantId]['moyenne'] = array_sum($notes) / count($notes);
            }
        }

        return $this->render('parent/dashboard.html.twig', [
            'enfants' => $enfants,
            'resultatsParEnfant' => array_values($resultatsParEnfant),
        ]);
    }
}
