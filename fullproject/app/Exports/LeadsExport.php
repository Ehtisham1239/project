<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected string $from, protected string $to) {}

    public function collection()
    {
        return Lead::with(['contact', 'stage', 'owner'])
            ->whereBetween('created_at', [$this->from, $this->to])
            ->get();
    }

    public function headings(): array
    {
        return ['ID', 'Title', 'Contact', 'Phone', 'Stage', 'Owner', 'Value', 'Source', 'Created'];
    }

    public function map($lead): array
    {
        return [
            $lead->id,
            $lead->title,
            $lead->contact->name,
            $lead->contact->phone,
            $lead->stage->name ?? '',
            $lead->owner->name ?? 'Unassigned',
            $lead->value,
            $lead->source,
            $lead->created_at->toDateString(),
        ];
    }
}
