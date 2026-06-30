<?php

namespace App\Services\Integrations;

use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GoHighLevel (GHL) integration.
 *
 * Required .env values:
 *   GHL_API_BASE (e.g. https://services.leadconnectorhq.com)
 *   GHL_API_KEY or GHL_OAUTH_TOKEN (per your GHL app's auth method)
 *   GHL_LOCATION_ID
 *
 * NOTE: GHL has two API generations (v1 API key, v2 OAuth). Confirm with the client
 * which their account uses before wiring real credentials — the request shapes differ.
 * This class targets the v2 REST pattern; adjust endpoints/headers per your GHL app.
 */
class GoHighLevelService
{
    protected string $baseUrl;
    protected string $token;
    protected string $locationId;

    public function __construct()
    {
        $this->baseUrl = config('services.ghl.api_base');
        $this->token = config('services.ghl.token');
        $this->locationId = config('services.ghl.location_id');
    }

    protected function client()
    {
        return Http::withToken($this->token)
            ->withHeaders(['Version' => '2021-07-28'])
            ->baseUrl($this->baseUrl);
    }

    /** Push a local contact to GHL as a new/updated contact. */
    public function syncContactOut(Contact $contact): array
    {
        $response = $this->client()->post('/contacts/', [
            'locationId' => $this->locationId,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
        ]);

        if ($response->failed()) {
            Log::error('GHL contact sync failed', ['body' => $response->json()]);
        }

        return $response->json() ?? [];
    }

    /** Push a local lead to GHL as an opportunity. */
    public function syncLeadOut(Lead $lead): array
    {
        $response = $this->client()->post('/opportunities/', [
            'locationId' => $this->locationId,
            'name' => $lead->title,
            'monetaryValue' => $lead->value,
            'status' => 'open',
        ]);

        if ($response->failed()) {
            Log::error('GHL lead sync failed', ['body' => $response->json()]);
        }

        return $response->json() ?? [];
    }

    /**
     * Pull contacts updated in GHL since a given timestamp.
     * Intended to be called from a scheduled job — see the Schedule::call() or
     * Schedule::command() registration pattern in routes/console.php.
     */
    public function pullUpdatedContacts(\DateTimeInterface $since): array
    {
        $response = $this->client()->get('/contacts/', [
            'locationId' => $this->locationId,
            'startAfter' => $since->getTimestamp() * 1000,
        ]);

        return $response->json('contacts', []);
    }
}
