<?php

namespace App\Services\WhatsApp;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\AiAssistantService;
use App\Services\Automation\WorkflowEngine;
use App\Services\WhatsApp\WhatsAppCloudApiService;

class WhatsAppWebhookHandler
{
    public function __construct(
        protected WorkflowEngine $workflows,
        protected AiAssistantService $ai,
        protected WhatsAppCloudApiService $whatsapp,
    ) {}

    /**
     * Meta sends a top-level payload with "entry" -> "changes" -> "value".
     * value.messages[] = inbound messages
     * value.statuses[] = delivery/read receipts for our outbound messages
     */
    public function handle(array $payload): void
    {
        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                $value = data_get($change, 'value', []);

                foreach ($value['messages'] ?? [] as $waMessage) {
                    $this->ingestInboundMessage($value, $waMessage);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->updateMessageStatus($status);
                }
            }
        }
    }

    protected function ingestInboundMessage(array $value, array $waMessage): void
    {
        $waPhone = $waMessage['from'];
        $profileName = data_get($value, 'contacts.0.profile.name');

        $contact = Contact::firstOrCreate(
            ['phone' => $waPhone],
            ['name' => $profileName, 'source' => 'whatsapp']
        );
        $contact->update(['last_contacted_at' => now()]);

        $conversation = Conversation::firstOrCreate(
            ['contact_id' => $contact->id, 'status' => 'open'],
            ['unread' => true]
        );

        [$type, $body, $mediaUrl, $mediaMime] = $this->extractContent($waMessage);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'direction' => 'inbound',
            'type' => $type,
            'body' => $body,
            'media_url' => $mediaUrl,
            'media_mime_type' => $mediaMime,
            'wa_message_id' => $waMessage['id'],
            'status' => 'delivered',
            'raw_payload' => $waMessage,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread' => true,
            'status' => 'open',
        ]);

        // Fire any workflows listening for inbound messages (e.g. auto-create lead, auto-assign).
        $this->workflows->fire('message_received', ['conversation' => $conversation]);

        // Optional AI chatbot auto-reply. Only fires for text messages, and only if no human
        // agent has claimed the conversation yet, so we don't talk over a live agent.
        if ($type === 'text' && ! $conversation->assigned_to && config('services.ai.auto_reply_enabled', false)) {
            $reply = $this->ai->generateChatbotReply($conversation, $body ?? '');

            $result = $this->whatsapp->sendTextMessage($contact->phone, $reply);

            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => null,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $reply,
                'wa_message_id' => $result['messages'][0]['id'] ?? null,
                'status' => isset($result['messages']) ? 'sent' : 'failed',
                'raw_payload' => $result,
            ]);
        }
    }

    protected function extractContent(array $waMessage): array
    {
        $type = $waMessage['type'] ?? 'text';

        return match ($type) {
            'text' => ['text', data_get($waMessage, 'text.body'), null, null],
            'image' => ['image', data_get($waMessage, 'image.caption'), data_get($waMessage, 'image.id'), data_get($waMessage, 'image.mime_type')],
            'document' => ['document', data_get($waMessage, 'document.caption'), data_get($waMessage, 'document.id'), data_get($waMessage, 'document.mime_type')],
            'audio' => ['audio', null, data_get($waMessage, 'audio.id'), data_get($waMessage, 'audio.mime_type')],
            'video' => ['video', data_get($waMessage, 'video.caption'), data_get($waMessage, 'video.id'), data_get($waMessage, 'video.mime_type')],
            'location' => ['location', json_encode(data_get($waMessage, 'location')), null, null],
            default => ['text', '[unsupported message type: '.$type.']', null, null],
        };
    }

    protected function updateMessageStatus(array $status): void
    {
        $message = Message::where('wa_message_id', $status['id'])->first();

        if (! $message) {
            return;
        }

        $message->update(['status' => $status['status']]); // sent, delivered, read, failed
    }
}
