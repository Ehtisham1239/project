<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\ConversationNote;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function __construct(protected WhatsAppCloudApiService $whatsapp) {}

    public function index(Request $request)
    {
        $query = Conversation::with(['contact', 'assignedAgent'])->latest('last_message_at');

        // Agents only see their own + unassigned by default; managers/admins see all.
        if ($request->user()->hasRole('agent')) {
            $query->where(function ($q) use ($request) {
                $q->where('assigned_to', $request->user()->id)->orWhereNull('assigned_to');
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $conversations = $query->paginate(25);
        $agents = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['agent', 'manager']))->get();

        return view('inbox.index', compact('conversations', 'agents'));
    }

    public function show(Conversation $conversation)
    {
        $conversation->load(['messages', 'notes.user', 'contact', 'assignedAgent']);
        $conversation->update(['unread' => false]);

        $templates = MessageTemplate::where('approval_status', 'approved')->get();
        $agents = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['agent', 'manager']))->get();

        return view('inbox.show', compact('conversation', 'templates', 'agents'));
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:4096'],
        ]);

        $result = $this->whatsapp->sendTextMessage($conversation->contact->phone, $data['body']);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $data['body'],
            'wa_message_id' => $result['messages'][0]['id'] ?? null,
            'status' => isset($result['messages']) ? 'sent' : 'failed',
            'raw_payload' => $result,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back();
    }

    public function sendTemplate(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'template_id' => ['required', 'exists:message_templates,id'],
        ]);

        $template = MessageTemplate::findOrFail($data['template_id']);

        $result = $this->whatsapp->sendTemplateMessage(
            $conversation->contact->phone,
            $template->name,
            $template->language
        );

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'direction' => 'outbound',
            'type' => 'template',
            'body' => $template->body,
            'wa_message_id' => $result['messages'][0]['id'] ?? null,
            'status' => isset($result['messages']) ? 'sent' : 'failed',
            'raw_payload' => $result,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back();
    }

    public function addNote(Request $request, Conversation $conversation)
    {
        $data = $request->validate(['body' => ['required', 'string']]);

        ConversationNote::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back();
    }

    public function assign(Request $request, Conversation $conversation)
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);

        $conversation->assignTo(User::findOrFail($data['user_id']));

        return back();
    }

    public function updateStatus(Request $request, Conversation $conversation)
    {
        $data = $request->validate(['status' => ['required', 'in:open,pending,resolved,closed']]);

        $conversation->update(['status' => $data['status']]);
        AuditLog::record('conversation_status_changed', $data, $conversation);

        return back();
    }
}
