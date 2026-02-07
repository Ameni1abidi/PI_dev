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
    $cours = $coursRepo->findAll(); // ou filter par enseignant connecté
    return $this->render('enseignant/dashboard.html.twig', [
        'cours' => $cours,
    ]);
}
}
