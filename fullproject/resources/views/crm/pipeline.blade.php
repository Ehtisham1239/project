@extends('layouts.app')
@section('title', 'CRM Pipeline')
@section('content')
    <h1 class="text-2xl font-semibold mb-6">Lead Pipeline</h1>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        @foreach($stages as $stage)
            <div class="bg-white rounded-lg border shadow-sm">
                <div class="px-3 py-2 border-b font-medium text-sm flex justify-between">
                    {{ $stage->name }} <span class="text-gray-400">{{ $stage->leads->count() }}</span>
                </div>
                <div class="p-2 space-y-2 min-h-[10rem]">
                    @foreach($stage->leads as $lead)
                        <div class="border rounded p-2 text-xs bg-gray-50">
                            <div class="font-medium">{{ $lead->title }}</div>
                            <div class="text-gray-500">{{ $lead->contact->name ?? $lead->contact->phone }}</div>
                            @if($lead->value)
                                <div class="text-gray-700 mt-1">PKR {{ number_format($lead->value) }}</div>
                            @endif
                            <div class="flex gap-2 mt-2">
                                <form method="POST" action="{{ route('crm.leads.summarize', $lead) }}">
                                    @csrf
                                    <button class="text-blue-600">AI Summary</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
