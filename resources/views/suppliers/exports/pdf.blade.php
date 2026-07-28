{{-- resources/views/inventory/suppliers/exports/pdf.blade.php --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Suppliers Export</title>
    <style>
        body{ font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
        .title{ font-size:18px; font-weight:700; margin:0 0 6px 0; }
        .meta{ font-size:11px; color:#555; margin:0 0 12px 0; }
        .filters{ background:#f7f7f7; border:1px solid #ddd; padding:10px; margin:0 0 12px 0; }
        .filters h4{ margin:0 0 6px 0; font-size:12px; }
        .filters .row{ margin:2px 0; }
        table{ width:100%; border-collapse: collapse; }
        th, td{ border:1px solid #ddd; padding:6px 6px; vertical-align: top; }
        th{ background:#efefef; text-align:left; font-weight:700; }
        .small{ font-size:10px; color:#666; }
        .right{ text-align:right; }
    </style>
</head>
<body>

    <div class="title">Suppliers Export</div>
    <div class="meta">
        Generated: {{ now()->format('Y-m-d H:i:s') }}
        <span class="small"> | Records: {{ $rows->count() }}</span>
    </div>

    @php
        $filters = $filters ?? [];
        $hasAnyFilter = collect($filters)->filter(fn($v)=> $v !== null && $v !== '')->isNotEmpty();
    @endphp

    @if($hasAnyFilter)
        <div class="filters">
            <h4>Applied Filters</h4>
            @foreach($filters as $k => $v)
                @if($v !== null && $v !== '')
                    <div class="row"><strong>{{ ucwords(str_replace('_',' ', $k)) }}:</strong> {{ $v }}</div>
                @endif
            @endforeach
        </div>
    @endif

    <table>
        <thead>
        <tr>
            <th style="width:6%;">ID</th>
            <th style="width:22%;">Name</th>
            <th style="width:10%;">Status</th>
            <th style="width:10%;">Currency</th>
            <th style="width:22%;">Payment Terms</th>
            <th style="width:10%;">Lead Time</th>
            <th style="width:10%;">Rating</th>
            <th style="width:10%;">Created</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $s)
            <tr>
                <td class="right">{{ $s->id }}</td>
                <td>{{ $s->name }}</td>
                <td>{{ $s->status }}</td>
                <td>{{ $s->default_currency }}</td>
                <td>{{ $s->payment_terms }}</td>
                <td class="right">{{ $s->lead_time_days }}</td>
                <td class="right">{{ is_null($s->rating) ? '' : number_format((float)$s->rating, 1) }}</td>
                <td>{{ optional($s->created_at)->format('Y-m-d') }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No records found.</td></tr>
        @endforelse
        </tbody>
    </table>

</body>
</html>
