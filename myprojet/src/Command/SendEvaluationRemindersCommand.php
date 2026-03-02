<?php

namespace App\Command;

use App\Repository\ExamenRepository;
use App\Repository\UtilisateurRepository;
use App\Service\EvaluationNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:evaluations:send-reminders',
    description: 'Envoie les rappels automatiques des evaluations a J-N (3 par defaut).'
)]
class SendEvaluationRemindersCommand extends Command
{
    public function __construct(
        private ExamenRepository $examenRepository,
        private UtilisateurRepository $utilisateurRepository,
        private EvaluationNotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days-before',
                null,
                InputOption::VALUE_REQUIRED,
                'Nombre de jours avant la date de l examen',
                '3'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $daysBefore = (int) $input->getOption('days-before');
        if ($daysBefore < 0) {
            $io->error('L option --days-before doit etre positive.');

            return Command::INVALID;
        }

        $targetDate = (new \DateTimeImmutable('today'))->modify(sprintf('+%d days', $daysBefore));
        $examens = $this->examenRepository->findByExactDate($targetDate);
        if ($examens === []) {
            $io->success(sprintf('Aucun examen a rappeler pour le %s.', $targetDate->format('d/m/Y')));

            return Command::SUCCESS;
        }

        $phones = $this->utilisateurRepository->findPhonesByRoles(['ROLE_ETUDIANT', 'ROLE_STUDENT', 'ROLE_PARENT']);
        if ($phones === []) {
            $io->warning('Aucun destinataire valide trouve.');

            return Command::SUCCESS;
        }

        $okCount = 0;
        foreach ($examens as $examen) {
            $result = $this->notificationService->sendEvaluationNotification(
                $phones,
                sprintf('Rappel evaluation: %s', (string) $examen->getTitre()),
                sprintf(
                    "Rappel: l evaluation approche.\n\nTitre: %s\nDate: %s\nDuree: %d minutes\nCours: %s",
                    (string) $examen->getTitre(),
                    $examen->getDateExamen()?->format('d/m/Y') ?? 'N/A',
                    (int) ($examen->getDuree() ?? 0),
                    $examen->getCours()?->getTitre() ?? 'N/A'
                )
            );

            if (($result['sent'] ?? false) === true) {
                ++$okCount;
                continue;
            }

            $io->warning(sprintf(
                'Examen #%d (%s): %s',
                (int) $examen->getId(),
                (string) $examen->getTitre(),
                (string) ($result['message'] ?? 'Echec envoi')
            ));
        }

        $io->success(sprintf(
            'Rappels traites: %d examen(s), succes: %d, echecs: %d.',
            count($examens),
            $okCount,
            count($examens) - $okCount
        ));

        return Command::SUCCESS;
    }
}

