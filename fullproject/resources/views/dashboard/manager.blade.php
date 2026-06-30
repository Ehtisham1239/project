@extends('layouts.app')
@section('title', 'Manager Dashboard')
@section('content')
    <h1 class="text-2xl font-semibold mb-2">Welcome, {{ $user->name }}</h1>
    <p class="text-gray-600 mb-6">Manager Dashboard</p>

    <div class="bg-white p-6 rounded-lg shadow-sm border text-gray-600 text-sm">
        Team inbox, lead pipeline, and reporting widgets will be added in Milestones 2–4.
    </div>
@endsection
