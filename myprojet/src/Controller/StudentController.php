<?php

namespace App\Controller;

use App\Entity\DevoirIa;
use App\Entity\DevoirIaReponse;
use App\Entity\Examen;
use App\Entity\Utilisateur;
use App\Form\StudentProfileType;
use App\Repository\DevoirIaReponseRepository;
use App\Repository\DevoirIaRepository;
use App\Repository\ExamenRepository;
use App\Repository\ResultatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class StudentController extends AbstractController
{
    #[Route('/eleve/dashboard', name: 'app_student_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('student/dashboard.html.twig');
    }

    #[Route('/eleve/profil', name: 'app_student_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Authentification requise.');
        }

        $form = $this->createForm(StudentProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = trim((string) $form->get('currentPassword')->getData());
            $plainPassword = trim((string) $form->get('plainPassword')->getData());

            if ($plainPassword !== '') {
                if ($currentPassword === '' || !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError('Mot de passe actuel incorrect.'));
                } else {
                    $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                }
            }

            if ($form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Profil mis a jour avec succes.');

                return $this->redirectToRoute('app_student_profile_edit');
            }
        }

        return $this->render('student/profile_edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/eleve/examens', name: 'app_student_examens', methods: ['GET'])]
    public function examens(ExamenRepository $examenRepository): Response
    {
        return $this->render('student/examens.html.twig', [
            'examens' => $examenRepository->findBy([], ['dateExamen' => 'ASC', 'id' => 'ASC']),
        ]);
    }

    #[Route('/eleve/examens/{id}', name: 'app_student_examen_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showExamen(Examen $examen): Response
    {
        return $this->render('student/examen_show.html.twig', [
            'examen' => $examen,
        ]);
    }

    #[Route('/eleve/resultats', name: 'app_student_resultats', methods: ['GET'])]
    public function resultats(ResultatRepository $resultatRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Authentification requise.');
        }

        return $this->render('student/resultats.html.twig', [
            'resultats' => $resultatRepository->findBy(['etudiant' => $user], ['id' => 'DESC']),
        ]);
    }

    #[Route('/eleve/devoirs-ia', name: 'app_student_devoirs_ia', methods: ['GET'])]
    public function devoirsIa(DevoirIaRepository $devoirIaRepository): Response
    {
        return $this->render('student/devoirs_ia.html.twig', [
            'devoirs' => $devoirIaRepository->findPublishedForStudents(),
        ]);
    }

    #[Route('/eleve/devoirs-ia/{id}', name: 'app_student_devoir_ia_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showDevoirIa(
        DevoirIa $devoirIa,
        DevoirIaReponseRepository $devoirIaReponseRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Authentification requise.');
        }

        $payload = $devoirIa->getContenuArray();
        $questions = $payload['questions'] ?? [];
        if (!is_array($questions)) {
            $questions = [];
        }

        $attempts = $devoirIaReponseRepository->findByEleveAndDevoir($user, $devoirIa, 25);
        $latestAttempt = $attempts[0] ?? null;
        $evaluation = null;
        if ($latestAttempt instanceof DevoirIaReponse) {
            $evaluation = $this->evaluateSubmission($questions, $latestAttempt->getReponsesArray());
        }

        return $this->render('student/devoir_ia_show.html.twig', [
            'devoir' => $devoirIa,
            'payload' => $payload,
            'evaluation' => $evaluation,
            'attempts' => $attempts,
        ]);
    }

    #[Route('/eleve/devoirs-ia/{id}/tentative', name: 'app_student_devoir_ia_retry', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function retryDevoirIa(
        DevoirIa $devoirIa,
        Request $request,
        EntityManagerInterface $entityManager,
        DevoirIaReponseRepository $devoirIaReponseRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Authentification requise.');
        }

        $payload = $devoirIa->getContenuArray();
        $questions = $payload['questions'] ?? [];
        if (!is_array($questions)) {
            $questions = [];
        }

        $savedAnswers = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('submit_devoir_ia_' . $devoirIa->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $answersRaw = $request->request->all('answers');
            $answers = is_array($answersRaw) ? $this->normalizeAnswers($answersRaw) : [];
            $savedAnswers = $answers;

            $evaluation = $this->evaluateSubmission($questions, $answers);

            $submission = new DevoirIaReponse();
            $submission
                ->setDevoir($devoirIa)
                ->setEleve($user)
                ->setReponsesArray($answers)
                ->setNote(number_format($evaluation['note'], 2, '.', ''))
                ->setFeedback($evaluation['feedback'])
                ->setDateSoumission(new \DateTimeImmutable());

            $entityManager->persist($submission);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Devoir soumis. Note: %.2f / 20', $evaluation['note']));

            return $this->redirectToRoute('app_student_devoir_ia_result_show', [
                'id' => $devoirIa->getId(),
                'submissionId' => $submission->getId(),
            ]);
        }

        return $this->render('student/devoir_ia_retry.html.twig', [
            'devoir' => $devoirIa,
            'payload' => $payload,
            'saved_answers' => $savedAnswers,
            'attempts_count' => count($devoirIaReponseRepository->findByEleveAndDevoir($user, $devoirIa, 100)),
        ]);
    }

    #[Route('/eleve/devoirs-ia/{id}/resultat/{submissionId}', name: 'app_student_devoir_ia_result_show', requirements: ['id' => '\d+', 'submissionId' => '\d+'], methods: ['GET'])]
    public function showDevoirIaResult(
        DevoirIa $devoirIa,
        int $submissionId,
        DevoirIaReponseRepository $devoirIaReponseRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Authentification requise.');
        }

        $submission = $devoirIaReponseRepository->find($submissionId);
        if (!$submission instanceof DevoirIaReponse) {
            throw $this->createNotFoundException('Tentative introuvable.');
        }

        if ($submission->getEleve()?->getId() !== $user->getId() || $submission->getDevoir()?->getId() !== $devoirIa->getId()) {
            throw $this->createAccessDeniedException('Cette tentative ne vous appartient pas.');
        }

        $payload = $devoirIa->getContenuArray();
        $questions = $payload['questions'] ?? [];
        if (!is_array($questions)) {
            $questions = [];
        }

        $answers = $submission->getReponsesArray();
        $evaluation = $this->evaluateSubmission($questions, $answers);

        return $this->render('student/devoir_ia_result.html.twig', [
            'devoir' => $devoirIa,
            'payload' => $payload,
            'submission' => $submission,
            'answers' => $answers,
            'evaluation' => $evaluation,
        ]);
    }

    /**
     * @param array<string, mixed> $answers
     * @return array<string, string>
     */
    private function normalizeAnswers(array $answers): array
    {
        $normalized = [];
        foreach ($answers as $key => $value) {
            $normalized[(string) $key] = trim((string) $value);
        }

        return $normalized;
    }

    /**
     * @param array<int, mixed> $questions
     * @param array<string, string> $answers
     * @return array{note: float, feedback: string, details: array<int, array<string, mixed>>}
     */
    private function evaluateSubmission(array $questions, array $answers): array
    {
        $total = count($questions);
        if ($total === 0) {
            return [
                'note' => 0.0,
                'feedback' => 'Aucune question disponible.',
                'details' => [],
            ];
        }

        $score = 0.0;
        $details = [];

        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                continue;
            }

            $expected = trim((string) ($question['answer'] ?? ''));
            $type = strtolower(trim((string) ($question['type'] ?? '')));
            $userAnswer = trim((string) ($answers[(string) $index] ?? ''));
            $questionScore = 0.0;
            $explanation = trim((string) ($question['explanation'] ?? ''));

            if ($userAnswer !== '') {
                if (in_array($type, ['qcm', 'vrai_faux'], true)) {
                    $questionScore = $this->isSameAnswer($expected, $userAnswer) ? 1.0 : 0.0;
                } else {
                    $questionScore = $this->scoreShortAnswer($expected, $explanation, $userAnswer);
                }
            }

            $score += $questionScore;
            $details[] = [
                'index' => $index,
                'question' => (string) ($question['question'] ?? ''),
                'type' => $type,
                'expected' => $expected,
                'given' => $userAnswer,
                'score' => $questionScore,
                'max' => 1.0,
                'explanation' => $explanation,
            ];
        }

        $note = round(($score / $total) * 20, 2);
        $feedback = $note >= 16
            ? 'Excellent travail. Continuez.'
            : ($note >= 10 ? 'Bon effort. Revisez les questions incorrectes.' : 'Resultat insuffisant. Reprendre les chapitres avant un nouvel essai.');

        return [
            'note' => $note,
            'feedback' => $feedback,
            'details' => $details,
        ];
    }

    private function isSameAnswer(string $expected, string $given): bool
    {
        $normalize = static function (string $value): string {
            $value = mb_strtolower(trim($value));
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;
            $value = str_replace(['.', ')', ':'], '', $value);

            if (preg_match('/^[abcd]\b/u', $value, $matches) === 1) {
                return $matches[0];
            }

            return $value;
        };

        return $normalize($expected) === $normalize($given);
    }

    private function scoreShortAnswer(string $expected, string $explanation, string $given): float
    {
        $reference = trim($expected . ' ' . $explanation);
        if ($reference === '') {
            return 0.5;
        }

        $keywords = $this->extractKeywords($reference);
        if ($keywords === []) {
            return 0.5;
        }

        $givenNorm = mb_strtolower($given);
        $hits = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($givenNorm, $keyword)) {
                ++$hits;
            }
        }

        $ratio = $hits / count($keywords);

        return max(0.0, min(1.0, round($ratio, 2)));
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $value): array
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]/i', ' ', $value) ?? $value;
        $words = preg_split('/\s+/', $value) ?: [];

        $stopWords = ['dans', 'avec', 'pour', 'elle', 'cette', 'cours', 'chapitre', 'notion', 'vous', 'nous', 'leur', 'plus', 'moins', 'tres', 'mais', 'donc', 'comme', 'leurs', 'etre', 'avoir', 'les', 'des', 'une', 'est', 'sont', 'par', 'sur', 'que', 'qui', 'pas', 'non', 'oui', 'du', 'de', 'la', 'le', 'un'];
        $counter = [];

        foreach ($words as $word) {
            $word = trim((string) $word);
            if (mb_strlen($word) < 4 || in_array($word, $stopWords, true)) {
                continue;
            }
            $counter[$word] = ($counter[$word] ?? 0) + 1;
        }

        arsort($counter);

        return array_values(array_slice(array_keys($counter), 0, 8));
    }
}
