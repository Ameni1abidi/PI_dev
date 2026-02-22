<?php

namespace App\Controller;

use App\Service\AdminCopilotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminCopilotController extends AbstractController
{
    #[Route('/admin/copilot', name: 'app_admin_copilot', methods: ['GET', 'POST'])]
    public function index(Request $request, AdminCopilotService $copilotService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $question = '';
        $result = null;
        $adminInitials = 'AD';

        $user = $this->getUser();
        if ($user && method_exists($user, 'getNom')) {
            $parts = preg_split('/\s+/', trim((string) $user->getNom())) ?: [];
            $initials = '';
            foreach (array_slice($parts, 0, 2) as $part) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
            $adminInitials = $initials !== '' ? $initials : 'AD';
        }

        if ($request->isMethod('POST')) {
            $submittedToken = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('admin_copilot_page', $submittedToken)) {
                $this->addFlash('error', 'Session invalide, veuillez reessayer.');
                return $this->redirectToRoute('app_admin_copilot');
            }

            $question = trim((string) $request->request->get('question', ''));
            if ($question === '') {
                $this->addFlash('error', 'Veuillez saisir une question.');
            } else {
                $result = $copilotService->answer($question);
            }
        }

        return $this->render('admin/copilot.html.twig', [
            'question' => $question,
            'result' => $result,
            'admin_initials' => $adminInitials,
        ]);
    }
}
