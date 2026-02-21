<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Service\ProfanityFilterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commentaire')]
class CommentaireController extends AbstractController
{
    #[Route('/{id}/edit', name: 'app_commentaire_edit', methods: ['GET','POST'])]
    public function edit(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager,
        ProfanityFilterService $profanityFilterService
    ): Response {

        if ($request->isMethod('POST')) {

            $contenu = $request->request->get('contenu');

            if ($contenu) {
                $badWords = $profanityFilterService->findInappropriateWords((string) $contenu);
                if ($badWords !== []) {
                    $this->addFlash(
                        'error',
                        'Commentaire refuse: mots inappropries detectes (' . implode(', ', $badWords) . ').'
                    );

                    return $this->redirectToRoute('app_forum_index');
                }

                $commentaire->setContenu($contenu);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('commentaire/edit.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_commentaire_delete', methods: ['POST'])]
    public function delete(
        Commentaire $commentaire,
        EntityManagerInterface $entityManager
    ): Response {

        $entityManager->remove($commentaire);
        $entityManager->flush();

        return $this->redirectToRoute('app_forum_index');
    }
}
