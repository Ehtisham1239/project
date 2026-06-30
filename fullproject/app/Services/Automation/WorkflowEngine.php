<?php

namespace App\Services\Automation;

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\Workflow;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    public function __construct(protected WhatsAppCloudApiService $whatsapp) {}

    /**
     * Call this whenever a trigger event happens, e.g.:
     *   $engine->fire('message_received', ['conversation' => $conversation]);
     *   $engine->fire('lead_stage_changed', ['lead' => $lead, 'stage' => $newStage]);
     */
    public function fire(string $triggerEvent, array $context): void
    {
        $workflows = Workflow::where('trigger_event', $triggerEvent)
            ->where('is_active', true)
            ->with('actions')
            ->get();

        foreach ($workflows as $workflow) {
            if (! $this->conditionsMatch($workflow->trigger_conditions ?? [], $context)) {
                continue;
            }

            foreach ($workflow->actions as $action) {
                $this->runAction($action->action_type, $action->action_params ?? [], $context);
            }
        }
    }

    protected function conditionsMatch(array $conditions, array $context): bool
    {
        foreach ($conditions as $key => $expected) {
            $actual = data_get($context, $key);
            if ($actual != $expected) {
                return false;
            }
        }

        return true;
    }

    protected function runAction(string $type, array $params, array $context): void
    {
        try {
            match ($type) {
                'send_message' => $this->actionSendMessage($params, $context),
                'send_template' => $this->actionSendTemplate($params, $context),
                'assign_agent' => $this->actionAssignAgent($params, $context),
                'change_lead_stage' => $this->actionChangeLeadStage($params, $context),
                'create_lead' => $this->actionCreateLead($params, $context),
                'notify_internal' => $this->actionNotifyInternal($params, $context),
                'wait' => null, // handled via queued delay job in production; no-op placeholder here
                default => Log::warning("Unknown workflow action type: {$type}"),
            };
        } catch (\Throwable $e) {
            Log::error("Workflow action '{$type}' failed: ".$e->getMessage());
        }
    }

    protected function actionSendMessage(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;
        if (! $conversation) return;

        $this->whatsapp->sendTextMessage($conversation->contact->phone, $params['body'] ?? '');
    }

    protected function actionSendTemplate(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;
        if (! $conversation) return;

        $template = MessageTemplate::find($params['template_id'] ?? null);
        if (! $template) return;

        $this->whatsapp->sendTemplateMessage($conversation->contact->phone, $template->name, $template->language);
    }

    protected function actionAssignAgent(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;
        $user = User::find($params['user_id'] ?? null);
        if ($conversation && $user) {
            $conversation->assignTo($user);
        }
    }

    protected function actionChangeLeadStage(array $params, array $context): void
    {
        $lead = $context['lead'] ?? null;
        $stage = LeadStage::find($params['stage_id'] ?? null);
        if ($lead && $stage) {
            $lead->moveToStage($stage);
        }
    }

    protected function actionCreateLead(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;
        if (! $conversation) return;

        $defaultStage = LeadStage::orderBy('order')->first();

        Lead::firstOrCreate(
            ['contact_id' => $conversation->contact_id],
            [
                'lead_stage_id' => $defaultStage?->id,
                'title' => $params['title'] ?? 'New WhatsApp Lead',
                'source' => 'whatsapp',
            ]
        );
    }

    protected function actionNotifyInternal(array $params, array $context): void
    {
        // TODO: wire to your notification channel of choice (Slack, email, Laravel Notification).
        Log::info('Internal workflow notification', ['params' => $params, 'context_keys' => array_keys($context)]);
    }
}
