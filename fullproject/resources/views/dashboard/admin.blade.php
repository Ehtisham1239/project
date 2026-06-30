@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
    <h1 class="text-2xl font-semibold mb-2">Welcome, {{ $user->name }}</h1>
    <p class="text-gray-600 mb-6">Administrator Dashboard</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-lg shadow-sm border">
            <div class="text-sm text-gray-500">Total Users</div>
            <div class="text-2xl font-bold">{{ \App\Models\User::count() }}</div>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border">
            <div class="text-sm text-gray-500">Active Roles</div>
            <div class="text-2xl font-bold">{{ \App\Models\Role::count() }}</div>
        </div>
        <div class="bg-white p-5 rounded-lg shadow-sm border">
            <div class="text-sm text-gray-500">Permissions</div>
            <div class="text-2xl font-bold">{{ \App\Models\Permission::count() }}</div>
        </div>
    </div>

    <div class="mt-8 text-sm text-gray-500">
        Modules for WhatsApp Inbox, CRM, Reports, and Voice will appear here as later milestones are delivered.
    </div>
@endsection
