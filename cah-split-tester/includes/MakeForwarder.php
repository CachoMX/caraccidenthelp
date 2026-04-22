<?php

declare(strict_types=1);

namespace VIXI\CahSplit;

use VIXI\CahSplit\Repositories\LeadsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class MakeForwarder
{
    public const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly Settings $settings,
        private readonly LeadsRepository $leads,
    ) {
    }

    public function forward(int $leadId, array $makePayload, bool $blocking = false): bool
    {
        $url = $this->settings->makeWebhookUrl();
        if ($url === '') {
            $this->leads->markForwardFailed($leadId, 'No Make.com webhook URL configured.');
            return false;
        }

        $payload = $this->stampLeadId($makePayload, $leadId);

        $response = \wp_remote_post($url, [
            'method'   => 'POST',
            'timeout'  => $blocking ? 10 : 5,
            'blocking' => $blocking,
            'headers'  => ['Content-Type' => 'application/json'],
            'body'     => \wp_json_encode($payload),
        ]);

        if (!$blocking) {
            return true;
        }

        if (\is_wp_error($response)) {
            $this->leads->markForwardFailed($leadId, $response->get_error_message());
            \error_log(\sprintf('[cah-split] Make forward failed for lead %d: %s', $leadId, $response->get_error_message()));
            return false;
        }

        $code = (int) \wp_remote_retrieve_response_code($response);
        $body = (string) \wp_remote_retrieve_body($response);

        if ($code >= 200 && $code < 300) {
            $this->leads->markForwardSuccess($leadId, $body !== '' ? \substr($body, 0, 5000) : null);
            return true;
        }

        $this->leads->markForwardFailed($leadId, \sprintf('HTTP %d: %s', $code, \substr($body, 0, 4000)));
        \error_log(\sprintf('[cah-split] Make forward non-2xx for lead %d: HTTP %d', $leadId, $code));
        return false;
    }

    public function retryPending(): void
    {
        $rows = $this->leads->findRetryable(self::MAX_ATTEMPTS);
        foreach ($rows as $row) {
            $raw = $row['raw_payload'] ?? null;
            if (!\is_string($raw) || $raw === '') {
                $this->leads->markForwardFailed((int) $row['id'], 'Raw payload missing; cannot retry.');
                continue;
            }
            $decoded = \json_decode($raw, true);
            if (!\is_array($decoded)) {
                $this->leads->markForwardFailed((int) $row['id'], 'Raw payload could not be decoded.');
                continue;
            }
            $makePayload = $decoded['make_payload'] ?? null;
            if (!\is_array($makePayload)) {
                $this->leads->markForwardFailed((int) $row['id'], 'Raw payload missing make_payload section.');
                continue;
            }
            $this->forward((int) $row['id'], $makePayload, true);
        }
    }

    private function stampLeadId(array $makePayload, int $leadId): array
    {
        if (!isset($makePayload[0]) || !\is_array($makePayload[0])) {
            return $makePayload;
        }
        if (!isset($makePayload[0]['form_meta']) || !\is_array($makePayload[0]['form_meta'])) {
            $makePayload[0]['form_meta'] = [];
        }
        $makePayload[0]['form_meta']['cah_lead_id'] = $leadId;
        return $makePayload;
    }
}
