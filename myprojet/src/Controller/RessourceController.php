<?php

namespace App\Controller;

use App\Entity\RessourceFavori;
use App\Entity\RessourceInteraction;
use App\Entity\RessourceLike;
use App\Entity\Ressource;
use App\Entity\Utilisateur;
use App\Form\RessourceType;
use App\Repository\ChapitreRepository;
use App\Repository\RessourceFavoriRepository;
use App\Repository\RessourceLikeRepository;
use App\Repository\RessourceRepository;
use App\Service\CloudinaryStorageService;
use App\Service\RessourceQuizGeneratorService;
use App\Service\ScoreCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ressource')]
final class RessourceController extends AbstractController
{
    private ?bool $hasInteractionTable = null;

    #[Route(name: 'app_ressource_index', methods: ['GET'])]
    public function index(
        Request $request,
        RessourceRepository $ressourceRepository,
        ChapitreRepository $chapitreRepository,
        EntityManagerInterface $entityManager,
        ScoreCalculatorService $scoreCalculatorService
    ): Response
    {
        $categorieNom = trim((string) $request->query->get('categorie_nom', ''));
        $chapitreId = $request->query->getInt('chapitre_id', 0);
        $chapitreTitre = '';
        $topRessources = [];

        if ($chapitreId > 0) {
            $chapitre = $chapitreRepository->find($chapitreId);
            $chapitreTitre = (string) ($chapitre?->getTitre() ?? '');
            $ressources = $ressourceRepository->findByChapitreId($chapitreId);
            $topRessources = $ressourceRepository->findTopByChapitreId($chapitreId, 3);
        } elseif ($categorieNom !== '') {
            $ressources = $ressourceRepository->findByCategorieNom($categorieNom);
        } else {
            $ressources = $ressourceRepository->findAllByScoreDesc();
        }

        $this->refreshBadgesForListing($ressources, $scoreCalculatorService);
        $entityManager->flush();

        return $this->render('ressource/index.html.twig', [
            'ressources' => $ressources,
            'top_ressources' => $topRessources,
            'categorie_nom' => $categorieNom,
            'chapitre_id' => $chapitreId,
            'chapitre_titre' => $chapitreTitre,
        ]);
    }

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CloudinaryStorageService $cloudinaryStorageService,
        RessourceQuizGeneratorService $quizGeneratorService
    ): Response
    {
        $ressource = new Ressource();
        $ressource->setAvailableAt(new \DateTimeImmutable());
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $upload = $this->resolveContenuForCategorie($form, $ressource, $cloudinaryStorageService);

            if ($form->isValid() && $upload !== null) {
                $ressource
                    ->setContenu($upload['url'])
                    ->setCloudinaryPublicId($upload['publicId'])
                    ->setCloudinaryResourceType($upload['resourceType']);

                $entityManager->persist($ressource);
                $entityManager->flush();
                $quizGeneratorService->regenerateForRessource($ressource);

                return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
            }

            $this->addFlash('error', 'Le formulaire contient des erreurs. Merci de verifier les champs.');
        }

        return $this->render('ressource/new.html.twig', [
            'ressource' => $ressource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Ressource $ressource,
        EntityManagerInterface $entityManager,
        ScoreCalculatorService $scoreCalculatorService,
        RessourceRepository $ressourceRepository,
        RessourceLikeRepository $ressourceLikeRepository,
        RessourceFavoriRepository $ressourceFavoriRepository
    ): Response
    {
        $ressource->incrementNbVues();
        $this->recordInteraction($entityManager, $ressource, RessourceInteraction::TYPE_VIEW);
        $scoreCalculatorService->recalculate($ressource);
        $this->refreshRelativeBadges($ressource, $ressourceRepository, $scoreCalculatorService);
        $entityManager->flush();

        $isLiked = false;
        $isFavori = false;
        $user = $this->getUser();
        if ($user instanceof Utilisateur) {
            $isLiked = $ressourceLikeRepository->findOneByRessourceAndUtilisateur($ressource, $user) !== null;
            $isFavori = $ressourceFavoriRepository->findOneByRessourceAndUtilisateur($ressource, $user) !== null;
        }

        return $this->render('ressource/show.html.twig', [
            'ressource' => $ressource,
            'is_liked' => $isLiked,
            'is_favori' => $isFavori,
        ]);
    }

    #[Route('/{id}/like', name: 'app_ressource_like', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleLike(
        Request $request,
        Ressource $ressource,
        RessourceLikeRepository $ressourceLikeRepository,
        RessourceRepository $ressourceRepository,
        ScoreCalculatorService $scoreCalculatorService,
        EntityManagerInterface $entityManager
    ): Response {
        $utilisateur = $this->getCurrentUtilisateur();
        $like = $ressourceLikeRepository->findOneByRessourceAndUtilisateur($ressource, $utilisateur);
        $ressource->incrementNbVues();
        $this->recordInteraction($entityManager, $ressource, RessourceInteraction::TYPE_VIEW, $utilisateur);

        if ($like === null) {
            $like = (new RessourceLike())
                ->setRessource($ressource)
                ->setUtilisateur($utilisateur);
            $entityManager->persist($like);
            $ressource->incrementNbLikes();
            $this->recordInteraction($entityManager, $ressource, RessourceInteraction::TYPE_LIKE, $utilisateur);
        } else {
            $entityManager->remove($like);
            $ressource->decrementNbLikes();
            $this->recordInteraction($entityManager, $ressource, RessourceInteraction::TYPE_UNLIKE, $utilisateur);
        }

        $scoreCalculatorService->recalculate($ressource);
        $this->refreshRelativeBadges($ressource, $ressourceRepository, $scoreCalculatorService);
        $entityManager->flush();

        return $this->redirectToReferer($request, $ressource);
    }

    #[Route('/{id}/favori', name: 'app_ressource_favori', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleFavori(
        Request $request,
        Ressource $ressource,
        RessourceFavoriRepository $ressourceFavoriRepository,
        RessourceRepository $ressourceRepository,
        ScoreCalculatorService $scoreCalculatorService,
        EntityManagerInterface $entityManager
    ): Response {
        $utilisateur = $this->getCurrentUtilisateur();
        $favori = $ressourceFavoriRepository->findOneByRessourceAndUtilisateur($ressource, $utilisateur);
        $ressource->incrementNbVues();
        $this->recordInteraction($entityManager, $ressource, RessourceInteraction::TYPE_VIEW, $utilisateur);

        if ($favori === null) {
            $favori = (new RessourceFavori())
                ->setRessource($ressource)
                ->setUtilisateur($utilisateur);
            $entityManager->persist($favori);
            $ressource->incrementNbFavoris();
            $this->recordInteraction($entityManager, $ressource, RessourceInteraction::TYPE_FAVORI, $utilisateur);
        } else {
            $entityManager->remove($favori);
            $ressource->decrementNbFavoris();
            $this->recordInteraction($entityManager, $ressource, RessourceInteraction::TYPE_UNFAVORI, $utilisateur);
        }

        $scoreCalculatorService->recalculate($ressource);
        $this->refreshRelativeBadges($ressource, $ressourceRepository, $scoreCalculatorService);
        $entityManager->flush();

        return $this->redirectToReferer($request, $ressource);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Ressource $ressource,
        EntityManagerInterface $entityManager,
        CloudinaryStorageService $cloudinaryStorageService,
        RessourceQuizGeneratorService $quizGeneratorService
    ): Response
    {
        $previousPublicId = $ressource->getCloudinaryPublicId();
        $previousResourceType = $ressource->getCloudinaryResourceType();

        $form = $this->createForm(RessourceType::class, $ressource);

        if ($request->isMethod('GET')) {
            $this->hydrateOptionalFields($form, $ressource);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $upload = $this->resolveContenuForCategorie($form, $ressource, $cloudinaryStorageService);

            if ($form->isValid() && $upload !== null) {
                $ressource
                    ->setContenu($upload['url'])
                    ->setCloudinaryPublicId($upload['publicId'])
                    ->setCloudinaryResourceType($upload['resourceType']);

                if ($previousPublicId !== null && $previousPublicId !== $upload['publicId']) {
                    $cloudinaryStorageService->delete($previousPublicId, $previousResourceType);
                }

                $entityManager->flush();
                $quizGeneratorService->regenerateForRessource($ressource);

                return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('ressource/edit.html.twig', [
            'ressource' => $ressource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Ressource $ressource,
        EntityManagerInterface $entityManager,
        CloudinaryStorageService $cloudinaryStorageService
    ): Response
    {
        $token = (string) $request->request->get('_token', '');
        if ($token === '' && method_exists($request, 'getPayload')) {
            $token = $request->getPayload()->getString('_token');
        }

        if (!$this->isCsrfTokenValid('delete'.$ressource->getId(), $token)) {
            $this->addFlash('error', 'Jeton de suppression invalide.');

            return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $cloudinaryStorageService->delete($ressource->getCloudinaryPublicId(), $ressource->getCloudinaryResourceType());
        } catch (\Throwable) {
            $this->addFlash('warning', 'Fichier distant non supprime, mais la ressource locale sera supprimee.');
        }

        try {
            $entityManager->remove($ressource);
            $entityManager->flush();
            $this->addFlash('success', 'Ressource supprimee avec succes.');
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            $isQuizTableIssue = str_contains($message, 'ressource_quiz') && (
                str_contains($message, "doesn't exist")
                || str_contains($message, 'base table or view not found')
            );

            if ($isQuizTableIssue) {
                try {
                    $deleted = $entityManager->getConnection()->executeStatement(
                        'DELETE FROM ressource WHERE id = :id',
                        ['id' => (int) $ressource->getId()]
                    );
                    if ($deleted > 0) {
                        $this->addFlash('success', 'Ressource supprimee avec succes.');

                        return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
                    }
                } catch (\Throwable) {
                    // Keep generic message below.
                }
            }

            $this->addFlash('error', 'Suppression impossible pour le moment. Veuillez reessayer.');
        }

        return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @return array{url: string, publicId: ?string, resourceType: ?string, isNewUpload: bool}|null
     */
    private function resolveContenuForCategorie(FormInterface $form, Ressource $ressource, CloudinaryStorageService $cloudinaryStorageService): ?array
    {
        $categorieNom = strtolower(trim((string) ($ressource->getCategorie()?->getNom() ?? '')));
        $existingContenu = $ressource->getContenu();
        $existingPublicId = $ressource->getCloudinaryPublicId();
        $existingResourceType = $ressource->getCloudinaryResourceType();

        if ($categorieNom === '') {
            $form->addError(new FormError('Veuillez choisir une categorie.'));

            return null;
        }

        if ($categorieNom === 'image') {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile instanceof UploadedFile) {
                if (!$this->hasAllowedExtension($imageFile, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                    $form->get('imageFile')->addError(new FormError('Veuillez choisir une image valide (jpg, jpeg, png, gif, webp, bmp).'));

                    return null;
                }

                return $this->uploadToCloudinary($imageFile, 'ressources/images', $form, 'imageFile', $cloudinaryStorageService);
            }
            if (!empty($existingContenu)) {
                return [
                    'url' => $existingContenu,
                    'publicId' => $existingPublicId,
                    'resourceType' => $existingResourceType,
                    'isNewUpload' => false,
                ];
            }
            $form->get('imageFile')->addError(new FormError('Veuillez choisir une image.'));
            return null;
        }

        if ($categorieNom === 'video') {
            /** @var UploadedFile|null $videoFile */
            $videoFile = $form->get('videoFile')->getData();
            $videoUrl = trim((string) $form->get('videoUrl')->getData());
            if ($videoUrl !== '') {
                return [
                    'url' => $videoUrl,
                    'publicId' => null,
                    'resourceType' => null,
                    'isNewUpload' => false,
                ];
            }
            if ($videoFile instanceof UploadedFile) {
                if (!$this->hasAllowedExtension($videoFile, ['mp4'])) {
                    $form->get('videoFile')->addError(new FormError('Veuillez choisir une video MP4 valide.'));

                    return null;
                }

                return $this->uploadToCloudinary($videoFile, 'ressources/videos', $form, 'videoFile', $cloudinaryStorageService);
            }
            if (!empty($existingContenu)) {
                return [
                    'url' => $existingContenu,
                    'publicId' => $existingPublicId,
                    'resourceType' => $existingResourceType,
                    'isNewUpload' => false,
                ];
            }
            $form->addError(new FormError('Ajoutez une Video URL ou telechargez une video MP4.'));
            return null;
        }

        if ($categorieNom === 'audio') {
            /** @var UploadedFile|null $audioFile */
            $audioFile = $form->get('audioFile')->getData();
            $audioUrl = trim((string) $form->get('audioUrl')->getData());
            if ($audioUrl !== '') {
                return [
                    'url' => $audioUrl,
                    'publicId' => null,
                    'resourceType' => null,
                    'isNewUpload' => false,
                ];
            }
            if ($audioFile instanceof UploadedFile) {
                if (!$this->hasAllowedExtension($audioFile, ['mp3', 'wav'])) {
                    $form->get('audioFile')->addError(new FormError('Veuillez choisir un audio MP3 ou WAV valide.'));

                    return null;
                }

                return $this->uploadToCloudinary($audioFile, 'ressources/audios', $form, 'audioFile', $cloudinaryStorageService);
            }
            if (!empty($existingContenu)) {
                return [
                    'url' => $existingContenu,
                    'publicId' => $existingPublicId,
                    'resourceType' => $existingResourceType,
                    'isNewUpload' => false,
                ];
            }
            $form->addError(new FormError('Ajoutez un Audio URL ou telechargez un fichier MP3/WAV.'));
            return null;
        }

        if ($categorieNom === 'pdf') {
            /** @var UploadedFile|null $documentFile */
            $documentFile = $form->get('documentFile')->getData();
            if ($documentFile instanceof UploadedFile) {
                if (!$this->hasAllowedExtension($documentFile, ['pdf'])) {
                    $form->get('documentFile')->addError(new FormError('Veuillez choisir un document PDF valide.'));

                    return null;
                }

                return $this->uploadToCloudinary($documentFile, 'ressources/documents', $form, 'documentFile', $cloudinaryStorageService);
            }
            if (!empty($existingContenu)) {
                return [
                    'url' => $existingContenu,
                    'publicId' => $existingPublicId,
                    'resourceType' => $existingResourceType,
                    'isNewUpload' => false,
                ];
            }
            $form->get('documentFile')->addError(new FormError('Veuillez choisir un document PDF.'));
            return null;
        }

        if ($categorieNom === 'lien') {
            $lienUrl = trim((string) $form->get('lienUrl')->getData());
            if ($lienUrl !== '') {
                return [
                    'url' => $lienUrl,
                    'publicId' => null,
                    'resourceType' => null,
                    'isNewUpload' => false,
                ];
            }
            if (!empty($existingContenu)) {
                return [
                    'url' => $existingContenu,
                    'publicId' => null,
                    'resourceType' => null,
                    'isNewUpload' => false,
                ];
            }
            $form->get('lienUrl')->addError(new FormError('Veuillez coller un lien externe.'));
            return null;
        }

        $form->addError(new FormError('Categorie non supportee. Utilisez video, audio, lien, image ou pdf.'));

        return null;
    }

    private function hydrateOptionalFields(FormInterface $form, Ressource $ressource): void
    {
        $categorieNom = strtolower(trim((string) ($ressource->getCategorie()?->getNom() ?? '')));
        $contenu = (string) $ressource->getContenu();

        if ($categorieNom === 'lien') {
            $form->get('lienUrl')->setData($contenu);
        }

        if ($categorieNom === 'video' && preg_match('/^https?:\\/\\//', $contenu) === 1) {
            $form->get('videoUrl')->setData($contenu);
        }

        if ($categorieNom === 'audio' && preg_match('/^https?:\\/\\//', $contenu) === 1) {
            $form->get('audioUrl')->setData($contenu);
        }
    }

    /**
     * @return array{url: string, publicId: ?string, resourceType: ?string, isNewUpload: bool}|null
     */
    private function uploadToCloudinary(
        UploadedFile $uploadedFile,
        string $folder,
        FormInterface $form,
        string $fieldName,
        CloudinaryStorageService $cloudinaryStorageService
    ): ?array
    {
        try {
            $uploaded = $cloudinaryStorageService->upload($uploadedFile, $folder);
        } catch (\Throwable $e) {
            $form->get($fieldName)->addError(new FormError('Echec upload Cloudinary: '.$e->getMessage()));

            return null;
        }

        return [
            'url' => $uploaded['secureUrl'],
            'publicId' => $uploaded['publicId'],
            'resourceType' => $uploaded['resourceType'],
            'isNewUpload' => true,
        ];
    }

    private function hasAllowedExtension(UploadedFile $uploadedFile, array $allowedExtensions): bool
    {
        $extension = $this->resolveUploadedExtension($uploadedFile);

        return in_array($extension, $allowedExtensions, true);
    }

    private function resolveUploadedExtension(UploadedFile $uploadedFile): string
    {
        $extension = strtolower((string) pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'bin';
    }

    private function getCurrentUtilisateur(): Utilisateur
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        return $user;
    }

    private function redirectToReferer(Request $request, Ressource $ressource): Response
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_ressource_show', ['id' => $ressource->getId()]);
    }

    private function recordInteraction(
        EntityManagerInterface $entityManager,
        Ressource $ressource,
        string $type,
        ?Utilisateur $utilisateur = null
    ): void {
        if (!$this->hasInteractionTable($entityManager)) {
            return;
        }

        $interaction = (new RessourceInteraction())
            ->setRessource($ressource)
            ->setType($type);

        if ($utilisateur instanceof Utilisateur) {
            $interaction->setUtilisateur($utilisateur);
        }

        $entityManager->persist($interaction);
    }

    private function hasInteractionTable(EntityManagerInterface $entityManager): bool
    {
        if ($this->hasInteractionTable !== null) {
            return $this->hasInteractionTable;
        }

        try {
            $schemaManager = $entityManager->getConnection()->createSchemaManager();
            $this->hasInteractionTable = $schemaManager->tablesExist(['ressource_interaction']);
        } catch (\Throwable) {
            $this->hasInteractionTable = false;
        }

        return $this->hasInteractionTable;
    }

    private function refreshRelativeBadges(
        Ressource $anchorRessource,
        RessourceRepository $ressourceRepository,
        ScoreCalculatorService $scoreCalculatorService
    ): void {
        $chapitreId = (int) ($anchorRessource->getChapitre()?->getId() ?? 0);
        $ressources = $chapitreId > 0
            ? $ressourceRepository->findByChapitreId($chapitreId)
            : $ressourceRepository->findAllByScoreDesc();

        if ($ressources === []) {
            return;
        }

        $maxScore = 0;
        foreach ($ressources as $ressource) {
            $maxScore = max($maxScore, $ressource->getScore());
        }

        foreach ($ressources as $ressource) {
            $scoreCalculatorService->applyRelativeBadge($ressource, $maxScore);
        }
    }

    /**
     * @param Ressource[] $ressources
     */
    private function refreshBadgesForListing(array $ressources, ScoreCalculatorService $scoreCalculatorService): void
    {
        if ($ressources === []) {
            return;
        }

        $byChapitre = [];
        foreach ($ressources as $ressource) {
            $chapitreId = (int) ($ressource->getChapitre()?->getId() ?? 0);
            $byChapitre[$chapitreId] ??= [];
            $byChapitre[$chapitreId][] = $ressource;
        }

        foreach ($byChapitre as $group) {
            $maxScore = 0;
            foreach ($group as $ressource) {
                $maxScore = max($maxScore, $ressource->getScore());
            }

            foreach ($group as $ressource) {
                $scoreCalculatorService->applyRelativeBadge($ressource, $maxScore);
            }
        }
    }
}
