<?php

namespace App\Services\Integrations;

use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ERP integration adapter.
 *
 * IMPORTANT: the spec says "ERP integration" without naming the specific ERP
 * (SAP, Odoo, Oracle NetSuite, a custom in-house system, etc). Each has a very
 * different API shape and auth model, so this class is written as a generic
 * REST adapter you point at whatever ERP endpoint the client confirms.
 *
 * Required .env values:
 *   ERP_API_BASE
 *   ERP_API_KEY
 *
 * ACTION NEEDED: confirm with the client which ERP system this targets, then
 * adjust the endpoint paths and payload shape below to match its actual API docs.
 */
class ErpIntegrationService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.erp.api_base');
        $this->apiKey = config('services.erp.api_key');
    }

    protected function client()
    {
        return Http::withToken($this->apiKey)->baseUrl($this->baseUrl);
    }

    /** Push a won lead into the ERP as a new customer/order record. */
    public function pushWonLead(Lead $lead): array
    {
        $response = $this->client()->post('/customers', [
            'name' => $lead->contact->name,
            'phone' => $lead->contact->phone,
            'email' => $lead->contact->email,
            'deal_value' => $lead->value,
            'source' => 'crm',
        ]);

        if ($response->failed()) {
            Log::error('ERP push failed', ['body' => $response->json()]);
        }

        return $response->json() ?? [];
    }

    /** Pull product/inventory data the CRM might reference (e.g. in quotes). */
    public function fetchProducts(): array
    {
        $response = $this->client()->get('/products');

        return $response->json('data', []);
    }
}
