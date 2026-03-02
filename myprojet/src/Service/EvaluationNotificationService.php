<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class EvaluationNotificationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $twilioAccountSid,
        private string $twilioAuthToken,
        private string $twilioFromNumber,
        private string $twilioWhatsAppFrom
    ) {
    }

    /**
     * @param string[] $toPhones
     * @return array<string, mixed>
     */
    public function sendEvaluationNotification(array $toPhones, string $subject, string $body): array
    {
        $recipients = array_values(array_filter(array_unique(array_map(
            fn (string $phone): string => $this->normalizePhone($phone),
            array_map('trim', $toPhones)
        ))));
        if ($recipients === []) {
            return [
                'sent' => false,
                'message' => 'Aucun destinataire valide.',
                'status_code' => null,
            ];
        }

        if ($this->twilioAccountSid === '' || $this->twilioAuthToken === '' || $this->twilioFromNumber === '') {
            return [
                'sent' => false,
                'message' => 'Configuration Twilio manquante.',
                'status_code' => null,
            ];
        }

        $smsBody = trim($subject) !== '' ? ($subject . "\n" . $body) : $body;
        $smsStatusCodes = [];
        $whatsAppStatusCodes = [];
        $errors = [];

        foreach ($recipients as $phone) {
            try {
                $response = $this->httpClient->request(
                    'POST',
                    sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode($this->twilioAccountSid)),
                    [
                        'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                        'body' => [
                            'To' => $phone,
                            'From' => $this->twilioFromNumber,
                            'Body' => $smsBody,
                        ],
                    ]
                );
                $smsStatus = $response->getStatusCode();
                $smsStatusCodes[] = $smsStatus;
                if ($smsStatus < 200 || $smsStatus >= 300) {
                    $payload = $response->toArray(false);
                    $errors[] = sprintf('SMS %s: %s', $phone, (string) ($payload['message'] ?? ('HTTP ' . $smsStatus)));
                }
            } catch (TransportExceptionInterface $e) {
                $smsStatusCodes[] = 0;
                $errors[] = sprintf('SMS %s: %s', $phone, $e->getMessage());
            }

            if ($this->twilioWhatsAppFrom !== '') {
                try {
                    $waResponse = $this->httpClient->request(
                        'POST',
                        sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode($this->twilioAccountSid)),
                        [
                            'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                            'body' => [
                                'To' => 'whatsapp:' . $phone,
                                'From' => $this->twilioWhatsAppFrom,
                                'Body' => $smsBody,
                            ],
                        ]
                    );
                    $waStatus = $waResponse->getStatusCode();
                    $whatsAppStatusCodes[] = $waStatus;
                    if ($waStatus < 200 || $waStatus >= 300) {
                        $waPayload = $waResponse->toArray(false);
                        $errors[] = sprintf('WhatsApp %s: %s', $phone, (string) ($waPayload['message'] ?? ('HTTP ' . $waStatus)));
                    }
                } catch (TransportExceptionInterface $e) {
                    $whatsAppStatusCodes[] = 0;
                    $errors[] = sprintf('WhatsApp %s: %s', $phone, $e->getMessage());
                }
            }
        }

        $smsSuccess = count(array_filter($smsStatusCodes, static fn (int $code): bool => $code >= 200 && $code < 300)) === count($smsStatusCodes);
        $waConfigured = $this->twilioWhatsAppFrom !== '';
        $waSuccess = !$waConfigured || (
            $whatsAppStatusCodes !== [] &&
            count(array_filter($whatsAppStatusCodes, static fn (int $code): bool => $code >= 200 && $code < 300)) === count($whatsAppStatusCodes)
        );
        $allSuccess = $smsSuccess && $waSuccess;
        $partialSuccess = $smsSuccess && $waConfigured && !$waSuccess;

        if ($allSuccess) {
            $message = $waConfigured ? 'SMS et WhatsApp envoyes.' : 'SMS envoye.';
        } elseif ($partialSuccess) {
            $message = 'SMS envoye, echec WhatsApp.';
        } else {
            $message = $waConfigured ? 'Echec envoi Twilio (SMS/WhatsApp).' : 'Echec envoi Twilio.';
        }
        if ($errors !== []) {
            $message .= ' ' . implode(' | ', $errors);
        }

        return [
            'sent' => $smsSuccess,
            'overall_success' => $allSuccess,
            'partial_success' => $partialSuccess,
            'sms_success' => $smsSuccess,
            'whatsapp_success' => $waSuccess,
            'message' => $message,
            'status_code' => $smsStatusCodes[0],
            'status_codes' => [
                'sms' => $smsStatusCodes,
                'whatsapp' => $whatsAppStatusCodes,
            ],
            'channel' => $waConfigured ? 'sms+whatsapp' : 'sms',
        ];
    }

    private function normalizePhone(string $value): string
    {
        $raw = trim((string) preg_replace('/^whatsapp:/i', '', trim($value)));
        if ($raw === '') {
            return '';
        }

        $clean = (string) preg_replace('/[^\d+]/', '', $raw);
        if ($clean === '') {
            return '';
        }

        if (str_starts_with($clean, '00')) {
            $clean = '+' . substr($clean, 2);
        }
        if (!str_starts_with($clean, '+')) {
            $clean = '+' . ltrim($clean, '+');
        }

        if (!preg_match('/^\+[1-9]\d{7,14}$/', $clean)) {
            return '';
        }

        return $clean;
    }
}
