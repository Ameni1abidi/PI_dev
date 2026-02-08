<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EnseignantController extends AbstractController
{
    #[Route('/enseignant/dashboard', name: 'app_enseignant_dashboard', methods: ['GET'])]
    public function dashboard(CoursRepository $coursRepo): Response
    {
        $cours = $coursRepo->findAll(); // or filter by connected enseignant
        return $this->render('enseignant/dashboard.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/enseignant/evaluations', name: 'app_enseignant_evaluations', methods: ['GET'])]
    public function evaluations(): Response
    {
        return $this->render('enseignant/evaluations.html.twig');
    }
}
