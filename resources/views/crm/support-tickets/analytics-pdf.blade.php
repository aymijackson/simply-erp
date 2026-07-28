<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Support Tickets Analytics</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .muted { color:#666; }
        table { width:100%; border-collapse: collapse; margin-top:10px; }
        th, td { border:1px solid #ddd; padding:6px; vertical-align: top; }
        th { background:#f3f3f3; }
        .kpi { width:100%; margin-top:10px; }
        .kpi td { border:none; padding:4px 8px; }
        .title { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>

<div class="title">Support Tickets Analytics Report</div>
<div class="muted">Generated: {{ $generated_at }}</div>

<table class="kpi">
    <tr>
        <td><strong>Total:</strong> {{ $kpis['total'] ?? '—' }}</td>
        <td><strong>Backlog:</strong> {{ $kpis['backlog'] ?? '—' }}</td>
        <td><strong>Avg First Response (mins):</strong> {{ $kpis['avg_first_response_mins'] ?? '—' }}</td>
        <td><strong>Avg Resolution (mins):</strong> {{ $kpis['avg_resolution_mins'] ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>Open:</strong> {{ $kpis['open'] ?? '—' }}</td>
        <td><strong>Pending:</strong> {{ $kpis['pending'] ?? '—' }}</td>
        <td><strong>Resolved:</strong> {{ $kpis['resolved'] ?? '—' }}</td>
        <td><strong>Closed:</strong> {{ $kpis['closed'] ?? '—' }}</td>
    </tr>
</table>

<div class="muted" style="margin-top:8px;">
    <strong>Filters:</strong>
    {{ json_encode($filters) }}
</div>

<table>
    <thead>
    <tr>
        <th>Ticket No</th>
        <th>Customer</th>
        <th>Subject</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Channel</th>
        <th>Category</th>
        <th>Assigned</th>
        <th>Created</th>
        <th>Resolved</th>
    </tr>
    </thead>
    <tbody>
    @foreach($tickets as $t)
        <tr>
            <td>{{ $t->ticket_no }}</td>
            <td>{{ $t->customer?->name ?? '—' }}</td>
            <td>{{ $t->subject }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ $t->priority }}</td>
            <td>{{ $t->channel ?? '—' }}</td>
            <td>{{ $t->category ?? '—' }}</td>
            <td>
                @if($t->assignee)
                    {{ trim(($t->assignee->first_name ?? '').' '.($t->assignee->last_name ?? '')) ?: '—' }}
                @else
                    —
                @endif
            </td>
            <td>{{ optional($t->created_at)->format('d-m-Y h:i a') }}</td>
            <td>{{ optional($t->resolved_at)->format('d-m-Y h:i a') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
