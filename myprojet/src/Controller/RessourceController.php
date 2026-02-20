<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\ChapitreRepository;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/ressource')]
final class RessourceController extends AbstractController
{
    #[Route(name: 'app_ressource_index', methods: ['GET'])]
    public function index(Request $request, RessourceRepository $ressourceRepository, ChapitreRepository $chapitreRepository): Response
    {
        $categorieNom = trim((string) $request->query->get('categorie_nom', ''));
        $chapitreId = $request->query->getInt('chapitre_id', 0);
        $chapitreTitre = '';

        if ($chapitreId > 0) {
            $chapitre = $chapitreRepository->find($chapitreId);
            $chapitreTitre = (string) ($chapitre?->getTitre() ?? '');
            $ressources = $ressourceRepository->findByChapitreId($chapitreId);
        } elseif ($categorieNom !== '') {
            $ressources = $ressourceRepository->findByCategorieNom($categorieNom);
        } else {
            $ressources = $ressourceRepository->findAll();
        }

        return $this->render('ressource/index.html.twig', [
            'ressources' => $ressources,
            'categorie_nom' => $categorieNom,
            'chapitre_id' => $chapitreId,
            'chapitre_titre' => $chapitreTitre,
        ]);
    }

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $ressource = new Ressource();
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $contenu = $this->resolveContenuForCategorie($form, $ressource, $slugger);

            if ($form->isValid() && $contenu !== null) {
                $ressource->setContenu($contenu);

                $entityManager->persist($ressource);
                $entityManager->flush();

                return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
            }

            $this->addFlash('error', 'Le formulaire contient des erreurs. Merci de verifier les champs.');
        }

        return $this->render('ressource/new.html.twig', [
            'ressource' => $ressource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_show', methods: ['GET'])]
    public function show(Ressource $ressource): Response
    {
        return $this->render('ressource/show.html.twig', [
            'ressource' => $ressource,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(RessourceType::class, $ressource);

        if ($request->isMethod('GET')) {
            $this->hydrateOptionalFields($form, $ressource);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $contenu = $this->resolveContenuForCategorie($form, $ressource, $slugger);

            if ($form->isValid() && $contenu !== null) {
                $ressource->setContenu($contenu);

                $entityManager->flush();

                return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('ressource/edit.html.twig', [
            'ressource' => $ressource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_delete', methods: ['POST'])]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ressource->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ressource);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
    }

    private function resolveContenuForCategorie(FormInterface $form, Ressource $ressource, SluggerInterface $slugger): ?string
    {
        $categorieNom = strtolower(trim((string) ($ressource->getCategorie()?->getNom() ?? '')));
        $existingContenu = $ressource->getContenu();

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

                return $this->uploadFile($imageFile, 'images', $slugger);
            }
            if (!empty($existingContenu)) {
                return $existingContenu;
            }
            $form->get('imageFile')->addError(new FormError('Veuillez choisir une image.'));
            return null;
        }

        if ($categorieNom === 'video') {
            /** @var UploadedFile|null $videoFile */
            $videoFile = $form->get('videoFile')->getData();
            $videoUrl = trim((string) $form->get('videoUrl')->getData());
            if ($videoUrl !== '') {
                return $videoUrl;
            }
            if ($videoFile instanceof UploadedFile) {
                if (!$this->hasAllowedExtension($videoFile, ['mp4'])) {
                    $form->get('videoFile')->addError(new FormError('Veuillez choisir une video MP4 valide.'));

                    return null;
                }

                return $this->uploadFile($videoFile, 'videos', $slugger);
            }
            if (!empty($existingContenu)) {
                return $existingContenu;
            }
            $form->addError(new FormError('Ajoutez une Video URL ou telechargez une video MP4.'));
            return null;
        }

        if ($categorieNom === 'audio') {
            /** @var UploadedFile|null $audioFile */
            $audioFile = $form->get('audioFile')->getData();
            $audioUrl = trim((string) $form->get('audioUrl')->getData());
            if ($audioUrl !== '') {
                return $audioUrl;
            }
            if ($audioFile instanceof UploadedFile) {
                if (!$this->hasAllowedExtension($audioFile, ['mp3', 'wav'])) {
                    $form->get('audioFile')->addError(new FormError('Veuillez choisir un audio MP3 ou WAV valide.'));

                    return null;
                }

                return $this->uploadFile($audioFile, 'audios', $slugger);
            }
            if (!empty($existingContenu)) {
                return $existingContenu;
            }
            $form->addError(new FormError('Ajoutez un Audio URL ou telechargez un fichier MP3/WAV.'));
            return null;
        }

        if ($categorieNom === 'lien') {
            $lienUrl = trim((string) $form->get('lienUrl')->getData());
            if ($lienUrl !== '') {
                return $lienUrl;
            }
            if (!empty($existingContenu)) {
                return $existingContenu;
            }
            $form->get('lienUrl')->addError(new FormError('Veuillez coller un lien externe.'));
            return null;
        }

        $form->addError(new FormError('Categorie non supportee. Utilisez video, audio, lien ou image.'));

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

    private function uploadFile(UploadedFile $uploadedFile, string $folder, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $extension = $this->resolveUploadedExtension($uploadedFile);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/ressources/'.$folder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $uploadedFile->move($uploadDir, $newFilename);

        return 'uploads/ressources/'.$folder.'/'.$newFilename;
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
}
