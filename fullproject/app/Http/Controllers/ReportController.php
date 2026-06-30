<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Uses barryvdh/laravel-dompdf for PDF and maatwebsite/excel for XLSX.
 * Install via:
 *   composer require barryvdh/laravel-dompdf maatwebsite/excel
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $stats = [
            'total_conversations' => Conversation::whereBetween('created_at', [$from, $to])->count(),
            'total_messages' => Message::whereBetween('created_at', [$from, $to])->count(),
            'leads_by_stage' => Lead::join('lead_stages', 'leads.lead_stage_id', '=', 'lead_stages.id')
                ->select('lead_stages.name', DB::raw('count(*) as total'))
                ->groupBy('lead_stages.name')
                ->get(),
            'avg_response_time_minutes' => $this->averageResponseTimeMinutes($from, $to),
        ];

        return view('reports.index', compact('stats', 'from', 'to'));
    }

    public function exportPdf(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $leads = Lead::with(['contact', 'stage'])->whereBetween('created_at', [$from, $to])->get();

        // Requires barryvdh/laravel-dompdf
        $pdf = app('dompdf.wrapper')->loadView('reports.pdf.leads', compact('leads', 'from', 'to'));

        AuditLog::record('report_exported', ['format' => 'pdf', 'from' => $from, 'to' => $to]);

        return $pdf->download("leads-report-{$from}-to-{$to}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        AuditLog::record('report_exported', ['format' => 'xlsx', 'from' => $from, 'to' => $to]);

        // Requires maatwebsite/excel; see app/Exports/LeadsExport.php
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LeadsExport($from, $to),
            "leads-report-{$from}-to-{$to}.xlsx"
        );
    }

    protected function averageResponseTimeMinutes(string $from, string $to): ?float
    {
        // Simplified: average gap between an inbound message and the next outbound reply.
        $avg = DB::table('messages as inbound')
            ->join('messages as outbound', function ($join) {
                $join->on('outbound.conversation_id', '=', 'inbound.conversation_id')
                    ->where('outbound.direction', '=', 'outbound')
                    ->whereColumn('outbound.created_at', '>', 'inbound.created_at');
            })
            ->where('inbound.direction', 'inbound')
            ->whereBetween('inbound.created_at', [$from, $to])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, inbound.created_at, outbound.created_at)) as avg_minutes')
            ->value('avg_minutes');

        return $avg ? round((float) $avg, 1) : null;
    }
}
