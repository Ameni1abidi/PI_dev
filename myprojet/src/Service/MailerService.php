<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(private MailerInterface $mailer) {}

    /**
     * Envoie un mail HTML simple
     */
    public function sendEmail(string $to, string $subject, string $htmlContent): void
    {
        $email = (new Email())
            ->from('no-reply@eduflex.tn') // expéditeur visible
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        try {
            $this->mailer->send($email);
            dump('✅ Mail envoyé à : '.$to); // debug
        } catch (\Exception $e) {
            dump('❌ Erreur mail : '.$e->getMessage());
        }
    }
}
