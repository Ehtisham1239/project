<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Contact;
use App\Services\AI\AiAssistantService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        $stages = LeadStage::orderBy('order')->with(['leads.contact', 'leads.owner'])->get();

        return view('crm.pipeline', compact('stages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'lead_stage_id' => ['required', 'exists:lead_stages,id'],
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric'],
        ]);

        $data['owner_id'] = $request->user()->id;
        Lead::create($data);

        return back()->with('status', 'Lead created.');
    }

    public function move(Request $request, Lead $lead)
    {
        $data = $request->validate(['lead_stage_id' => ['required', 'exists:lead_stages,id']]);

        $lead->moveToStage(LeadStage::findOrFail($data['lead_stage_id']));

        return back();
    }

    public function summarize(Lead $lead, AiAssistantService $ai)
    {
        $conversation = $lead->contact->conversations()->latest('last_message_at')->first();

        if (! $conversation) {
            return back()->with('status', 'No conversation history to summarize yet.');
        }

        $summary = $ai->summarizeConversation($conversation);
        $lead->update(['notes' => $summary]);

        return back()->with('status', 'AI summary added to lead notes.');
    }
}
