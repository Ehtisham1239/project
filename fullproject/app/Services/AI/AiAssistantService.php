<?php

namespace App\Services\AI;

use App\Models\AiChatLog;
use App\Models\Conversation;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the Anthropic Messages API to power:
 *  - the auto-reply chatbot
 *  - agent "suggested reply" assistance
 *  - conversation summaries
 *
 * Required .env values:
 *   ANTHROPIC_API_KEY
 *   ANTHROPIC_MODEL (e.g. claude-sonnet-4-6)
 */
class AiAssistantService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->model = config('services.anthropic.model', 'claude-sonnet-4-6');
    }

    /**
     * Builds a knowledge-base-grounded system prompt so the bot only answers
     * from approved company content rather than hallucinating policy details.
     */
    protected function buildSystemPrompt(): string
    {
        $articles = KnowledgeBaseArticle::where('is_published', true)->get();

        $knowledge = $articles->map(fn ($a) => "### {$a->title}\n{$a->content}")->implode("\n\n");

        return <<<PROMPT
        You are a helpful customer support assistant for this business, replying over WhatsApp.
        Answer only using the knowledge base content below. If the answer isn't in the knowledge
        base, say you'll have a team member follow up — never invent policies, prices, or facts.
        Keep replies short and conversational, suitable for a WhatsApp message.

        KNOWLEDGE BASE:
        {$knowledge}
        PROMPT;
    }

    /**
     * Generates an automatic chatbot reply to an inbound message.
     * Caller decides whether to auto-send or hold for agent approval.
     */
    public function generateChatbotReply(Conversation $conversation, string $inboundMessage): string
    {
        $history = $conversation->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => [
                'role' => $m->direction === 'inbound' ? 'user' : 'assistant',
                'content' => $m->body,
            ])
            ->values()
            ->toArray();

        $response = $this->callClaude($this->buildSystemPrompt(), $history, $inboundMessage);

        AiChatLog::create([
            'conversation_id' => $conversation->id,
            'prompt' => $inboundMessage,
            'response' => $response,
            'was_auto_sent' => false,
        ]);

        return $response;
    }

    /**
     * Same as above, but framed as a suggestion the agent can edit before sending.
     */
    public function suggestReply(Conversation $conversation): string
    {
        $lastInbound = $conversation->messages()->where('direction', 'inbound')->latest()->first();

        if (! $lastInbound) {
            return '';
        }

        return $this->generateChatbotReply($conversation, $lastInbound->body ?? '');
    }

    public function summarizeConversation(Conversation $conversation): string
    {
        $transcript = $conversation->messages->map(
            fn ($m) => ($m->direction === 'inbound' ? 'Customer: ' : 'Agent: ').$m->body
        )->implode("\n");

        $system = 'Summarize the following WhatsApp support conversation in 2-3 sentences for an internal CRM note. Focus on the customer\'s need and current status.';

        return $this->callClaude($system, [], $transcript);
    }

    protected function callClaude(string $system, array $history, string $userMessage): string
    {
        $messages = array_merge($history, [
            ['role' => 'user', 'content' => $userMessage],
        ]);

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 500,
            'system' => $system,
            'messages' => $messages,
        ]);

        if ($response->failed()) {
            Log::error('Anthropic API error', ['body' => $response->json()]);
            return "Sorry, I'm unable to generate a response right now.";
        }

        $blocks = $response->json('content', []);

        return collect($blocks)->where('type', 'text')->pluck('text')->implode("\n");
    }
}
