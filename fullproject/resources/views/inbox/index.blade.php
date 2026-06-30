@extends('layouts.app')
@section('title', 'Shared Inbox')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Shared Inbox</h1>
        <div class="flex gap-2 text-sm">
            @foreach(['open','pending','resolved','closed'] as $status)
                <a href="{{ request()->fullUrlWithQuery(['status' => $status]) }}"
                   class="px-3 py-1 rounded border {{ request('status') === $status ? 'bg-blue-600 text-white' : 'bg-white' }}">
                    {{ ucfirst($status) }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border divide-y">
        @forelse($conversations as $conv)
            <a href="{{ route('inbox.show', $conv) }}" class="flex justify-between items-center px-4 py-3 hover:bg-gray-50">
                <div>
                    <div class="font-medium {{ $conv->unread ? 'font-bold' : '' }}">{{ $conv->contact->name ?? $conv->contact->phone }}</div>
                    <div class="text-xs text-gray-500">{{ $conv->contact->phone }} · {{ $conv->assignedAgent->name ?? 'Unassigned' }}</div>
                </div>
                <div class="text-xs text-gray-400">{{ $conv->last_message_at?->diffForHumans() }}</div>
            </a>
        @empty
            <div class="px-4 py-8 text-center text-gray-500 text-sm">No conversations yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $conversations->links() }}</div>
@endsection
