<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\CoursRepository;
use App\Repository\ExamenRepository;
use App\Repository\ForumRepository;
use App\Repository\ResultatRepository;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EnseignantController extends AbstractController
{
    #[Route('/enseignant/dashboard', name: 'app_enseignant_dashboard', methods: ['GET'])]
public function dashboard(
        CoursRepository $coursRepo,
        ExamenRepository $examenRepo,
        ForumRepository $forumRepo,
        ResultatRepository $resultatRepo
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            $this->addFlash('error', 'Vous devez etre connecte pour acceder a cette page.');

            return $this->redirectToRoute('app_login');
        }

        $enseignantId = $user->getId();
        $cours = $coursRepo->findByEnseignant($enseignantId);

        $evaluations = $examenRepo->createQueryBuilder('e')
            ->leftJoin('e.cours', 'c')
            ->leftJoin('e.enseignant', 'ens')
            ->leftJoin('c.enseignant', 'cEns')
            ->andWhere('ens.id = :enseignantId OR cEns.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->orderBy('e.dateExamen', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();

$forums = $forumRepo->findBy([], ['dateCreation' => 'DESC']);
        $classesCount = count(array_unique(array_map(
            static fn ($cour) => (string) ($cour->getNiveau() ?? ''),
            $cours
        )));

        // Get recent results (last 5) - sorted by examen date
        $recentResults = $resultatRepo->findBy(
            [],
            [],
            5
        );

        // Build activity timeline from recent data
        $activities = [];
        
        // Add recent evaluations to activities
        foreach (array_slice($evaluations, 0, 3) as $evaluation) {
            $activities[] = [
                'type' => 'evaluation',
                'title' => $evaluation->getTitre(),
                'date' => $evaluation->getDateExamen(),
                'icon' => 'fa-clipboard',
                'color' => 'primary'
            ];
        }
        
        // Add recent forums to activities
        foreach (array_slice($forums, 0, 3) as $forum) {
            $activities[] = [
                'type' => 'forum',
                'title' => $forum->getTitre(),
                'date' => $forum->getDateCreation(),
                'icon' => 'fa-comments',
                'color' => 'success'
            ];
        }
        
        // Add recent results to activities
        foreach (array_slice($recentResults, 0, 3) as $resultat) {
            $activities[] = [
                'type' => 'resultat',
                'title' => 'Resultat: ' . ($resultat->getExamen() ? $resultat->getExamen()->getTitre() : 'N/A'),
                'date' => $resultat->getExamen() ? $resultat->getExamen()->getDateExamen() : null,
                'icon' => 'fa-chart-line',
                'color' => 'info'
            ];
        }
        
        // Sort activities by date descending
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        // Keep only top 6 activities
        $activities = array_slice($activities, 0, 6);

        return $this->render('enseignant/dashboard.html.twig', [
            'cours' => $cours,
            'evaluations' => $evaluations,
            'forums' => $forums,
            'classes_count' => $classesCount,
            'user' => $user,
            'recent_results' => $recentResults,
            'activities' => $activities,
            'stats' => [
                'total_cours' => count($cours),
                'total_evaluations' => count($evaluations),
                'total_forums' => count($forums),
                'classes_count' => $classesCount
            ]
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
