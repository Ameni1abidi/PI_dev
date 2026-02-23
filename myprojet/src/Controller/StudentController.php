<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Entity\Cours;
use App\Entity\StudentChapitreProgress;
use App\Entity\Utilisateur;
use App\Repository\StudentChapitreProgressRepository;
use App\Repository\CoursRepository;
use App\Service\ChapitreChatService;
use App\Service\CourseBadgeService;
use App\Service\ResumeGenerator;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StudentController extends AbstractController
{
    #[Route('/mes-cours', name: 'student_cours_index', methods: ['GET'])]
    public function index(
        CoursRepository $coursRepo,
        WeatherService $weatherService,
        StudentChapitreProgressRepository $progressRepository,
        CourseBadgeService $courseBadgeService
    ): Response
    {
        $user = $this->getUser();
        $etudiant = $user instanceof Utilisateur ? $user : null;
        $cours = $coursRepo->findAll();
        $progressMap = $etudiant ? $progressRepository->findMapByUtilisateur($etudiant) : [];
        $coursIds = array_map(static fn (Cours $c) => (int) $c->getId(), $cours);
        $startedByCours = $progressRepository->countStartedStudentsByCoursIds($coursIds);
        $now = new \DateTimeImmutable();
        $startedLast7 = $progressRepository->countStartedStudentsByCoursIdsBetween(
            $coursIds,
            $now->sub(new \DateInterval('P7D')),
            $now
        );
        $startedPrev7 = $progressRepository->countStartedStudentsByCoursIdsBetween(
            $coursIds,
            $now->sub(new \DateInterval('P14D')),
            $now->sub(new \DateInterval('P7D'))
        );
        $badgesByCours = $courseBadgeService->buildBadgesForCourses(
            $cours,
            $startedByCours,
            $startedLast7,
            $startedPrev7,
            $now
        );

        $courseProgress = [];
        $chapterUi = [];
        $certificateAccess = [];

        foreach ($cours as $coursItem) {
            $chapitres = $coursItem->getChapitres()->toArray();
            usort($chapitres, static fn (Chapitre $a, Chapitre $b) => ($a->getOrdre() ?? 0) <=> ($b->getOrdre() ?? 0));

            $completedCount = 0;
            $previousCompleted = true;
            $previousTitle = null;
            $requiredMinutes = 0;
            $spentMinutesTotal = 0.0;

            foreach ($chapitres as $chapitre) {
                $progress = $progressMap[$chapitre->getId()] ?? null;
                $isCompleted = $progress?->isCompleted() ?? false;
                if ($isCompleted) {
                    ++$completedCount;
                }

                $unlocked = $previousCompleted;
                $spentMinutes = $progress ? round($progress->getTimeSpentSeconds() / 60, 1) : 0.0;
                $estimated = (int) ($chapitre->getDureeEstimee() ?? 0);
                $requiredMinutes += $estimated;
                $spentMinutesTotal += $spentMinutes;

                $chapterUi[$chapitre->getId()] = [
                    'completed' => $isCompleted,
                    'unlocked' => $unlocked,
                    'blockedMessage' => $unlocked ? null : sprintf('Terminez "%s" d\'abord.', (string) ($previousTitle ?? 'le chapitre precedent')),
                    'spentMinutes' => $spentMinutes,
                    'timeBadge' => $this->resolveTimeBadge($estimated, $spentMinutes, $isCompleted),
                ];

                $previousCompleted = $isCompleted;
                $previousTitle = $chapitre->getTitre();
            }

            $total = count($chapitres);
            $percent = $total > 0 ? (int) round(($completedCount / $total) * 100) : 0;
            $courseProgress[$coursItem->getId()] = [
                'completed' => $completedCount,
                'total' => $total,
                'percent' => $percent,
            ];

            $remaining = max(0.0, round($requiredMinutes - $spentMinutesTotal, 1));
            $remainingChapitres = max(0, $total - $completedCount);
            $allChapitresCompleted = $remainingChapitres === 0;
            $certificateAccess[$coursItem->getId()] = [
                'eligible' => $allChapitresCompleted && $spentMinutesTotal >= $requiredMinutes,
                'spentMinutes' => round($spentMinutesTotal, 1),
                'requiredMinutes' => $requiredMinutes,
                'remainingMinutes' => $remaining,
                'remainingChapitres' => $remainingChapitres,
            ];
        }

        return $this->render('student/courstudent.html.twig', [
            'cours' => $cours,
            'courseProgress' => $courseProgress,
            'chapterUi' => $chapterUi,
            'certificateAccess' => $certificateAccess,
            'badgesByCours' => $badgesByCours,
            ...$this->buildStudentLayoutData($weatherService),
        ]);
    }

    #[Route('/chapitre/{id}', name: 'student_chapitre_show', methods: ['GET'])]
    public function showChapitre(
        Chapitre $chapitre,
        ResumeGenerator $resumeGenerator,
        EntityManagerInterface $em,
        Request $request,
        WeatherService $weatherService,
        StudentChapitreProgressRepository $progressRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $blockingChapitre = $this->findBlockingPrerequisite($chapitre, $progressRepository, $user);
        if ($blockingChapitre !== null) {
            $this->addFlash('warning', sprintf('Terminez "%s" d\'abord.', (string) $blockingChapitre->getTitre()));
            return $this->redirectToRoute('student_cours_index');
        }

        $progress = $progressRepository->findOrCreate($user, $chapitre);
        $this->trackActiveTime($progress);

        $resume = $resumeGenerator->generateAndSave($chapitre);
        $chapitre->setResume($resume);
        $em->flush();

        $spentMinutes = round($progress->getTimeSpentSeconds() / 60, 1);
        $chatMessages = $request->getSession()->get($this->chapterChatSessionKey((int) $chapitre->getId()), []);

        return $this->render('student/chapitre_show.html.twig', [
            'chapitre' => $chapitre,
            'resume' => $resume,
            'chatMessages' => $chatMessages,
            'hideWeather' => true,
            'chapterProgress' => [
                'completed' => $progress->isCompleted(),
                'spentMinutes' => $spentMinutes,
                'estimatedMinutes' => (int) ($chapitre->getDureeEstimee() ?? 0),
                'timeBadge' => $this->resolveTimeBadge((int) ($chapitre->getDureeEstimee() ?? 0), $spentMinutes, $progress->isCompleted()),
            ],
            ...$this->buildStudentLayoutData($weatherService),
        ]);
    }

    #[Route('/chapitre/{id}/terminer', name: 'student_chapitre_complete', methods: ['POST'])]
    public function completeChapitre(
        Chapitre $chapitre,
        Request $request,
        EntityManagerInterface $em,
        StudentChapitreProgressRepository $progressRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        if (!$this->isCsrfTokenValid('complete_chapitre_' . $chapitre->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('student_chapitre_show', ['id' => $chapitre->getId()]);
        }

        $blockingChapitre = $this->findBlockingPrerequisite($chapitre, $progressRepository, $user);
        if ($blockingChapitre !== null) {
            $this->addFlash('warning', sprintf('Terminez "%s" d\'abord.', (string) $blockingChapitre->getTitre()));
            return $this->redirectToRoute('student_cours_index');
        }

        $progress = $progressRepository->findOrCreate($user, $chapitre);
        $this->trackActiveTime($progress);
        if (!$progress->isCompleted()) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        $em->flush();
        $this->addFlash('success', 'Chapitre marque comme termine.');

        return $this->redirectToRoute('student_chapitre_show', ['id' => $chapitre->getId()]);
    }

    #[Route('/chapitre/{id}/chat', name: 'student_chapitre_chat', methods: ['POST'])]
    public function askChapterQuestion(
        Chapitre $chapitre,
        Request $request,
        ChapitreChatService $chatService,
        StudentChapitreProgressRepository $progressRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        if (!$this->isCsrfTokenValid('chat_chapitre_' . $chapitre->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('student_chapitre_show', ['id' => $chapitre->getId()]);
        }

        $blockingChapitre = $this->findBlockingPrerequisite($chapitre, $progressRepository, $user);
        if ($blockingChapitre !== null) {
            $this->addFlash('warning', sprintf('Terminez "%s" d\'abord.', (string) $blockingChapitre->getTitre()));
            return $this->redirectToRoute('student_cours_index');
        }

        $question = trim((string) $request->request->get('question', ''));
        if ($question === '') {
            $this->addFlash('warning', 'Veuillez saisir une question.');
            return $this->redirectToRoute('student_chapitre_show', ['id' => $chapitre->getId()]);
        }

        $answer = $chatService->ask($chapitre, $question);

        $session = $request->getSession();
        $key = $this->chapterChatSessionKey((int) $chapitre->getId());
        $history = $session->get($key, []);
        $history[] = [
            'question' => $question,
            'answer' => $answer,
            'askedAt' => (new \DateTimeImmutable())->format('H:i'),
        ];
        $history = array_slice($history, -8);
        $session->set($key, $history);

        return $this->redirectToRoute('student_chapitre_show', ['id' => $chapitre->getId()]);
    }

    #[Route('/student/dashboard', name: 'app_student_dashboard')]
    public function dashboard(WeatherService $weatherService): Response
    {
        return $this->render('student/dashboard.html.twig', [
            ...$this->buildStudentLayoutData($weatherService),
        ]);
    }

    #[Route('/mes-cours/{id}/certificat', name: 'student_cours_certificate_model', methods: ['GET'])]
    public function certificateModel(
        Cours $cours,
        WeatherService $weatherService,
        StudentChapitreProgressRepository $progressRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $progressMap = $progressRepository->findMapByUtilisateur($user);
        $requiredMinutes = 0;
        $spentMinutes = 0.0;
        $missingChapitres = [];
        $completedCount = 0;
        $totalChapitres = 0;

        foreach ($cours->getChapitres() as $chapitre) {
            ++$totalChapitres;
            $estimated = (int) ($chapitre->getDureeEstimee() ?? 0);
            $requiredMinutes += $estimated;

            $progress = $progressMap[$chapitre->getId()] ?? null;
            $chapterSpent = $progress ? round($progress->getTimeSpentSeconds() / 60, 1) : 0.0;
            $spentMinutes += $chapterSpent;
            $isCompleted = $progress?->isCompleted() ?? false;
            if ($isCompleted) {
                ++$completedCount;
            }

            if ($chapterSpent < $estimated) {
                $missingChapitres[] = [
                    'titre' => (string) $chapitre->getTitre(),
                    'remaining' => round(max(0, $estimated - $chapterSpent), 1),
                ];
            }
        }

        if ($completedCount < $totalChapitres) {
            $remaining = $totalChapitres - $completedCount;
            $this->addFlash('error', sprintf(
                'Certificat bloque: terminez tous les chapitres (%d restant(s)).',
                $remaining
            ));

            return $this->redirectToRoute('student_cours_index');
        }

        $eligible = $spentMinutes >= $requiredMinutes;
        if (!$eligible) {
            $remaining = round(max(0, $requiredMinutes - $spentMinutes), 1);
            $this->addFlash('error', sprintf(
                'Certificat bloque: temps insuffisant. Il vous reste %s min a terminer.',
                $remaining
            ));

            return $this->redirectToRoute('student_cours_index');
        }

        return $this->render('student/certificate_model.html.twig', [
            'cours' => $cours,
            'etudiant' => $user,
            'eligible' => $eligible,
            'requiredMinutes' => $requiredMinutes,
            'spentMinutes' => round($spentMinutes, 1),
            'missingChapitres' => $missingChapitres,
            ...$this->buildStudentLayoutData($weatherService),
        ]);
    }

    private function buildStudentLayoutData(WeatherService $weatherService): array
    {
        return [
            'todayDate' => (new \DateTimeImmutable('today'))->format('d/m/Y'),
            'weather' => $weatherService->getTodayWeather(),
        ];
    }

    private function trackActiveTime(StudentChapitreProgress $progress): void
    {
        $now = new \DateTimeImmutable();
        $lastViewedAt = $progress->getLastViewedAt();

        if ($lastViewedAt !== null && !$progress->isCompleted()) {
            $delta = max(0, $now->getTimestamp() - $lastViewedAt->getTimestamp());
            $progress->setTimeSpentSeconds($progress->getTimeSpentSeconds() + $delta);
        } elseif ($progress->getStartedAt() === null) {
            $progress->setStartedAt($now);
        }

        $progress->setLastViewedAt($now);
    }

    private function findBlockingPrerequisite(
        Chapitre $chapitre,
        StudentChapitreProgressRepository $progressRepository,
        Utilisateur $etudiant
    ): ?Chapitre {
        $cours = $chapitre->getCours();
        if ($cours === null) {
            return null;
        }

        $previous = null;
        foreach ($cours->getChapitres() as $candidate) {
            if ($candidate->getId() === $chapitre->getId()) {
                continue;
            }

            if (($candidate->getOrdre() ?? 0) < ($chapitre->getOrdre() ?? 0)) {
                if ($previous === null || ($candidate->getOrdre() ?? 0) > ($previous->getOrdre() ?? 0)) {
                    $previous = $candidate;
                }
            }
        }

        if ($previous === null) {
            return null;
        }

        $previousProgress = $progressRepository->findOneByUtilisateurAndChapitre($etudiant, $previous);
        if ($previousProgress === null || !$previousProgress->isCompleted()) {
            return $previous;
        }

        return null;
    }

    private function resolveTimeBadge(int $estimatedMinutes, float $spentMinutes, bool $isCompleted): string
    {
        if (!$isCompleted) {
            return 'En cours';
        }

        if ($estimatedMinutes <= 0) {
            return 'Normal';
        }

        $ratio = $spentMinutes / $estimatedMinutes;
        if ($ratio <= 0.80) {
            return 'Rapide';
        }
        if ($ratio <= 1.25) {
            return 'Normal';
        }

        return 'A revoir';
    }

    private function chapterChatSessionKey(int $chapitreId): string
    {
        return 'chapter_chat_' . $chapitreId;
    }
}
