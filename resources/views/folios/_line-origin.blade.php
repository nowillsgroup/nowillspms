@if($transaction->consolidationTransfer)
  @php $sourceFolio = $transaction->consolidationTransfer->sourceFolio; @endphp
  <span class="tiny muted" data-consolidation-origin> · From {{ $sourceFolio?->folio_no ?? ('folio #'.$transaction->consolidationTransfer->source_folio_id) }} @if($sourceFolio) @if($sourceFolio->reservation_room_id) · stay #{{ $sourceFolio->reservation_room_id }} @if($sourceFolio->stay?->room?->room_no) (last assigned room {{ $sourceFolio->stay->room->room_no }}) @endif @if($sourceFolio->stay?->guest?->full_name) · {{ $sourceFolio->stay->guest->full_name }} @endif @else · reservation folio @endif @else · source folio unavailable @endif</span>
@endif
