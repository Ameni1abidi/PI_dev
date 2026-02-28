<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Entity\Ressource;
use App\Repository\ChapitreRepository;
use App\Repository\RessourceInteractionRepository;
use App\Repository\RessourceRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ressource')]
final class RessourceInsightController extends AbstractController
{
    #[Route('/calendar', name: 'app_ressource_calendar', methods: ['GET'])]
    public function calendarView(Request $request, ChapitreRepository $chapitreRepository): Response
    {
        if (!$this->isGranted('ROLE_PROF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acces reserve aux enseignants.');
        }

        $chapitreId = $request->query->getInt('chapitre_id', 0);
        $chapitre = $chapitreId > 0 ? $chapitreRepository->find($chapitreId) : null;

        return $this->render('ressource/calendar.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $chapitre?->getCours(),
            'chapitre_id' => $chapitreId,
            'is_teacher' => $this->isGranted('ROLE_PROF') || $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/calendar/{id}/move', name: 'app_ressource_calendar_move', methods: ['POST'])]
    public function moveCalendarEvent(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_PROF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acces reserve aux enseignants.');
        }

        $payload = json_decode((string) $request->getContent(), true);
        $token = isset($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('move_ressource', $token)) {
            return $this->json(['success' => false, 'message' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $start = isset($payload['start']) ? (string) $payload['start'] : '';
        if ($start === '') {
            return $this->json(['success' => false, 'message' => 'Date de depart manquante.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ressource->setAvailableAt(new \DateTimeImmutable($start));
            $entityManager->flush();
        } catch (\Throwable) {
            return $this->json(['success' => false, 'message' => 'Date invalide.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'id' => $ressource->getId(),
            'availableAt' => $ressource->getAvailableAt()?->format(DATE_ATOM),
        ]);
    }

    #[Route('/dashboard', name: 'app_ressource_dashboard', methods: ['GET'])]
    public function dashboard(
        Request $request,
        ChapitreRepository $chapitreRepository,
        RessourceRepository $ressourceRepository,
        RessourceInteractionRepository $interactionRepository
    ): Response {
        if (!$this->isGranted('ROLE_PROF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acces reserve aux enseignants.');
        }

        $chapitreId = $request->query->getInt('chapitre_id', 0);
        $chapitre = $chapitreId > 0 ? $chapitreRepository->find($chapitreId) : null;

        if ($chapitre instanceof Chapitre) {
            $metrics = $ressourceRepository->getMetricsByRessourceForChapitre($chapitreId);
            $categoryDistribution = $ressourceRepository->getCategoryDistributionForChapitre($chapitreId);
            $dailyInteractions = $interactionRepository->aggregateDailyByTypeForChapitre(
                $chapitreId,
                new \DateTimeImmutable('-30 days')
            );
        } else {
            $metrics = $this->buildMetricsAll($ressourceRepository);
            $categoryDistribution = $this->buildCategoryDistributionAll($metrics);
            $dailyInteractions = [];
        }

        $charts = $this->buildCharts($metrics, $categoryDistribution, $dailyInteractions);
        $chartPayload = $this->buildChartPayload($metrics, $categoryDistribution, $dailyInteractions);
        $summary = $this->buildSummary($metrics);

        return $this->render('ressource/dashboard.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $chapitre?->getCours(),
            'chapitre_id' => $chapitreId,
            'summary' => $summary,
            'top_ressources' => array_slice($metrics, 0, 3),
            'engagement_chart' => $charts['engagement'],
            'ranking_chart' => $charts['ranking'],
            'category_chart' => $charts['category'],
            'timeline_chart' => $charts['timeline'],
            'chart_payload' => $chartPayload,
        ]);
    }

    #[Route('/dashboard-data', name: 'app_ressource_dashboard_data', methods: ['GET'])]
    public function dashboardData(
        Request $request,
        ChapitreRepository $chapitreRepository,
        RessourceRepository $ressourceRepository,
        RessourceInteractionRepository $interactionRepository
    ): JsonResponse {
        if (!$this->isGranted('ROLE_PROF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acces reserve aux enseignants.');
        }

        $chapitreId = $request->query->getInt('chapitre_id', 0);
        $chapitre = $chapitreId > 0 ? $chapitreRepository->find($chapitreId) : null;
        if ($chapitre instanceof Chapitre) {
            $metrics = $ressourceRepository->getMetricsByRessourceForChapitre($chapitreId);
            $dailyInteractions = $interactionRepository->aggregateDailyByTypeForChapitre(
                $chapitreId,
                new \DateTimeImmutable('-30 days')
            );
        } else {
            $metrics = $this->buildMetricsAll($ressourceRepository);
            $dailyInteractions = [];
        }

        return $this->json([
            'summary' => $this->buildSummary($metrics),
            'topRessources' => array_slice($metrics, 0, 3),
            'timeline' => $dailyInteractions,
            'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    /**
     * @return array<int, array{titre: string, vues: int, likes: int, favoris: int, score: int, categorie: string}>
     */
    private function buildMetricsAll(RessourceRepository $ressourceRepository): array
    {
        $rows = [];
        foreach ($ressourceRepository->findAllByScoreDesc() as $ressource) {
            $rows[] = [
                'titre' => (string) $ressource->getTitre(),
                'vues' => (int) $ressource->getNbVues(),
                'likes' => (int) $ressource->getNbLikes(),
                'favoris' => (int) $ressource->getNbFavoris(),
                'score' => (int) $ressource->getScore(),
                'categorie' => (string) ($ressource->getCategorie()?->getNom() ?? 'Sans categorie'),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array{titre: string, vues: int, likes: int, favoris: int, score: int, categorie: string}> $metrics
     *
     * @return array<int, array{categorie: string, total: int}>
     */
    private function buildCategoryDistributionAll(array $metrics): array
    {
        $counts = [];
        foreach ($metrics as $row) {
            $key = $row['categorie'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $result = [];
        foreach ($counts as $categorie => $total) {
            $result[] = ['categorie' => (string) $categorie, 'total' => (int) $total];
        }

        return $result;
    }

    /**
     * @param array<int, array{titre: string, vues: int, likes: int, favoris: int, score: int, categorie: string}> $metrics
     * @param array<int, array{categorie: string, total: int}> $categoryDistribution
     * @param array<int, array{day: string, type: string, total: int}> $dailyInteractions
     *
     * @return array<string, object>
     */
    private function buildCharts(array $metrics, array $categoryDistribution, array $dailyInteractions): array
    {
        $engagementChart = new ColumnChart();
        $engagementData = [['Ressource', 'Vues', 'Likes', 'Favoris']];
        foreach ($metrics as $row) {
            $engagementData[] = [$row['titre'], $row['vues'], $row['likes'], $row['favoris']];
        }
        $engagementChart->getData()->setArrayToDataTable($engagementData);
        $engagementChart->getOptions()->setTitle('Engagement par ressource')->setHeight(380);

        $rankingChart = new ColumnChart();
        $rankingData = [['Ressource', 'Score']];
        foreach (array_slice($metrics, 0, 10) as $row) {
            $rankingData[] = [$row['titre'], $row['score']];
        }
        $rankingChart->getData()->setArrayToDataTable($rankingData);
        $rankingChart->getOptions()->setTitle('Top ressources par score')->setHeight(380);

        $categoryChart = new PieChart();
        $categoryData = [['Categorie', 'Total']];
        foreach ($categoryDistribution as $row) {
            $categoryData[] = [$row['categorie'], $row['total']];
        }
        $categoryChart->getData()->setArrayToDataTable($categoryData);
        $categoryChart->getOptions()->setTitle('Repartition des ressources par categorie')->setHeight(380);

        $timelineChart = new LineChart();
        $timelineData = [['Date', 'Vues', 'Likes', 'Favoris']];
        $dailyMap = [];
        foreach ($dailyInteractions as $row) {
            $day = $row['day'];
            $dailyMap[$day] ??= ['view' => 0, 'like' => 0, 'favori' => 0];
            if ($row['type'] === 'view' || $row['type'] === 'like' || $row['type'] === 'favori') {
                $dailyMap[$day][$row['type']] = $row['total'];
            }
        }
        ksort($dailyMap);
        foreach ($dailyMap as $day => $values) {
            $timelineData[] = [$day, $values['view'], $values['like'], $values['favori']];
        }
        if (\count($timelineData) === 1) {
            $timelineData[] = [(new \DateTimeImmutable('today'))->format('Y-m-d'), 0, 0, 0];
        }
        $timelineChart->getData()->setArrayToDataTable($timelineData);
        $timelineChart->getOptions()->setTitle('Evolution des interactions (30 jours)')->setHeight(380);

        return [
            'engagement' => $engagementChart,
            'ranking' => $rankingChart,
            'category' => $categoryChart,
            'timeline' => $timelineChart,
        ];
    }

    /**
     * @param array<int, array{titre: string, vues: int, likes: int, favoris: int, score: int, categorie: string}> $metrics
     *
     * @return array{totalVues: int, totalLikes: int, totalFavoris: int, scoreMoyen: float}
     */
    private function buildSummary(array $metrics): array
    {
        $totalVues = 0;
        $totalLikes = 0;
        $totalFavoris = 0;
        $totalScore = 0;

        foreach ($metrics as $row) {
            $totalVues += $row['vues'];
            $totalLikes += $row['likes'];
            $totalFavoris += $row['favoris'];
            $totalScore += $row['score'];
        }

        return [
            'totalVues' => $totalVues,
            'totalLikes' => $totalLikes,
            'totalFavoris' => $totalFavoris,
            'scoreMoyen' => \count($metrics) > 0 ? round($totalScore / \count($metrics), 2) : 0.0,
        ];
    }

    /**
     * @param array<int, array{titre: string, vues: int, likes: int, favoris: int, score: int, categorie: string}> $metrics
     * @param array<int, array{categorie: string, total: int}> $categoryDistribution
     * @param array<int, array{day: string, type: string, total: int}> $dailyInteractions
     *
     * @return array{
     *   engagement: array<int, array<int|string>>,
     *   ranking: array<int, array<int|string>>,
     *   category: array<int, array<int|string>>,
     *   timeline: array<int, array<int|string>>
     * }
     */
    private function buildChartPayload(array $metrics, array $categoryDistribution, array $dailyInteractions): array
    {
        $engagement = [['Ressource', 'Vues', 'Likes', 'Favoris']];
        foreach ($metrics as $row) {
            $engagement[] = [$row['titre'], $row['vues'], $row['likes'], $row['favoris']];
        }
        if (\count($engagement) === 1) {
            $engagement[] = ['Aucune ressource', 0, 0, 0];
        }

        $ranking = [['Ressource', 'Score']];
        foreach (array_slice($metrics, 0, 10) as $row) {
            $ranking[] = [$row['titre'], $row['score']];
        }
        if (\count($ranking) === 1) {
            $ranking[] = ['Aucune ressource', 0];
        }

        $category = [['Categorie', 'Total']];
        $categoryTotals = [];
        foreach ($categoryDistribution as $row) {
            $categoryTotals[(string) $row['categorie']] = (int) $row['total'];
        }
        if ($categoryTotals === [] && $metrics !== []) {
            foreach ($metrics as $row) {
                $key = (string) $row['categorie'];
                $categoryTotals[$key] = ($categoryTotals[$key] ?? 0) + 1;
            }
        }
        foreach ($categoryTotals as $label => $total) {
            $category[] = [$label, $total];
        }
        $categorySum = 0;
        for ($i = 1; $i < \count($category); ++$i) {
            $categorySum += (int) $category[$i][1];
        }
        if (\count($category) === 1 || $categorySum <= 0) {
            $category = [['Categorie', 'Total'], ['Aucune donnee', 1]];
        }

        $timeline = [['Date', 'Vues', 'Likes', 'Favoris']];
        $dailyMap = [];
        foreach ($dailyInteractions as $row) {
            $day = $row['day'];
            $dailyMap[$day] ??= ['view' => 0, 'like' => 0, 'favori' => 0];
            if ($row['type'] === 'view' || $row['type'] === 'like' || $row['type'] === 'favori') {
                $dailyMap[$day][$row['type']] = $row['total'];
            }
        }
        ksort($dailyMap);
        foreach ($dailyMap as $day => $values) {
            $timeline[] = [$day, $values['view'], $values['like'], $values['favori']];
        }
        if (\count($timeline) === 1) {
            $timeline[] = [(new \DateTimeImmutable('today'))->format('Y-m-d'), 0, 0, 0];
        }

        return [
            'engagement' => $engagement,
            'ranking' => $ranking,
            'category' => $category,
            'timeline' => $timeline,
        ];
    }
}
