@extends('layouts.print')
@section('title')Statement {{ $folio->folio_no }}@endsection
@section('backurl'){{ route('folios.show', $folio) }}@endsection
@section('pageclass', 'folio-statement')
@section('doctitle')STATEMENT OF ACCOUNT@endsection
@section('docmeta')<div class="small statement-docmeta">
  Folio no.: <b id="docNo">{{ $folio->folio_no }}</b><br>
  Date: <time datetime="{{ $printedAt->toDateString() }}">{{ $printedAt->format('d M Y') }}</time><br>
  Settlement: <span id="settlementStatus" class="badge badge-{{ $settlementTone }}">{{ $settlementLabel }}</span><br>
  <span class="statement-copy-label">Guest copy</span>
  <div class="statement-header-balance statement-header-balance--{{ $settlementTone }}"><span>{{ $balanceLabel }}</span><strong>{{ money(abs($folioBalance)) }}</strong></div>
</div>@endsection
@push('head')
<style>
  .print-page.folio-statement { position:relative; max-width: 1020px; padding: 2.15rem 2.35rem; }
  .folio-statement .print-head { align-items: flex-start; border-bottom: 3px solid #166534; padding-bottom: 1rem; margin-bottom: 1.15rem; }
  .folio-statement .print-head h2 { color: #14532d; letter-spacing: -.025em; }
  .folio-statement .print-head .right h2 { font-size: 1.25rem; line-height: 1.2; }
  .folio-statement .statement-docmeta { margin-top: .45rem; color: #526358; line-height: 1.7; }
  .folio-statement #docNo { color: #14532d; font-size: 1.08rem; letter-spacing: .025em; }
  .folio-statement .statement-copy-label { display:inline-block; margin:.18rem 0 0; padding:.1rem .45rem; border:1px solid #d5e4d7; border-radius:999px; background:#f5faf5; color:#355a3d; font-size:.64rem; font-weight:800; letter-spacing:.045em; text-transform:uppercase; }
  .folio-statement .statement-header-balance { display:flex; align-items:center; justify-content:space-between; gap:.9rem; margin-top:.55rem; padding:.48rem .65rem; border:1px solid #cfe1d1; border-left:4px solid #166534; border-radius:8px; background:#eef7ef; color:#14532d; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .folio-statement .statement-header-balance span { color:#526358; font-size:.66rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
  .folio-statement .statement-header-balance strong { font-size:1rem; font-weight:850; font-variant-numeric:tabular-nums; white-space:nowrap; }
  .folio-statement .statement-header-balance--amber { border-color:#f3d18a; border-left-color:#b45309; background:#fffbeb; color:#92400e; }
  .folio-statement .statement-header-balance--blue { border-color:#bfdbfe; border-left-color:#1d4ed8; background:#eff6ff; color:#1e40af; }
  .folio-statement .statement-header-balance--slate { border-color:#d8dee7; border-left-color:#64748b; background:#f8fafc; color:#334155; }
  #settlementStatus { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .folio-statement .statement-intro { margin: 0 0 1.1rem; padding: .6rem .75rem; border: 1px solid #e2e8f0; border-left: 3px solid #166534; border-radius: 8px; background: #f8fafc; color: #475569; font-size: .76rem; line-height: 1.55; }
  .folio-statement .statement-intro b { color: #14532d; }
  .folio-statement .statement-parties { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; margin: 1.15rem 0; }
  .folio-statement .statement-panel { min-width: 0; margin: 0; padding: .85rem 1rem; border: 1px solid #dce6dd; border-radius: 10px; background: #fbfdfb; }
  .folio-statement .statement-panel-title { margin:0 0 .6rem; color:#14532d; font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
  .folio-statement .statement-panel dt { color: #657669; font-size: .69rem; font-weight: 750; letter-spacing: .055em; text-transform: uppercase; }
  .folio-statement .statement-panel dd { min-width: 0; overflow-wrap: anywhere; color: #203728; font-weight: 600; }
  .folio-statement .statement-section-heading { display: flex; align-items: center; gap: .6rem; margin: 1.25rem 0 .5rem; color: #14532d; font-size: .88rem; font-weight: 800; letter-spacing: .015em; }
  .folio-statement .statement-table-wrap { width: 100%; margin: .45rem 0 1rem; overflow-x: auto; border: 1px solid #dce5dd; border-radius: 9px; }
  .folio-statement #statementLines, .folio-statement #statementTenders { margin: 0; border-collapse: collapse; }
  .folio-statement #statementLines thead th, .folio-statement #statementTenders thead th { padding: .65rem .55rem; background: #174a2a; border-color: #174a2a; color: #fff; font-size: .65rem; letter-spacing: .045em; white-space: normal; }
  .folio-statement #statementLines tbody td { padding: .55rem .5rem; border-color: #e8eee9; font-size: .76rem; }
  .folio-statement #statementLines tbody td:nth-child(2) { overflow-wrap:anywhere; }
  .folio-statement .statement-tax-cell { display:block; white-space:nowrap; }
  .folio-statement #statementLines tbody tr:nth-child(even):not(.void) { background: #f8fbf8; }
  .folio-statement #statementLines tbody tr.void { background: #fff8f8; }
  .folio-statement #statementLines .day-total th, .folio-statement #statementLines .day-total td { padding:.38rem .5rem; background:#f1f7f2; border-top:1px solid #cbdccc; color:#294b33; font-size:.69rem; font-weight:750; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .folio-statement #statementLines .day-net-movement { color:#647669; font-size:.62rem; font-weight:600; white-space:nowrap; }
  .folio-statement #statementLines tfoot td { padding: .55rem .5rem; background: #f1f7f2; border-top: 1px solid #cbdccc; color: #294b33; font-size: .73rem; }
  .folio-statement #statementLines tfoot tr:last-child td { border-top: 2px solid #166534; background: #eaf4eb; color: #14532d; font-size: .82rem; font-weight: 800; }
  .folio-statement #statementTenders { max-width: 540px; margin-left: auto; font-size: .72rem; border: 1px solid #d7e3d9; border-radius: 10px; overflow: hidden; }
  .folio-statement #statementTenders th, .folio-statement #statementTenders td { padding: .45rem .65rem; }
  .folio-statement #statementTenders tbody td { color: #203728; font-weight: 600; }
  .folio-statement #statementTenders tfoot th, .folio-statement #statementTenders tfoot td { border-top: 2px solid #166534; background: #eef7ef; color: #14532d; font-weight: 800; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  #statementSummary { width: 100%; max-width: 540px; margin: 1rem 0 1.25rem auto; border: 1px solid #d7e3d9; border-radius: 10px; border-collapse: separate; border-spacing: 0; overflow: hidden; }
  #statementSummary th, #statementSummary td { padding: .55rem .75rem; }
  #statementSummary th { background: #fff; color: #526358; font-size: .77rem; font-weight: 600; text-align: left; }
  #statementSummary tbody td { color: #203728; font-weight: 700; }
  #statementSummary tbody tr + tr th, #statementSummary tbody tr + tr td { border-top: 1px solid #edf2ee; }
  #statementSummary tfoot th, #statementSummary tfoot td { border-top: 2px solid #166534; background: #eef7ef; color: #14532d; font-weight: 800; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  #statementSummary .settlement-state { margin-left: .45rem; vertical-align: middle; }
  .folio-statement .statement-amount-words { margin:.4rem 0 1.1rem; padding:.65rem .8rem; border-left:3px solid #9dbda2; background:#f7faf7; color:#334b38; font-size:.76rem; }
  .folio-statement .statement-amount-words span { display:block; margin-bottom:.15rem; color:#647669; font-size:.65rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
  .folio-statement .statement-document-footer { margin-top: 1.25rem; padding-top: .85rem; border-top: 1px solid #dce6dd; color: #526358; }
  .folio-statement .statement-signatures { margin-top: 2rem; padding-top: 1.2rem !important; color: #334155; }
  @media (max-width: 700px) {
    .print-page.folio-statement { padding: 1.1rem; }
    .folio-statement .print-head { flex-direction: column; align-items: flex-start; }
    .folio-statement .print-head .right { text-align: left; }
    .folio-statement .statement-parties { grid-template-columns: 1fr; }
    #statementSummary, .folio-statement #statementTenders { max-width: none; }
    #statementSummary .settlement-state { display: table; margin: .35rem 0 0 auto; }
    .folio-statement #statementLines { min-width: 720px; }
  }
  @page { size: A4; margin: 11mm; }
  @media print {
    .print-page.folio-statement { width: 100%; max-width: none; padding: 0; margin: 0; border: 0; border-radius: 0; }
    .folio-statement .print-head { flex-direction: row; margin-bottom: .9rem; }
    .folio-statement .statement-parties, .folio-statement .statement-panel, .folio-statement .statement-document-footer, .folio-statement .statement-amount-words, .folio-statement .statement-signatures, .folio-statement .statement-intro, #statementSummary, .folio-statement #statementTenders { break-inside: avoid; page-break-inside: avoid; }
    .folio-statement .statement-section-heading { break-after: avoid; page-break-after: avoid; }
    .folio-statement .statement-table-wrap { overflow: visible; border: 0; border-radius: 0; }
    .folio-statement #statementLines, .folio-statement #statementTenders { min-width: 0; width: 100%; max-width: none; }
    .folio-statement #statementLines thead, .folio-statement #statementTenders thead { display: table-header-group; }
    .folio-statement #statementLines tr, .folio-statement #statementTenders tr { break-inside: avoid; page-break-inside: avoid; }
    .folio-statement #statementLines thead th, .folio-statement #statementLines tfoot td, .folio-statement #statementTenders thead th, .folio-statement #statementTenders tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
@endpush

@section('content')
<p class="statement-intro">This is a <b>statement of account</b> for folio {{ $folio->folio_no }}, reflecting every posting up to <time datetime="{{ $printedAt->toIso8601String() }}">{{ $printedAt->format('d M Y · H:i') }}</time>. It is not a tax invoice.@if($folio->invoice_no) A tax invoice (<b>{{ $folio->invoice_no }}</b>) has also been issued for this folio — <a class="no-print" href="{{ route('folios.invoice', $folio) }}" target="_blank" rel="noopener">view the invoice</a>.@endif</p>

<div class="grid g-2 statement-parties">
  <dl class="kv statement-panel" id="statementBuyerBox">
    <dt>Statement for</dt><dd><b>{{ $statementCompany?->name ?? $statementGuest?->full_name ?? '—' }}</b></dd>
    @if($statementCompany)<dt>Guest</dt><dd>{{ $statementGuest?->full_name }}</dd>@endif
    <dt>Address</dt><dd>@if($statementCompany){{ $statementCompany->address ?: '—' }}@else{{ trim(($statementGuest?->address ?? '').' '.($statementGuest?->city ?? '')) ?: '—' }}@endif</dd>
    @if($guestTin)<dt>{{ $guestTinLabel }}</dt><dd>{{ $guestTin }}</dd>@endif
    <dt>Phone</dt><dd>{{ $statementGuest?->phone ?? '—' }}</dd>
    @if($statementGuest?->email)<dt>Email</dt><dd>{{ $statementGuest->email }}</dd>@endif
  </dl>
  <section class="statement-panel" aria-labelledby="statementStayTitle">
    <h4 class="statement-panel-title" id="statementStayTitle">Stay details</h4>
    <dl class="kv m-0">
    <dt>Reservation</dt><dd>{{ $reservation?->reservation_no ?? '—' }}</dd>
    <dt>Room{{ count($stayLines) === 1 ? '' : 's' }}</dt><dd>@if(count($stayLines) > 1)@foreach($stayLines as $stayLine){{ $stayLine['label'] }}@if($stayLine['dates']) ({{ $stayLine['dates'] }})@endif @if($stayLine['occupant']) — {{ $stayLine['occupant'] }}@endif @if(! $loop->last)<br>@endif @endforeach @else{{ $roomLabel ?: 'No active room allocation' }}@if($stayLines[0]['occupant'] ?? null) — {{ $stayLines[0]['occupant'] }}@endif @endif</dd>
    @if(count($occupants))<dt>Occupant{{ count($occupants) === 1 ? '' : 's' }}</dt><dd>{{ $occupants->implode(', ') }}</dd>@endif
    @if($arrivalDate)<dt>Arrival</dt><dd><time datetime="{{ $arrivalAt?->toIso8601String() ?? $arrivalDate->toDateString() }}">{{ $arrivalAt?->format('d M Y, H:i') ?? $arrivalDate->format('d M Y') }}</time></dd>@endif
    @if($departureDate)<dt>Departure</dt><dd><time datetime="{{ $departureAt?->toIso8601String() ?? $departureDate->toDateString() }}">{{ $departureAt?->format('d M Y, H:i') ?? $departureDate->format('d M Y') }}</time></dd>@endif
    <dt>Guests</dt><dd>{{ $adults || $children ? $adults.' adults'.($children ? ', '.$children.' children' : '') : 'Not recorded' }}</dd>
    @if($planLabel !== '')<dt>Plan type</dt><dd>{{ $planLabel }}</dd>@endif
    </dl>
  </section>
</div>

<h4 class="statement-section-heading">Folio activity</h4>
<div class="statement-table-wrap"><table class="table table-compact" id="statementLines">
  <caption class="sr-only">Folio statement ledger for {{ $folio->folio_no }}</caption>
  <thead><tr><th scope="col">Business date / recorded</th><th scope="col">Particulars</th><th scope="col" class="num">Charge / tax</th><th scope="col" class="num">Debit</th><th scope="col" class="num">Credit</th><th scope="col" class="num">Running balance</th></tr></thead>
  @foreach($days as $day)
  <tbody aria-label="Folio activity for {{ $day['date']?->format('d M Y') }}">
    @foreach($day['rows'] as $row)
    <tr class="{{ $row['isVoid'] ? 'void' : '' }}">
      <td class="nowrap"><time datetime="{{ $row['date']->toDateString() }}">{{ $row['date']->format('d/m/Y') }}</time>@if($row['type'] === 'payment' || $row['type'] === 'refund')<br><span class="tiny text-2">Recorded <time datetime="{{ $row['createdAt']?->toIso8601String() }}">{{ $row['createdAt']?->format('d M Y, H:i') }}</time></span>@endif</td>
      <td>{{ $row['description'] }}@if($row['paymentMode']) – {{ $row['paymentMode'] }}@endif @include('folios._line-origin', ['transaction' => $row['transaction']]) @if($row['isComplimentary'])<span class="badge badge-slate">Informational · no charge</span>@endif @if($row['isOpening'])<span class="badge badge-amber">opening · {{ $row['openingLabel'] }}</span>@endif @if($row['isVoid'])<span class="badge badge-red" title="Excluded from totals">VOIDED</span>@endif @if($row['reference'])<br><span class="tiny text-2">Reference: {{ $row['reference'] }}</span>@endif</td>
      <td class="num">{{ $row['type'] === 'charge' || $row['isOpening'] ? money($row['amount'], false) : '' }}@if($row['type'] === 'charge' && (float) $row['taxAmount'] != 0)<span class="tiny text-2 statement-tax-cell">Tax {{ money($row['taxAmount'], false) }} {{ '@'.(float) $row['taxRate'] }}%</span>@endif</td>
      <td class="num">{{ $row['debit'] !== null ? money($row['debit'], false) : '' }}</td>
      <td class="num">{{ $row['credit'] !== null ? money($row['credit'], false) : '' }}</td>
      <td class="num">{{ money($row['running'], false) }}</td>
    </tr>
    @endforeach
    <tr class="day-total"><th scope="row" colspan="3">Day total for {{ $day['date']?->format('d M Y') }} <span class="day-net-movement">· Net movement {{ money($day['net'], false) }}</span></th><td class="num">{{ money($day['debits'], false) }}</td><td class="num">{{ money($day['credits'], false) }}</td><td class="num">{{ money($day['running'], false) }}</td></tr>
  </tbody>
  @endforeach
  <tfoot>
    <tr><td colspan="3" class="right">Charges on this statement{{ $openingTxs->isNotEmpty() ? ' (opening balance excluded)' : '' }}</td><td class="num">{{ money($totals['charges'] + $totals['refunds'], false) }}</td><td class="num">{{ money($totals['payments'] + $totals['discount'], false) }}</td><td></td></tr>
    @if($openingTxs->isNotEmpty())<tr><td colspan="3" class="right">Opening balance brought forward</td><td class="num">{{ money($openingBalance, false) }}</td><td></td><td></td></tr>@endif
    <tr><td colspan="5" class="right">Balance outstanding</td><td class="num">{{ money($folioBalance) }}</td></tr>
  </tfoot>
</table></div>
@if($voidCount > 0)<p class="tiny text-2">{{ $voidCount }} voided {{ $voidCount === 1 ? 'line is' : 'lines are' }} shown struck-through and excluded from every total on this statement.</p>@endif

@if(count($tenderSummary))
<h4 class="statement-section-heading">Payments received by method</h4>
<div class="statement-table-wrap" style="border:0"><table class="table table-compact" id="statementTenders">
  <caption class="sr-only">Payments by method on folio {{ $folio->folio_no }}</caption>
  <thead><tr><th scope="col">Method</th><th scope="col" class="num">Count</th><th scope="col" class="num">Amount</th></tr></thead>
  <tbody>@foreach($tenderSummary as $tender)
    <tr><td>{{ $tender['label'] }}</td><td class="num">{{ $tender['count'] }}×</td><td class="num">{{ money($tender['total'], false) }}</td></tr>
  @endforeach</tbody>
  <tfoot><tr><th scope="row" colspan="2">Net payments (after refunds)</th><td class="num">{{ money($totals['payments'] - $totals['refunds']) }}</td></tr></tfoot>
</table></div>
@endif

<h4 class="statement-section-heading" id="statementSummaryTitle">Statement summary</h4>
<section aria-labelledby="statementSummaryTitle">
<table class="table table-compact" id="statementSummary">
  <caption class="sr-only">Summary of charges, payments and outstanding balance on folio {{ $folio->folio_no }}</caption>
  <tbody>
    <tr><th scope="row">Charges (incl. tax)</th><td class="num">{{ money($totals['charges']) }}</td></tr>
    @if(abs($openingBalance) > 0.009)<tr><th scope="row">{{ $openingBalance > 0 ? 'Opening balance brought forward' : 'Opening credit brought forward' }}</th><td class="num">{{ money(abs($openingBalance)) }}</td></tr>@endif
    <tr><th scope="row">Payments received</th><td class="num">{{ money($totals['payments']) }}</td></tr>
    <tr><th scope="row">Refunds issued</th><td class="num">{{ money($totals['refunds']) }}</td></tr>
    @if((float) $totals['discount'] != 0.0)<tr><th scope="row">Discounts / allowances</th><td class="num">{{ money($totals['discount']) }}</td></tr>@endif
  </tbody>
  <tfoot><tr>
    <th scope="row">{{ $balanceLabel }}@if($openingTxs->isNotEmpty()) <span class="tiny text-2">(includes opening balance)</span>@endif</th>
    <td class="num">{{ money(abs($folioBalance)) }} <span class="badge badge-{{ $settlementTone }} settlement-state">{{ $settlementLabel }}</span></td>
  </tr></tfoot>
</table>
</section>

<p class="statement-amount-words"><span>Balance outstanding in words</span><strong>{{ $currentHotel->currency ?: 'GHS' }} {{ amount_words(abs($folioBalance)) }}</strong></p>

<div class="statement-document-footer">
  @if($currentHotel->invoice_footer)<p class="small text-2 mb-1">{{ $currentHotel->invoice_footer }}</p>@endif
  <p class="small text-2 m-0">This statement reflects all postings made to folio {{ $folio->folio_no }} up to the time of printing. Please quote folio no. {{ $folio->folio_no }} in any correspondence about this account.</p>
</div>
<div class="flex justify-between statement-signatures"><div>Received by<br><br>______________________<br>Guest / company representative</div><div class="right">For {{ $currentHotel->legal_name ?: $currentHotel->name }}<br><br>______________________<br>Authorised signatory</div></div>
@endsection
