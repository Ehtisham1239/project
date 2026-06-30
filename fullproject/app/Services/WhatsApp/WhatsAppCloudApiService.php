<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client around Meta's WhatsApp Cloud API.
 *
 * Required .env values:
 *   WHATSAPP_PHONE_NUMBER_ID
 *   WHATSAPP_BUSINESS_ACCOUNT_ID
 *   WHATSAPP_ACCESS_TOKEN
 *   WHATSAPP_API_VERSION (e.g. v20.0)
 *   WHATSAPP_VERIFY_TOKEN (for webhook handshake)
 *   WHATSAPP_APP_SECRET (for verifying webhook signatures)
 */
class WhatsAppCloudApiService
{
    protected string $baseUrl;
    protected string $token;
    protected string $phoneNumberId;

    public function __construct()
    {
        $version = config('services.whatsapp.api_version', 'v20.0');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->token = config('services.whatsapp.access_token');
        $this->baseUrl = "https://graph.facebook.com/{$version}/{$this->phoneNumberId}";
    }

    protected function client()
    {
        return Http::withToken($this->token)->baseUrl($this->baseUrl);
    }

    public function sendTextMessage(string $to, string $body): array
    {
        $response = $this->client()->post('/messages', [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ]);

        return $this->handleResponse($response, 'sendTextMessage');
    }

    public function sendTemplateMessage(string $to, string $templateName, string $language, array $components = []): array
    {
        $response = $this->client()->post('/messages', [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);

        return $this->handleResponse($response, 'sendTemplateMessage');
    }

    public function sendMediaMessage(string $to, string $type, string $mediaUrlOrId, bool $isId = false): array
    {
        $key = $isId ? 'id' : 'link';

        $response = $this->client()->post('/messages', [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => $type, // image, document, audio, video
            $type => [$key => $mediaUrlOrId],
        ]);

        return $this->handleResponse($response, 'sendMediaMessage');
    }

    public function markAsRead(string $waMessageId): array
    {
        $response = $this->client()->post('/messages', [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $waMessageId,
        ]);

        return $this->handleResponse($response, 'markAsRead');
    }

    /**
     * Verifies the X-Hub-Signature-256 header Meta sends on every webhook POST.
     */
    public function verifySignature(string $payload, ?string $signatureHeader): bool
    {
        if (! $signatureHeader) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, config('services.whatsapp.app_secret'));

        return hash_equals($expected, $signatureHeader);
    }

    protected function handleResponse($response, string $action): array
    {
        if ($response->failed()) {
            Log::error("WhatsApp API error during {$action}", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }

        return $response->json() ?? [];
    }
}
