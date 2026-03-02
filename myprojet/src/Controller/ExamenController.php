<?php

namespace App\Controller;

use App\Entity\Examen;
use App\Form\ExamenType;
use App\Repository\ExamenRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ExamenQualityAnalyzerService;
use App\Service\EvaluationNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/examen')]
final class ExamenController extends AbstractController
{
    private const ALLOWED_FILE_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt'];

    #[Route('/', name: 'app_examen_index', methods: ['GET'])]
    public function index(ExamenRepository $examenRepository): Response
    {
        return $this->render('examen/index.html.twig', [
            'examens' => $examenRepository->findAllWithExistingRelations(),
        ]);
    }

    #[Route('/new', name: 'app_examen_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        UtilisateurRepository $utilisateurRepository,
        EvaluationNotificationService $notificationService
    ): Response
    {
        $examen = new Examen();
        $form = $this->createForm(ExamenType::class, $examen);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('contenuFile')->getData();
            if ($uploadedFile !== null) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $extension = $this->resolveUploadedExtension($uploadedFile);
                if (!$this->isAllowedUploadedExtension($extension)) {
                    $form->get('contenuFile')->addError(new FormError('Formats autorises: PDF, DOC, DOCX, TXT.'));

                    return $this->render('examen/new.html.twig', [
                        'examen' => $examen,
                        'form' => $form,
                    ]);
                }

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/examens';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $uploadedFile->move(
                    $uploadDir,
                    $newFilename
                );

                $examen->setContenu($newFilename);
            }

            $entityManager->persist($examen);
            $entityManager->flush();

            $phones = $utilisateurRepository->findPhonesByRoles(['ROLE_ETUDIANT', 'ROLE_STUDENT', 'ROLE_PARENT']);
            $notificationService->sendEvaluationNotification(
                $phones,
                sprintf('Nouvelle evaluation: %s', (string) $examen->getTitre()),
                sprintf(
                    "Une nouvelle evaluation a ete planifiee.\n\nTitre: %s\nType: %s\nDate: %s\nDuree: %d minutes\nCours: %s",
                    (string) $examen->getTitre(),
                    (string) $examen->getType(),
                    $examen->getDateExamen()?->format('d/m/Y') ?? 'N/A',
                    (int) ($examen->getDuree() ?? 0),
                    $examen->getCours()?->getTitre() ?? 'N/A'
                )
            );

            return $this->redirectToRoute('app_examen_index');
        }

        return $this->render('examen/new.html.twig', [
            'examen' => $examen,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_examen_show', methods: ['GET'])]
    public function show(Examen $examen): Response
    {
        return $this->render('examen/show.html.twig', [
            'examen' => $examen,
        ]);
    }

    #[Route('/{id}/ai-quality', name: 'app_examen_ai_quality', methods: ['POST'])]
    public function analyzeQuality(Examen $examen, ExamenQualityAnalyzerService $analyzer): JsonResponse
    {
        $result = $analyzer->analyze($examen);

        return $this->json([
            'analysis' => $result,
            'examen_id' => $examen->getId(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_examen_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Examen $examen, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ExamenType::class, $examen, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('contenuFile')->getData();
            if ($uploadedFile !== null) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $extension = $this->resolveUploadedExtension($uploadedFile);
                if (!$this->isAllowedUploadedExtension($extension)) {
                    $form->get('contenuFile')->addError(new FormError('Formats autorises: PDF, DOC, DOCX, TXT.'));

                    return $this->render('examen/edit.html.twig', [
                        'examen' => $examen,
                        'form' => $form,
                    ]);
                }

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/examens';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $uploadedFile->move(
                    $uploadDir,
                    $newFilename
                );

                $examen->setContenu($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_examen_index');
        }

        return $this->render('examen/edit.html.twig', [
            'examen' => $examen,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_examen_delete', methods: ['POST'])]
    public function delete(Request $request, Examen $examen, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $examen->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($examen);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_examen_index');
    }

    private function resolveUploadedExtension(UploadedFile $uploadedFile): string
    {
        $extension = strtolower((string) pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'bin';
    }

    private function isAllowedUploadedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_FILE_EXTENSIONS, true);
    }
}
