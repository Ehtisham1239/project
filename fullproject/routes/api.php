<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// WhatsApp Cloud API webhook — Meta calls these directly, so they're outside the
// 'web' middleware group (no CSRF) but the controller verifies the signature itself.
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive']);

// Twilio voice status-callback webhook (Milestone 4) would be added here, e.g.:
// Route::post('/webhooks/voice/status', [VoiceWebhookController::class, 'status']);
