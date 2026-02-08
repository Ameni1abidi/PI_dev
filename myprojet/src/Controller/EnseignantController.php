<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EnseignantController extends AbstractController
{
    #[Route('/enseignant/dashboard', name: 'app_enseignant_dashboard', methods: ['GET'])]
    public function dashboard(CoursRepository $coursRepo): Response
    {
        $cours = $coursRepo->findAll(); // or filter by connected enseignant
        return $this->render('enseignant/dashboard.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/enseignant/evaluations', name: 'app_enseignant_evaluations', methods: ['GET'])]
    public function evaluations(): Response
    {
        return $this->redirectToRoute('app_resultat_index');
    }

    #[Route('/enseignant/classes', name: 'app_enseignant_classes', methods: ['GET'])]
    public function classes(): Response
    {
        $classes = [
            ['nom' => '3A', 'niveau' => 'College', 'eleves' => 28, 'prof' => 'Mme Legrand'],
            ['nom' => '2B', 'niveau' => 'College', 'eleves' => 26, 'prof' => 'Mme Legrand'],
            ['nom' => '1C', 'niveau' => 'College', 'eleves' => 24, 'prof' => 'Mme Legrand'],
        ];

        return $this->render('enseignant/classes.html.twig', [
            'classes' => $classes,
        ]);
    }

    #[Route('/enseignant/evaluations/planifiees', name: 'app_enseignant_evaluations_planifiees', methods: ['GET'])]
    public function evaluationsPlanifiees(): Response
    {
        $evaluations = [
            ['date' => '2026-02-10', 'matiere' => 'Maths', 'classe' => '3A', 'statut' => 'A venir'],
            ['date' => '2026-02-12', 'matiere' => 'Francais', 'classe' => '2B', 'statut' => 'A venir'],
            ['date' => '2026-02-14', 'matiere' => 'Sciences', 'classe' => '1C', 'statut' => 'A venir'],
        ];

        return $this->render('enseignant/evaluations_planifiees.html.twig', [
            'evaluations' => $evaluations,
        ]);
    }

    #[Route('/enseignant/suivi', name: 'app_enseignant_suivi', methods: ['GET'])]
    public function suivi(): Response
    {
        $suivi = [
            ['classe' => '3A', 'absences' => 2, 'notes' => 'En cours', 'progression' => '72%'],
            ['classe' => '2B', 'absences' => 1, 'notes' => 'Valide', 'progression' => '64%'],
            ['classe' => '1C', 'absences' => 3, 'notes' => 'A corriger', 'progression' => '58%'],
        ];

        return $this->render('enseignant/suivi.html.twig', [
            'suivi' => $suivi,
        ]);
    }

}
