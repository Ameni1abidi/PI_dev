<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

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
     */
    public function sendEvaluationNotification(array $toPhones, string $subject, string $body): array
    {
        $recipients = array_values(array_filter(array_unique(array_map('trim', $toPhones))));
        if ($recipients === []) {
            return [
                'sent' => false,
                'message' => 'Aucun destinataire.',
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

        $normalizePhone = static fn (string $value): string => preg_replace('/^whatsapp:/', '', trim($value)) ?? trim($value);

        foreach ($recipients as $phone) {
            $rawPhone = $normalizePhone($phone);

            $response = $this->httpClient->request(
                'POST',
                sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode($this->twilioAccountSid)),
                [
                    'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                    'body' => [
                        'To' => $rawPhone,
                        'From' => $this->twilioFromNumber,
                        'Body' => $smsBody,
                    ],
                ]
            );
            $smsStatusCodes[] = $response->getStatusCode();

            if ($this->twilioWhatsAppFrom !== '') {
                $waResponse = $this->httpClient->request(
                    'POST',
                    sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode($this->twilioAccountSid)),
                    [
                        'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                        'body' => [
                            'To' => 'whatsapp:' . $rawPhone,
                            'From' => $this->twilioWhatsAppFrom,
                            'Body' => $smsBody,
                        ],
                    ]
                );
                $whatsAppStatusCodes[] = $waResponse->getStatusCode();
            }
        }

        $smsSuccess = $smsStatusCodes !== [] && count(array_filter($smsStatusCodes, static fn (int $code): bool => $code >= 200 && $code < 300)) === count($smsStatusCodes);
        $waConfigured = $this->twilioWhatsAppFrom !== '';
        $waSuccess = !$waConfigured || (
            $whatsAppStatusCodes !== [] &&
            count(array_filter($whatsAppStatusCodes, static fn (int $code): bool => $code >= 200 && $code < 300)) === count($whatsAppStatusCodes)
        );
        $allSuccess = $smsSuccess && $waSuccess;

        $message = $allSuccess
            ? ($waConfigured ? 'SMS et WhatsApp envoyes.' : 'SMS envoye.')
            : ($waConfigured ? 'Echec envoi Twilio (SMS/WhatsApp).' : 'Echec envoi Twilio.');

        return [
            'sent' => $allSuccess,
            'message' => $message,
            'status_code' => $smsStatusCodes[0] ?? null,
            'status_codes' => [
                'sms' => $smsStatusCodes,
                'whatsapp' => $whatsAppStatusCodes,
            ],
            'channel' => $waConfigured ? 'sms+whatsapp' : 'sms',
        ];
    }
}
