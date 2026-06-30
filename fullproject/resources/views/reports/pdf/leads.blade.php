<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>
body{font-family:sans-serif;font-size:12px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ddd;padding:6px;text-align:left}
th{background:#f3f4f6}
</style></head>
<body>
<h2>Leads Report ({{ $from }} to {{ $to }})</h2>
<table>
<thead><tr><th>Title</th><th>Contact</th><th>Stage</th><th>Owner</th><th>Value</th></tr></thead>
<tbody>
@foreach($leads as $lead)
<tr>
<td>{{ $lead->title }}</td>
<td>{{ $lead->contact->name ?? $lead->contact->phone }}</td>
<td>{{ $lead->stage->name ?? '' }}</td>
<td>{{ $lead->owner->name ?? 'Unassigned' }}</td>
<td>{{ $lead->value ? number_format($lead->value) : '' }}</td>
</tr>
@endforeach
</tbody>
</table>
</body>
</html>
