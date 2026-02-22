<?php

namespace App\Controller;

use App\Repository\CommentaireRepository;
use App\Repository\CoursRepository;
use App\Repository\ForumRepository;
use App\Repository\UtilisateurRepository;
use App\Service\AdminCopilotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        CoursRepository $coursRepository,
        ForumRepository $forumRepository,
        CommentaireRepository $commentaireRepository,
        AdminCopilotService $adminCopilotService
    ): Response
    {
        $now = new \DateTimeImmutable();
        $startOfMonth = $now->modify('first day of this month')->setTime(0, 0);
        $startOfWeek = $now->modify('monday this week')->setTime(0, 0);
        $startOfPreviousWeek = $startOfWeek->modify('-7 days');

        $totalUsers = $utilisateurRepository->count([]);
        $totalCours = $coursRepository->count([]);
        $totalForums = $forumRepository->count([]);
        $totalCommentaires = $commentaireRepository->count([]);

        $newCoursThisMonth = (int) $coursRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.dateCreation >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $forumsThisWeek = (int) $forumRepository->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.dateCreation >= :start')
            ->setParameter('start', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult();

        $commentsThisWeek = (int) $commentaireRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.dateEnvoi >= :start')
            ->setParameter('start', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult();

        $commentsPreviousWeek = (int) $commentaireRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.dateEnvoi >= :start AND c.dateEnvoi < :end')
            ->setParameter('start', $startOfPreviousWeek)
            ->setParameter('end', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult();

        $messagesChangePercent = $this->computePercentChange($commentsThisWeek, $commentsPreviousWeek);

        $usersByRole = [
            'etudiants' => (int) $utilisateurRepository->count(['role' => 'ROLE_ETUDIANT']),
            'enseignants' => (int) $utilisateurRepository->count(['role' => 'ROLE_PROF']),
            'parents' => (int) $utilisateurRepository->count(['role' => 'ROLE_PARENT']),
            'admins' => (int) $utilisateurRepository->count(['role' => 'ROLE_ADMIN']),
        ];

        $recentActivities = [];
        foreach ($forumRepository->findBy([], ['dateCreation' => 'DESC'], 4) as $forum) {
            $recentActivities[] = [
                'title' => (string) $forum->getTitre(),
                'meta' => 'Nouveau sujet forum',
                'date' => $forum->getDateCreation(),
            ];
        }
        foreach ($commentaireRepository->findBy([], ['dateEnvoi' => 'DESC'], 4) as $commentaire) {
            $recentActivities[] = [
                'title' => 'Commentaire #'.$commentaire->getId(),
                'meta' => 'Nouveau message',
                'date' => $commentaire->getDateEnvoi(),
            ];
        }
        usort($recentActivities, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);
        $recentActivities = array_slice($recentActivities, 0, 3);

        $popularClasses = [];
        foreach ($coursRepository->findBy([], ['id' => 'DESC'], 4) as $cours) {
            $popularClasses[] = [
                'code' => strtoupper(substr((string) $cours->getNiveau(), 0, 3)),
                'title' => (string) $cours->getTitre(),
                'meta' => count($cours->getChapitres()).' chapitres',
            ];
        }

        $user = $this->getUser();
        $adminInitials = 'AD';
        if ($user && method_exists($user, 'getNom')) {
            $parts = preg_split('/\s+/', trim((string) $user->getNom())) ?: [];
            $initials = '';
            foreach (array_slice($parts, 0, 2) as $part) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
            $adminInitials = $initials !== '' ? $initials : 'AD';
        }

        $copilotQuestion = '';
        $copilotResult = null;

        if ($request->isMethod('POST')) {
            $submittedToken = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('admin_copilot', $submittedToken)) {
                $this->addFlash('error', 'Session invalide. Veuillez reessayer.');
            } else {
                $copilotQuestion = trim((string) $request->request->get('question', ''));
                if ($copilotQuestion === '') {
                    $this->addFlash('error', 'Veuillez saisir une question pour le copilote.');
                } else {
                    $copilotResult = $adminCopilotService->answer($copilotQuestion);
                }
            }
        }

        return $this->render('admin/index.html.twig', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_classes' => $totalCours,
                'total_activities' => $totalForums,
                'total_messages' => $totalCommentaires,
                'new_classes_month' => $newCoursThisMonth,
                'activities_week' => $forumsThisWeek,
                'messages_change_percent' => $messagesChangePercent,
                'verified_users' => (int) $utilisateurRepository->count(['isVerified' => true]),
            ],
            'users_by_role' => $usersByRole,
            'recent_activities' => $recentActivities,
            'popular_classes' => $popularClasses,
            'admin_initials' => $adminInitials,
            'copilot_question' => $copilotQuestion,
            'copilot_result' => $copilotResult,
        ]);
    }

    private function computePercentChange(int $current, int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}

