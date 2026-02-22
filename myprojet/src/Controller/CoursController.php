<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Chapitre;
use App\Entity\Utilisateur;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use App\Repository\RessourceFavoriRepository;
use App\Repository\RessourceLikeRepository;
use App\Repository\RessourceQuizRepository;
use App\Repository\RessourceRepository;
use App\Service\RessourceQuizGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cours')]
final class CoursController extends AbstractController
{
    private ?bool $hasRessourceQuizTable = null;

    #[Route('/cours', name: 'app_cours_index')]
    public function index(Request $request, CoursRepository $coursRepo): Response
    {
    
        $keyword = $request->query->get('search'); 

        if ($keyword) {
            $cours = $coursRepo->findByTitre($keyword);
        } else {
            $cours = $coursRepo->findAll();
        }

        return $this->render('cours/index.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/new', name: 'app_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cours();
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cour);
            $entityManager->flush();

            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/new.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_show', methods: ['GET'])]
        public function show(Cours $cours): Response
    {
    return $this->render('cours/show.html.twig', [
        'cours' => $cours,
    ]);
    }

    #[Route('/{id}/edit', name: 'app_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cours/edit.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cours_delete', methods: ['POST'])]
public function delete(Request $request, Cours $cour, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$cour->getId(), $request->request->get('_token'))) {
        $entityManager->remove($cour);
        $entityManager->flush();
        $this->addFlash('success', 'Cours supprimé avec succès !');
    }

    return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
}
 #[Route('/eleve/cours', name: 'eleve_cours_index')]
    public function indexEleve(Request $request, CoursRepository $coursRepo): Response
    {
        $keyword = $request->query->get('search');

        if ($keyword) {
            $cours = $coursRepo->findByTitre($keyword); // méthode custom dans ton repo
        } else {
            $cours = $coursRepo->findAll();
        }

        return $this->render('student/courstudent.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/eleve/cours/{id}', name: 'eleve_cours_show')]
    public function showChapitres(Cours $cours): Response
    {
        return $this->render('student/cours_show.html.twig', [
            'cours' => $cours,
            'chapitres' => $cours->getChapitres(),
        ]);
    }

    #[Route('/eleve/chapitre/{id}/ressources', name: 'eleve_chapitre_ressources', methods: ['GET'])]
    public function chapitreRessources(
        Request $request,
        Chapitre $chapitre,
        RessourceRepository $ressourceRepository,
        RessourceQuizRepository $ressourceQuizRepository,
        RessourceLikeRepository $ressourceLikeRepository,
        RessourceFavoriRepository $ressourceFavoriRepository,
        EntityManagerInterface $entityManager,
        RessourceQuizGeneratorService $quizGeneratorService
    ): Response
    {
        $chapitreId = (int) $chapitre->getId();
        $ressources = $ressourceRepository->findByChapitreId($chapitreId);
        $topRessources = $ressourceRepository->findTopByChapitreId($chapitreId, 3);
        $ressourceIds = array_values(array_filter(array_map(
            static fn ($ressource) => $ressource->getId(),
            $ressources
        )));
        $quizByRessource = $this->hasRessourceQuizTable($entityManager)
            ? $ressourceQuizRepository->findGroupedByRessourceIds($ressourceIds)
            : [];

        if ($this->hasRessourceQuizTable($entityManager)) {
            $generated = false;
            foreach ($ressources as $ressource) {
                $resourceId = (int) ($ressource->getId() ?? 0);
                if ($resourceId > 0 && !isset($quizByRessource[$resourceId])) {
                    $quizGeneratorService->regenerateForRessource($ressource);
                    $generated = true;
                }
            }

            if ($generated) {
                $quizByRessource = $ressourceQuizRepository->findGroupedByRessourceIds($ressourceIds);
            }
        } else {
            foreach ($ressources as $ressource) {
                $resourceId = (int) ($ressource->getId() ?? 0);
                if ($resourceId <= 0) {
                    continue;
                }
                $quizByRessource[$resourceId] = $quizGeneratorService->buildPreviewForRessource($ressource);
            }
        }

        $likedIds = [];
        $favoriIds = [];
        $user = $this->getUser();
        if ($user instanceof Utilisateur) {
            $likedIds = $ressourceLikeRepository->findLikedRessourceIdsByUtilisateurAndChapitre($user, $chapitreId);
            $favoriIds = $ressourceFavoriRepository->findFavoriRessourceIdsByUtilisateurAndChapitre($user, $chapitreId);
        }

        return $this->render('student/chapitre_ressources.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $chapitre->getCours(),
            'ressources' => $ressources,
            'top_ressources' => $topRessources,
            'liked_ids' => $likedIds,
            'favori_ids' => $favoriIds,
            'quiz_by_ressource' => $quizByRessource,
            'quiz_results' => (array) $request->getSession()->get('quiz_results', []),
        ]);
    }

    #[Route('/eleve/chapitre/{id}/ressources/{ressourceId}/quiz', name: 'eleve_ressource_quiz_submit', requirements: ['id' => '\d+', 'ressourceId' => '\d+'], methods: ['POST'])]
    public function submitRessourceQuiz(
        Request $request,
        Chapitre $chapitre,
        int $ressourceId,
        RessourceRepository $ressourceRepository,
        RessourceQuizRepository $ressourceQuizRepository,
        EntityManagerInterface $entityManager,
        RessourceQuizGeneratorService $quizGeneratorService
    ): Response {
        if (!$this->isCsrfTokenValid('quiz_submit_'.$ressourceId, (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Soumission du quiz invalide.');

            return $this->redirectToRoute('eleve_chapitre_ressources', ['id' => $chapitre->getId()]);
        }

        $ressource = $ressourceRepository->find($ressourceId);
        if ($ressource === null || (int) ($ressource->getChapitre()?->getId() ?? 0) !== (int) $chapitre->getId()) {
            $this->addFlash('error', 'Ressource introuvable pour ce chapitre.');

            return $this->redirectToRoute('eleve_chapitre_ressources', ['id' => $chapitre->getId()]);
        }

        $quizRows = [];
        if ($this->hasRessourceQuizTable($entityManager)) {
            $grouped = $ressourceQuizRepository->findGroupedByRessourceIds([$ressourceId]);
            $quizRows = $grouped[$ressourceId] ?? [];
            if ($quizRows === []) {
                $quizGeneratorService->regenerateForRessource($ressource);
                $grouped = $ressourceQuizRepository->findGroupedByRessourceIds([$ressourceId]);
                $quizRows = $grouped[$ressourceId] ?? [];
            }
        } else {
            $quizRows = $quizGeneratorService->buildPreviewForRessource($ressource);
        }

        if ($quizRows === []) {
            $this->addFlash('error', 'Quiz indisponible pour cette ressource.');

            return $this->redirectToRoute('eleve_chapitre_ressources', ['id' => $chapitre->getId()]);
        }

        $action = (string) $request->request->get('quiz_action', 'note');
        $allResults = (array) $request->getSession()->get('quiz_results', []);
        $storedResult = isset($allResults[$ressourceId]) && is_array($allResults[$ressourceId]) ? $allResults[$ressourceId] : null;

        if ($action === 'correction' && is_array($storedResult)) {
            $storedResult['show_correction'] = true;
            $allResults[$ressourceId] = $storedResult;
            $request->getSession()->set('quiz_results', $allResults);
            $this->addFlash('success', 'Correction IA affichee.');

            return $this->redirect($this->generateUrl('eleve_chapitre_ressources', ['id' => $chapitre->getId()]).'#ressource-'.$ressourceId);
        }

        $answers = (array) $request->request->all('quiz_answers');
        $result = $this->buildQuizResult($quizRows, $answers);
        $result['show_correction'] = false;

        $allResults[$ressourceId] = $result;
        $request->getSession()->set('quiz_results', $allResults);

        $this->addFlash('success', sprintf('Note calculee: %.2f/20', $result['note']));

        return $this->redirect($this->generateUrl('eleve_chapitre_ressources', ['id' => $chapitre->getId()]).'#ressource-'.$ressourceId);
    }

    private function hasRessourceQuizTable(EntityManagerInterface $entityManager): bool
    {
        if ($this->hasRessourceQuizTable !== null) {
            return $this->hasRessourceQuizTable;
        }

        try {
            $schemaManager = $entityManager->getConnection()->createSchemaManager();
            $this->hasRessourceQuizTable = $schemaManager->tablesExist(['ressource_quiz']);
        } catch (\Throwable) {
            $this->hasRessourceQuizTable = false;
        }

        return $this->hasRessourceQuizTable;
    }

    /**
     * @param array<int, array{type: string, question: string, choices: array<int, string>, answer_hint: ?string}> $quizRows
     * @param array<string, mixed> $answers
     *
     * @return array{
     *   score: int,
     *   total: int,
     *   note: float,
     *   corrections: array<int, array{question: string, type: string, user_answer: string, expected_answer: string, is_correct: bool}>,
     *   show_correction?: bool
     * }
     */
    private function buildQuizResult(array $quizRows, array $answers): array
    {
        $score = 0;
        $total = 0;
        $corrections = [];

        foreach ($quizRows as $index => $quiz) {
            $key = (string) $index;
            $type = (string) ($quiz['type'] ?? 'open');
            $question = (string) ($quiz['question'] ?? 'Question');
            $userAnswer = trim((string) ($answers[$key] ?? ''));
            $expected = '';
            $isCorrect = false;

            if ($type === 'mcq') {
                $choices = array_values(array_filter((array) ($quiz['choices'] ?? []), static fn (mixed $choice): bool => is_string($choice)));
                $expected = (string) ($choices[0] ?? '');
                $isCorrect = $userAnswer !== '' && mb_strtolower($userAnswer) === mb_strtolower($expected);
            } else {
                $expected = (string) ($quiz['answer_hint'] ?? 'Reponse ouverte personnelle attendue.');
                $isCorrect = $userAnswer !== '';
            }

            if ($isCorrect) {
                ++$score;
            }

            ++$total;
            $corrections[] = [
                'question' => $question,
                'type' => $type,
                'user_answer' => $userAnswer !== '' ? $userAnswer : 'Aucune reponse',
                'expected_answer' => $expected !== '' ? $expected : 'Non defini',
                'is_correct' => $isCorrect,
            ];
        }

        $note = $total > 0 ? round(($score / $total) * 20, 2) : 0.0;

        return [
            'score' => $score,
            'total' => $total,
            'note' => $note,
            'corrections' => $corrections,
        ];
    }
}
