<div class="btn-group" role="group">
    @can('sales.quotes.view')
    <a href="{{ route('admin.sales.quotes.show', $r->id) }}" class="btn btn-sm btn-outline-primary" title="View">
        <i class="fas fa-eye"></i>
    </a>
    @endcan

    @can('sales.quotes.edit')
    @if(in_array($r->status, ['draft', 'won']))
    <a href="{{ route('admin.sales.quotes.edit', $r->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
        <i class="fas fa-edit"></i>
    </a>
    @endif
    @endcan

    @can('sales.quotes.send')
    @if($r->status === 'draft')
    <button type="button" class="btn btn-sm btn-outline-info" title="Send"
            onclick="doQuoteAction('send', {{ $r->id }}, 'Send this quote?', 'It will be marked as sent to the customer.', '#0dcaf0')">
        <i class="fas fa-paper-plane"></i>
    </button>
    @endif
    @endcan

    @can('sales.quotes.win')
    @if($r->status === 'sent')
    <button type="button" class="btn btn-sm btn-outline-success" title="Mark Won"
            onclick="doQuoteAction('win', {{ $r->id }}, 'Mark this quote as won?', 'A privileged user can then review it before converting.', '#28a745')">
        <i class="fas fa-trophy"></i>
    </button>
    @endif
    @endcan

    @can('sales.quotes.reject')
    @if($r->status === 'sent')
    <button type="button" class="btn btn-sm btn-outline-danger" title="Reject"
            onclick="doQuoteAction('reject', {{ $r->id }}, 'Reject this quote?', 'This cannot be undone.', '#dc3545')">
        <i class="fas fa-times"></i>
    </button>
    @endif
    @endcan

    @can('sales.quotes.expire')
    @if($r->status === 'sent')
    <button type="button" class="btn btn-sm btn-outline-dark" title="Mark Expired"
            onclick="doQuoteAction('expire', {{ $r->id }}, 'Mark this quote as expired?', 'This cannot be undone.', '#343a40')">
        <i class="fas fa-hourglass-end"></i>
    </button>
    @endif
    @endcan

    @can('sales.quotes.delete')
    @if($r->status === 'draft')
    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
            onclick="if(confirm('Delete this draft quote?')){ document.getElementById('deleteQuoteForm{{ $r->id }}').submit(); }">
        <i class="fas fa-trash"></i>
    </button>
    <form id="deleteQuoteForm{{ $r->id }}" action="{{ route('admin.sales.quotes.destroy', $r->id) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endif
    @endcan
</div>
