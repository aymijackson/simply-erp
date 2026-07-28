@php
    $id     = $payment->id;
    $status = strtolower($payment->status ?? 'draft');
@endphp

<div class="btn-group btn-group-sm" role="group">

    @can('sales.payments.view')
        <a href="{{ route('admin.sales.payments.show', $id) }}" class="btn btn-outline-dark" title="View">
            <i class="fas fa-eye"></i>
        </a>
    @endcan

    @can('sales.payments.edit')
        @if($status === 'draft')
            <a href="{{ route('admin.sales.payments.edit', $id) }}" class="btn btn-outline-primary" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
        @endif
    @endcan

    @can('sales.payments.allocate')
        <a href="{{ route('admin.sales.payments.allocate', $id) }}" class="btn btn-outline-info" title="Allocate">
            <i class="fas fa-random"></i>
        </a>
    @endcan

    @can('sales.payments.post')
        @if($status === 'draft')
            <button type="button"
                    class="btn btn-outline-success js-post-payment"
                    data-id="{{ $id }}"
                    title="Post">
                <i class="fas fa-check"></i>
            </button>
        @endif
    @endcan

    @can('sales.payments.void')
        @if($status === 'posted')
            <button type="button"
                    class="btn btn-outline-warning js-void-payment"
                    data-id="{{ $id }}"
                    title="Void">
                <i class="fas fa-ban"></i>
            </button>
        @endif
    @endcan

    @can('sales.payments.delete')
        @if($status === 'draft')
            <button type="button"
                    class="btn btn-outline-danger js-delete-payment"
                    data-id="{{ $id }}"
                    title="Delete">
                <i class="fas fa-trash"></i>
            </button>
        @endif
    @endcan

</div>
