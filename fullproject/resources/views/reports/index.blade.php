@extends('layouts.app')
@section('title', 'Reports')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Reports</h1>
        <div class="flex gap-2">
            <a href="{{ route('reports.export.pdf', request()->query()) }}" class="text-sm bg-red-600 text-white px-3 py-1.5 rounded">Export PDF</a>
            <a href="{{ route('reports.export.excel', request()->query()) }}" class="text-sm bg-green-600 text-white px-3 py-1.5 rounded">Export Excel</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg border"><div class="text-xs text-gray-500">Conversations</div><div class="text-xl font-bold">{{ $stats['total_conversations'] }}</div></div>
        <div class="bg-white p-4 rounded-lg border"><div class="text-xs text-gray-500">Messages</div><div class="text-xl font-bold">{{ $stats['total_messages'] }}</div></div>
        <div class="bg-white p-4 rounded-lg border"><div class="text-xs text-gray-500">Avg Response (min)</div><div class="text-xl font-bold">{{ $stats['avg_response_time_minutes'] ?? '—' }}</div></div>
    </div>

    <div class="bg-white rounded-lg border p-4">
        <h2 class="font-medium mb-3">Leads by Stage</h2>
        @foreach($stats['leads_by_stage'] as $row)
            <div class="flex justify-between text-sm py-1 border-b last:border-0">
                <span>{{ $row->name }}</span><span>{{ $row->total }}</span>
            </div>
        @endforeach
    </div>
@endsection
