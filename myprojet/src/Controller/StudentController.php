<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StudentController extends AbstractController
{
    #[Route('/eleve/dashboard', name: 'app_student_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('student/dashboard.html.twig');
    }
}
