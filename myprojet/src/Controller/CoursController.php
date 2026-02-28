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
use App\Repository\StudentChapitreProgressRepository;
use App\Repository\StudentRepository;
use App\Repository\UtilisateurRepository;
use App\Service\CourseBadgeService;
use App\Service\ResumeGenerator;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/cours')]
final class CoursController extends AbstractController
{
    private ?bool $hasRessourceQuizTable = null;

    public function __construct(private readonly WeatherService $weatherService)
    {
    }

   #[Route('/cours', name: 'app_cours_index')]
public function index(
    Request $request,
    CoursRepository $coursRepo,
    StudentChapitreProgressRepository $progressRepository,
    CourseBadgeService $courseBadgeService
): Response
{
    $cours = $coursRepo->findAll();
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

    $translatedId = $request->query->get('trad');

    $titreTraduit = null;
    $descriptionTraduit = null;

    if ($translatedId) {
        $coursToTranslate = $coursRepo->find($translatedId);

        if ($coursToTranslate) {
            $tr = new GoogleTranslate('en');
            $tr->setSource('fr');

            $titreTraduit = $tr->translate($coursToTranslate->getTitre());
            $descriptionTraduit = $tr->translate($coursToTranslate->getDescription());
        }
    }

    return $this->render('cours/index.html.twig', [
        'cours' => $cours,
        'translatedId' => $translatedId,
        'titreTraduit' => $titreTraduit,
        'descriptionTraduit' => $descriptionTraduit,
        'startedByCours' => $startedByCours,
        'badgesByCours' => $badgesByCours,
        ...$this->buildTeacherLayoutData(),
    ]);
}
#[Route('/test-mail', name: 'test_mail', methods: ['GET'])]
public function testMail(MailerInterface $mailer)
{
    try {
        $email = (new Email())
            ->from('no-reply@eduflex.com')
            ->to('4ce5c3b8f2-0e207d+user1@inbox.mailtrap.io') // Ton inbox Mailtrap
            ->subject('TEST MAIL DEBUG')
            ->text('Hello Mailtrap !');

        $mailer->send($email);

        return new Response('Mail envoyé !');
    } catch (\Exception $e) {
        return new Response('Erreur mail : '.$e->getMessage());
    }
}
#[Route('/traduire/{id}', name: 'app_cours_traduire')]
public function traduire(Cours $cours, Request $request): Response
{
    $currentTrad = $request->query->get('trad');

    if ($currentTrad == $cours->getId()) {
        return $this->redirectToRoute('app_cours_index');
    }

    return $this->redirectToRoute('app_cours_index', [
        'trad' => $cours->getId()
    ]);
}

#[Route('/new', name: 'app_cours_new', methods: ['GET','POST'])]
public function new(
    Request $request,
    EntityManagerInterface $em,
    MailerInterface $mailer,
    StudentRepository $studentRepo,
    UtilisateurRepository $utilisateurRepository,
    ResumeGenerator $resumeGenerator
): Response {

    $cours = new Cours();
    $form = $this->createForm(CoursType::class, $cours);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 1️⃣ Enregistrer le cours
        $em->persist($cours);
        $em->flush();

        // 2️⃣ Générer un résumé automatique pour tous les chapitres existants (si déjà ajoutés)
        foreach ($cours->getChapitres() as $chapitre) {
            $resumeText = $resumeGenerator->generateAndSave($chapitre);
            $chapitre->setResume($resumeText);
        }
        $em->flush();

        // 3️⃣ Envoyer un mail à chaque étudiant (table student + utilisateur ROLE_ETUDIANT)
        $students = $studentRepo->findAll();
        $utilisateursEtudiants = $utilisateurRepository->findBy(['role' => 'ROLE_ETUDIANT']);
        $courseUrl = $this->generateUrl('eleve_cours_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $sentCount = 0;
        $alreadySentTo = [];

        foreach ($students as $student) {
            $studentEmail = $student->getEmail();
            if (!$studentEmail || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $normalizedEmail = mb_strtolower(trim($studentEmail));
            if (isset($alreadySentTo[$normalizedEmail])) {
                continue;
            }

            $email = (new Email())
                ->from('no-reply@eduflex.com')
                ->to($studentEmail)
                ->subject('Nouveau cours disponible : ' . $cours->getTitre())
                ->html(
                    '<p>Bonjour ' . htmlspecialchars((string) $student->getNom(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ',</p>'
                    . '<p>Un nouveau cours vient d\'etre ajoute : <strong>' . htmlspecialchars((string) $cours->getTitre(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>.</p>'
                    . '<p>Description : ' . htmlspecialchars((string) $cours->getDescription(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
                    . '<p>Accedez au cours ici : <a href="' . htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">Voir le cours</a></p>'
                    . '<br><p>EduFlex</p>'
                );

            try {
                $mailer->send($email);
                ++$sentCount;
                $alreadySentTo[$normalizedEmail] = true;
            } catch (TransportExceptionInterface) {
                // Continue pour ne pas bloquer la creation du cours sur un echec d'envoi.
            }
        }

        foreach ($utilisateursEtudiants as $etudiantUser) {
            $emailUser = $etudiantUser->getEmail();
            if (!$emailUser || !filter_var($emailUser, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $normalizedEmail = mb_strtolower(trim($emailUser));
            if (isset($alreadySentTo[$normalizedEmail])) {
                continue;
            }

            $email = (new Email())
                ->from('no-reply@eduflex.com')
                ->to($emailUser)
                ->subject('Nouveau cours disponible : ' . $cours->getTitre())
                ->html(
                    '<p>Bonjour ' . htmlspecialchars((string) $etudiantUser->getNom(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ',</p>'
                    . '<p>Un nouveau cours vient d\'etre ajoute : <strong>' . htmlspecialchars((string) $cours->getTitre(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>.</p>'
                    . '<p>Description : ' . htmlspecialchars((string) $cours->getDescription(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
                    . '<p>Accedez au cours ici : <a href="' . htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">Voir le cours</a></p>'
                    . '<br><p>EduFlex</p>'
                );

            try {
                $mailer->send($email);
                ++$sentCount;
                $alreadySentTo[$normalizedEmail] = true;
            } catch (TransportExceptionInterface) {
                // Continue pour ne pas bloquer la creation du cours sur un echec d'envoi.
            }
        }

        $this->addFlash('success', sprintf('Cours ajoute avec succes. %d notification(s) envoyee(s).', $sentCount));

        return $this->redirectToRoute('app_cours_index');
    }

    return $this->render('cours/new.html.twig', [
        'form' => $form->createView(),
        ...$this->buildTeacherLayoutData(),
    ]);
}

    #[Route('/cours/{id}', name: 'app_cours_show', methods: ['GET'])]
        public function show(Cours $cours): Response
    {
    return $this->render('cours/show.html.twig', [
        'cours' => $cours,
        ...$this->buildTeacherLayoutData(),
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

    #[Route('/{id}/delete', name: 'app_cours_delete', methods: ['POST'])]
    #[Route('/{id}', name: 'app_cours_delete_legacy', methods: ['POST'])]
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

    private function buildTeacherLayoutData(): array
    {
        return [
            'todayDate' => (new \DateTimeImmutable('today'))->format('d/m/Y'),
            'weather' => $this->weatherService->getTodayWeather(),
        ];
    }
}
