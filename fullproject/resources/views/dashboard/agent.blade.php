@extends('layouts.app')
@section('title', 'Agent Dashboard')
@section('content')
    <h1 class="text-2xl font-semibold mb-2">Welcome, {{ $user->name }}</h1>
    <p class="text-gray-600 mb-6">Agent Dashboard</p>

    <div class="bg-white p-6 rounded-lg shadow-sm border text-gray-600 text-sm">
        Your assigned conversations and leads will appear here once the WhatsApp Inbox (Milestone 2) is live.
    </div>
@endsection
