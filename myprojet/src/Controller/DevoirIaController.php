<?php

namespace App\Controller;

use App\Entity\DevoirIa;
use App\Entity\Utilisateur;
use App\Form\DevoirIaType;
use App\Repository\DevoirIaRepository;
use App\Service\DevoirIaGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/devoir-ia')]
final class DevoirIaController extends AbstractController
{
    #[Route('/', name: 'app_devoir_ia_index', methods: ['GET'])]
    public function index(DevoirIaRepository $devoirIaRepository): Response
    {
        $user = $this->getUser();
        $devoirs = $devoirIaRepository->findBy([], ['dateCreation' => 'DESC']);

        if ($user instanceof Utilisateur) {
            $devoirs = $devoirIaRepository->findForTeacher((int) $user->getId());
        }

        return $this->render('devoir_ia/index.html.twig', [
            'devoirs' => $devoirs,
        ]);
    }

    #[Route('/new', name: 'app_devoir_ia_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        DevoirIaGeneratorService $generator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Authentification requise.');
        }

        $devoir = new DevoirIa();
        $devoir->setEnseignant($user);

        $form = $this->createForm(DevoirIaType::class, $devoir);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (($devoir->getNbQcm() + $devoir->getNbVraiFaux() + $devoir->getNbReponseCourte()) <= 0) {
                $this->addFlash('danger', 'Ajoutez au moins une question.');

                return $this->render('devoir_ia/new.html.twig', [
                    'devoir' => $devoir,
                    'form' => $form,
                ]);
            }

            $payload = $generator->generate($devoir);
            $devoir->setContenuArray($payload);

            $entityManager->persist($devoir);
            $entityManager->flush();

            $this->addFlash('success', 'Devoir genere avec succes.');

            return $this->redirectToRoute('app_devoir_ia_show', ['id' => $devoir->getId()]);
        }

        return $this->render('devoir_ia/new.html.twig', [
            'devoir' => $devoir,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_devoir_ia_show', methods: ['GET'])]
    public function show(DevoirIa $devoirIa): Response
    {
        return $this->render('devoir_ia/show.html.twig', [
            'devoir' => $devoirIa,
            'payload' => $devoirIa->getContenuArray(),
        ]);
    }
}
