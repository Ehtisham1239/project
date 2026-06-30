@extends('layouts.app')
@section('title', 'Conversation')
@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border p-4 mb-4 flex justify-between items-center">
                <div>
                    <div class="font-semibold">{{ $conversation->contact->name ?? $conversation->contact->phone }}</div>
                    <div class="text-xs text-gray-500">{{ $conversation->contact->phone }}</div>
                </div>
                <form method="POST" action="{{ route('inbox.status', $conversation) }}">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
                        @foreach(['open','pending','resolved','closed'] as $s)
                            <option value="{{ $s }}" @selected($conversation->status === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-4 mb-4 space-y-3 max-h-[28rem] overflow-y-auto">
                @foreach($conversation->messages as $msg)
                    <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xs px-3 py-2 rounded-lg text-sm {{ $msg->direction === 'outbound' ? 'bg-blue-600 text-white' : 'bg-gray-100' }}">
                            {{ $msg->body }}
                            <div class="text-[10px] opacity-70 mt-1">{{ $msg->created_at->format('H:i') }} · {{ $msg->status }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('inbox.message', $conversation) }}" class="flex gap-2 mb-3">
                @csrf
                <input name="body" required placeholder="Type a message..." class="flex-1 border rounded px-3 py-2 text-sm">
                <button class="bg-blue-600 text-white rounded px-4 py-2 text-sm">Send</button>
            </form>

            <form method="POST" action="{{ route('inbox.template', $conversation) }}" class="flex gap-2">
                @csrf
                <select name="template_id" class="flex-1 border rounded px-3 py-2 text-sm">
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
                <button class="bg-gray-700 text-white rounded px-4 py-2 text-sm">Send Template</button>
            </form>
        </div>

        <div>
            <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                <h3 class="font-medium text-sm mb-2">Assign</h3>
                <form method="POST" action="{{ route('inbox.assign', $conversation) }}" class="flex gap-2">
                    @csrf
                    <select name="user_id" class="flex-1 border rounded px-2 py-1 text-xs">
                        @foreach($agents as $a)
                            <option value="{{ $a->id }}" @selected($conversation->assigned_to === $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                    <button class="text-xs text-blue-600">Assign</button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-4">
                <h3 class="font-medium text-sm mb-2">Internal Notes</h3>
                <form method="POST" action="{{ route('inbox.note', $conversation) }}" class="mb-3">
                    @csrf
                    <textarea name="body" required rows="2" class="w-full border rounded px-2 py-1 text-xs" placeholder="Add a private note..."></textarea>
                    <button class="text-xs text-blue-600 mt-1">Add Note</button>
                </form>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($conversation->notes as $note)
                        <div class="text-xs bg-yellow-50 border border-yellow-200 rounded p-2">
                            <div class="font-medium">{{ $note->user->name }}</div>
                            {{ $note->body }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
