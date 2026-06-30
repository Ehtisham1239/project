<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Services\WhatsApp\WhatsAppWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $api,
        protected WhatsAppWebhookHandler $handler,
    ) {}

    /**
     * GET — Meta's one-time webhook verification handshake.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * POST — actual inbound message/status events.
     */
    public function receive(Request $request)
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $this->api->verifySignature($request->getContent(), $signature)) {
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_FORBIDDEN);
        }

        $this->handler->handle($request->all());

        return response()->json(['status' => 'ok']);
    }
}
