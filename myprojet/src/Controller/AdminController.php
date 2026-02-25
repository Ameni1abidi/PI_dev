<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/admin/statistiques', name: 'app_admin_stats', methods: ['GET'])]
    public function stats(): RedirectResponse
    {
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/classes', name: 'app_admin_classes', methods: ['GET'])]
    public function classes(CoursRepository $coursRepository): Response
    {
        return $this->render('admin/classes.html.twig', [
            'cours' => $coursRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/admin/droits-acces', name: 'app_admin_access', methods: ['GET'])]
    public function access(): RedirectResponse
    {
        return $this->redirectToRoute('app_utilisateur_index');
    }

    #[Route('/admin/parametres', name: 'app_admin_settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->render('admin/settings.html.twig');
    }
}

