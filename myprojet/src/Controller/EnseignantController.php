<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EnseignantController extends AbstractController
{
    #[Route('/enseignant/dashboard', name: 'app_enseignant_dashboard', methods: ['GET'])]
<<<<<<< HEAD
    public function dashboard(CoursRepository $coursRepo): Response
{
    $cours = $coursRepo->findAll(); // ou filter par enseignant connecté
    return $this->render('enseignant/dashboard.html.twig', [
        'cours' => $cours,
    ]);
}
=======
    public function dashboard(): Response
    {
        return $this->render('enseignant/dashboard.html.twig');
    }

    #[Route('/enseignant/evaluations', name: 'app_enseignant_evaluations', methods: ['GET'])]
    public function evaluations(): Response
    {
        return $this->render('enseignant/evaluations.html.twig');
    }
>>>>>>> 4f71f37c8025d4e6d8caf600463874d1796f6efe
}
