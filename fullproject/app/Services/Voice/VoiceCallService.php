<?php

namespace App\Services\Voice;

use App\Models\VoiceCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Voice calling integration. The spec just says "Voice integration" without
 * naming a provider — this targets the common Twilio Voice REST pattern since
 * it's the most widely used for this kind of CRM click-to-call feature.
 * If the client uses a different provider, swap the endpoint/auth below;
 * the public methods (initiateCall, logIncomingCall) and DB schema stay the same.
 *
 * Required .env values:
 *   TWILIO_ACCOUNT_SID
 *   TWILIO_AUTH_TOKEN
 *   TWILIO_FROM_NUMBER
 */
class VoiceCallService
{
    protected string $accountSid;
    protected string $authToken;
    protected string $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.sid');
        $this->authToken = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from_number');
    }

    /** Agent clicks "Call" in the CRM; this places an outbound call via the provider. */
    public function initiateCall(string $toNumber, int $userId, int $contactId): VoiceCall
    {
        $response = Http::withBasicAuth($this->accountSid, $this->authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Calls.json", [
                'To' => $toNumber,
                'From' => $this->fromNumber,
                'Url' => route('voice.twiml'), // TwiML instructions endpoint, see VoiceController
            ]);

        if ($response->failed()) {
            Log::error('Twilio call initiation failed', ['body' => $response->json()]);
        }

        return VoiceCall::create([
            'contact_id' => $contactId,
            'user_id' => $userId,
            'direction' => 'outbound',
            'status' => $response->successful() ? 'completed' : 'failed',
            'provider_call_id' => $response->json('sid'),
            'raw_payload' => $response->json(),
        ]);
    }

    /** Called from the provider's status-callback webhook once a call ends. */
    public function logCallCompletion(array $webhookPayload): void
    {
        $call = VoiceCall::where('provider_call_id', $webhookPayload['CallSid'] ?? null)->first();

        if (! $call) {
            return;
        }

        $call->update([
            'status' => match ($webhookPayload['CallStatus'] ?? '') {
                'completed' => 'completed',
                'no-answer', 'busy' => 'missed',
                default => 'failed',
            },
            'duration_seconds' => $webhookPayload['CallDuration'] ?? null,
            'recording_url' => $webhookPayload['RecordingUrl'] ?? null,
        ]);
    }
}
