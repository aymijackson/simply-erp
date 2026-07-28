 @php
  $status = strtolower($json['status'] ?? 'draft');

  $isDraft    = $status === 'draft';
  $isPosted   = $status === 'posted';
  $isReversed = $status === 'reversed';
  $isVoided   = $status === 'voided';
@endphp

<div class="btn-group btn-group-sm">
    <button class="btn btn-sm btn-info viewEntry" data-id="{{$json['id']}}">
        <i class="fas fa-eye"></i>
    </button>
  @if($isDraft)
    <button class="btn btn-outline-primary btn-edit-je" data-json='@json($json)' title="Edit">
      <i class="fas fa-edit"></i>
    </button>

    <button class="btn btn-outline-success btn-post-je" data-id="{{ $json['id'] }}" title="Post">
      <i class="fas fa-check"></i>
    </button>

    <button class="btn btn-outline-danger btn-del-je" data-id="{{ $json['id'] }}" title="Delete">
      <i class="fas fa-trash"></i>
    </button>

  @elseif($isPosted)
    <button class="btn btn-outline-warning btn-reverse-je" data-id="{{ $json['id'] }}" title="Reverse">
      <i class="fas fa-undo"></i>
    </button>

    {{-- Only keep this if your finance policy allows posted entries to be voided --}}
    <button class="btn btn-outline-secondary btn-void-je" data-id="{{ $json['id'] }}" title="Void">
      <i class="fas fa-ban"></i>
    </button>

  @elseif($isReversed || $isVoided)
    <button class="btn btn-outline-secondary" type="button" disabled title="No further actions">
      <i class="fas fa-ban"></i>
    </button>
  @else
    <button class="btn btn-outline-secondary" type="button" disabled title="Unknown status">
      <i class="fas fa-question-circle"></i>
    </button>
  @endif
</div>