<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParentController extends AbstractController
{
    #[Route('/parent/dashboard', name: 'app_parent_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('parent/dashboard.html.twig');
    }
}
