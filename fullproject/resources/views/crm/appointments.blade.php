@extends('layouts.app')
@section('title', 'Appointments')
@section('content')
    <h1 class="text-2xl font-semibold mb-6">Appointments</h1>
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr><th class="px-4 py-2">Title</th><th class="px-4 py-2">Contact</th><th class="px-4 py-2">When</th><th class="px-4 py-2">Agent</th><th class="px-4 py-2">Status</th></tr>
            </thead>
            <tbody>
                @foreach($appointments as $a)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $a->title }}</td>
                        <td class="px-4 py-2">{{ $a->contact->name ?? $a->contact->phone }}</td>
                        <td class="px-4 py-2">{{ $a->scheduled_at->format('M d, H:i') }}</td>
                        <td class="px-4 py-2">{{ $a->agent->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ ucfirst($a->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $appointments->links() }}</div>
@endsection
