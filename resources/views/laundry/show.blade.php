@extends('layouts.app')
@section('title', 'Laundry '.$o->order_no)
@section('crumbs') <a href="{{ route('laundry.index') }}">Laundry orders</a> / {{ $o->order_no }} @endsection
@section('actions')
<span class="badge badge-{{ \App\Models\LaundryOrder::TONES[$o->status] }}" id="loStatus">{{ $o->statusLabel() }}</span>@if($o->isOverdue()) <span class="badge badge-red">Overdue</span>@endif
<a class="btn" href="{{ route('laundry.docket', $o) }}" target="_blank"><x-icon name="print"/> Bag docket</a>
@if(can_do('laundry.orders'))
  @if(in_array($o->status, ['collected', 'processing']) && $o->pieces)<form method="post" action="{{ route('laundry.ready', $o) }}" class="inline">@csrf<button class="btn btn-accent" id="loReady"><x-icon name="check"/> Mark ready</button></form>@endif
  @if(in_array($o->status, ['processing', 'ready']))<button class="btn btn-success" data-modal="deliverModal" id="loDeliver"><x-icon name="money"/> Deliver &amp; settle</button>@endif
  @if($o->isOpen())<button class="btn btn-ghost text-red" data-modal="cancelModal"><x-icon name="x"/> Cancel</button>@endif
@endif
@endsection
@section('content')
@push('head')
<style>
  /* Laundry order screen — page-scoped polish only; behaviour unchanged. */
  .lo-hero { display: flex; flex-wrap: wrap; gap: 1.1rem; align-items: flex-start; justify-content: space-between; padding: 1.1rem 1.25rem; }
  .lo-hero-main { min-width: 0; }
  .lo-hero-main h2 { margin: .15rem 0 .4rem; font-size: 1.45rem; letter-spacing: -0.01em; overflow-wrap: anywhere; }
  .lo-eyebrow { display: flex; align-items: center; gap: .45rem; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--c-text-2); }
  .lo-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .1rem; }
  .lo-chip { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid var(--c-border); background: var(--c-slate-50); color: var(--c-text-2); border-radius: 999px; padding: .18rem .6rem; font-size: .76rem; white-space: nowrap; }
  .lo-chip .i { width: .85rem; height: .85rem; }
  .lo-chip-warn { border-color: #fecaca; background: var(--c-red-50); color: var(--c-red); font-weight: 600; }
  .lo-payable { flex: 0 0 auto; text-align: right; background: var(--c-primary-50); border: 1px solid var(--c-primary-100); border-radius: var(--radius-lg); padding: .6rem .95rem; }
  .lo-payable .lo-payable-label { font-size: .7rem; font-weight: 700; letter-spacing: .06em; color: var(--c-accent); text-transform: uppercase; }
  .lo-payable .lo-payable-amount { font-size: 1.75rem; font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; }
  .lo-payable .tiny { max-width: 22rem; margin-left: auto; }
  /* Lifecycle stepper */
  .lo-flow { list-style: none; margin: 0; padding: 0; flex-basis: 100%; display: flex; border: 1px solid var(--c-border); border-radius: var(--radius-lg); overflow: hidden; background: var(--c-surface); }
  .lo-step { position: relative; flex: 1 1 0; display: flex; align-items: center; gap: .5rem; padding: .55rem .75rem; border-right: 1px solid var(--c-border); min-width: 0; }
  .lo-step:last-child { border-right: 0; }
  .lo-step-icon { flex: 0 0 auto; width: 1.6rem; height: 1.6rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: var(--c-slate-50); color: var(--c-muted); border: 1px solid var(--c-border); }
  .lo-step-icon .i { width: .9rem; height: .9rem; }
  .lo-step-name { display: block; font-size: .78rem; font-weight: 700; }
  .lo-step-meta { display: block; font-size: .7rem; color: var(--c-text-2); font-variant-numeric: tabular-nums; }
  .lo-step.is-done .lo-step-icon { background: var(--c-green-50); border-color: #bbf7d0; color: var(--c-green); }
  .lo-step.is-done .lo-step-name { color: var(--c-text); }
  .lo-step.is-current { background: var(--c-primary-50); }
  .lo-step.is-current .lo-step-icon { background: #fff; border-color: var(--c-primary); color: var(--c-primary); box-shadow: 0 0 0 3px var(--c-primary-100); }
  .lo-flow-cancelled { flex-basis: 100%; margin: 0; }
  /* Section dividers inside the kv grid */
  .kv-section { grid-column: 1 / -1 !important; padding: .35rem 0 .15rem !important; border-bottom: 0 !important; font-size: .7rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--c-text-2); }
  .lo-next { margin: .55rem 0 0; padding: .45rem .6rem; border-left: 3px solid var(--c-primary); background: var(--c-primary-50); border-radius: var(--radius-sm); font-size: .8rem; color: var(--c-text-2); }
  .lo-next b { color: var(--c-text); }
  .lo-charge-list { margin: .35rem 0 0; padding-left: 1.05rem; }
  .lo-charge-list li { margin: .1rem 0; }
  @media (max-width: 900px) { .lo-payable { text-align: left; } .lo-payable .tiny { margin-left: 0; } .lo-flow { overflow-x: auto; } .lo-step { min-width: 9.5rem; } }
</style>
@endpush
<div class="grid g-2-1">
<div>
  <div class="card lo-hero">
    <div class="lo-hero-main">
      <span class="lo-eyebrow"><x-icon name="shirt" size="sm"/> {{ $o->type === 'walk_in' ? 'Walk-in customer' : 'In-house guest' }}</span>
      <h2>{{ $o->type === 'walk_in' ? $o->guest_name : 'Room '.$o->room?->room_no.' · '.$o->guest_name }}</h2>
      <div class="lo-chips">
        <span class="lo-chip"><x-icon name="shirt"/> {{ \App\Services\LaundryService::SPEEDS[$o->speed] }}@if($o->speed === 'express') +{{ rtrim(rtrim(number_format($expressPct, 2), '0'), '.') }} %@endif</span>
        <span class="lo-chip"><x-icon name="box"/> Bag {{ $o->bag_no ?: '—' }}@if($o->bag_pieces) · {{ $o->bag_pieces }} pcs@endif</span>
        @if($o->promised_at)
        <span class="lo-chip {{ $o->isOverdue() ? 'lo-chip-warn' : '' }}"><x-icon name="{{ $o->isOverdue() ? 'alert' : 'clock' }}"/> {{ $o->isOverdue() ? 'Overdue' : 'Due back' }} {{ $o->promised_at->format('d M, H:i') }}</span>
        @endif
        @if($o->ready_at)<span class="lo-chip"><x-icon name="check"/> Ready {{ $o->ready_at->format('d M, H:i') }}</span>@endif
      </div>
    </div>
    <div class="right lo-payable" style="text-align:right"><div class="lo-payable-label">PAYABLE@if($quote['rate'] > 0) · INCL. {{ rtrim(rtrim(number_format($quote['rate'], 2), '0'), '.') }}% TAX@endif</div>
      <div class="lo-payable-amount" id="loPayable">{{ money($quote['total']) }}</div>
      <div class="tiny muted">{{ money($quote['listed'], false) }} {{ $quote['inclusive'] ? 'incl.' : '+' }} {{ $quote['rate'] > 0 ? 'tax '.money($quote['tax'], false) : 'no tax set for laundry' }}@if((float) $o->express_charge > 0) · incl. express {{ money($o->express_charge, false) }}@endif</div></div>
    @if($o->status === 'cancelled')
      <p class="alert alert-error lo-flow-cancelled" role="alert"><x-icon name="x"/><div><b>Order cancelled.</b>{{ $o->cancel_reason ? ' '.$o->cancel_reason : '' }}</div></p>
    @else
      @php
        $loStages = [
          ['name' => 'Collected', 'at' => $o->collected_at, 'icon' => 'login'],
          ['name' => 'Itemised', 'at' => $o->pieces ? $o->collected_at : null, 'icon' => 'list'],
          ['name' => 'Ready', 'at' => $o->ready_at, 'icon' => 'check'],
          ['name' => 'Delivered', 'at' => $o->delivered_at, 'icon' => 'truck'],
        ];
        $loCurrent = 0;
        foreach ($loStages as $i => $stage) { if ($stage['at']) { $loCurrent = $i + 1; } }
        $loCurrent = min($loCurrent, count($loStages)); // all done → index == count (none current)
        $loItemised = (int) ($o->pieces ?? 0) > 0;
      @endphp
      <ol class="lo-flow" aria-label="Order progress">
        @foreach($loStages as $i => $stage)
          @php
            $done = $stage['name'] === 'Itemised' ? $loItemised : (bool) $stage['at'];
            $isCurrent = ! $done && $i === $loCurrent;
          @endphp
          <li class="lo-step {{ $done ? 'is-done' : ($isCurrent ? 'is-current' : '') }}" @if($isCurrent) aria-current="step" @endif>
            <span class="lo-step-icon"><x-icon name="{{ $done ? 'check' : $stage['icon'] }}"/></span>
            <span><span class="lo-step-name">{{ $stage['name'] }}</span><span class="lo-step-meta">@if($stage['name'] === 'Itemised'){{ $loItemised ? $o->pieces.' pcs' : 'not yet' }}@elseif($stage['at']){{ $stage['at']->format('d M, H:i') }}@else pending @endif</span></span>
          </li>
        @endforeach
      </ol>
    @endif
  </div>

  <div class="card"><div class="card-head"><h3>Order details</h3></div><div class="card-body">
    <div class="kv-grid mt-2">
      <div class="kv-section">The guest &amp; the bag</div>
      <div><span>Service</span><b>{{ \App\Services\LaundryService::SPEEDS[$o->speed] }}</b>@if($o->speed === 'express')<span class="tiny muted">+{{ rtrim(rtrim(number_format($expressPct, 2), '0'), '.') }} %</span>@endif</div>
      <div><span>Bag / tag</span><b>{{ $o->bag_no ?: '—' }}</b>@if($o->bag_pieces)<span class="tiny muted">{{ $o->bag_pieces }} pcs counted at pick-up{{ $o->pieces && $o->pieces !== $o->bag_pieces ? ' – itemised '.$o->pieces : '' }}</span>@endif</div>
      <div><span>Collected</span><b>{{ $o->collected_at?->format('d M Y, H:i') }}</b><span class="tiny muted">by {{ $o->creator?->name ?? '—' }}</span></div>
      <div><span>Promised</span><b class="{{ $o->isOverdue() ? 'text-red' : '' }}">{{ $o->promised_at?->format('d M Y, H:i') }}</b></div>
      @if($o->ready_at)<div><span>Ready</span><b>{{ $o->ready_at->format('d M Y, H:i') }}</b></div>@endif
      @if($o->delivered_at)<div><span>Delivered</span><b>{{ $o->delivered_at->format('d M Y, H:i') }}</b><span class="tiny muted">by {{ $o->deliverer?->name }}</span></div>@endif
      @if($o->guestRequest)<div><span>Guest request</span><b>{{ $o->guestRequest->request_no }}</b><span class="tiny muted">{{ $o->guestRequest->item }} · {{ \App\Models\GuestRequest::STATUSES[$o->guestRequest->status] ?? $o->guestRequest->status }}</span></div>@endif
      @if($o->folioTransaction || $o->notes || $o->cancel_reason)<div class="kv-section">Billing &amp; notes</div>@endif
      @if($o->folioTransaction)<div class="span-all"><span>{{ $o->settledDirectly() ? 'Settled' : 'Charged to' }}</span><b><a href="{{ route('folios.show', $o->folio) }}">Folio {{ $o->folio->folio_no }}</a>{{ $o->folio->invoice_no ? ' · invoice '.$o->folio->invoice_no : '' }}</b>
        <span class="tiny muted">{{ money($o->folioTransaction->amount) }} + {{ money($o->folioTransaction->tax_amount) }} tax {{ $o->folioTransaction->tax_rate > 0 ? '('.rtrim(rtrim(number_format((float) $o->folioTransaction->tax_rate, 2), '0'), '.').'%)' : '' }} = {{ money((float) $o->folioTransaction->amount + (float) $o->folioTransaction->tax_amount) }}{{ $o->settledDirectly() ? ' · paid across the counter'.($o->paymentMode ? ' by '.$o->paymentMode->name : '') : ($o->paymentMode ? ' · paid by '.$o->paymentMode->name : '') }}</span>@if($o->folioTransaction->is_void)<span class="badge badge-red">Charge voided on the folio</span>@endif</div>@endif
      @if($o->notes)<div class="span-all"><span>Notes</span><b>{{ $o->notes }}</b></div>@endif
      @if($o->cancel_reason)<div class="span-all"><span>Cancelled</span><b class="text-red">{{ $o->cancel_reason }}</b></div>@endif
    </div>
  </div></div>

  @if($o->isOpen() && can_do('laundry.orders'))
  <form method="post" action="{{ route('laundry.items', $o) }}" class="card flush" id="itemsCard">@csrf
    <div class="card-head"><h3>Items</h3><div class="flex gap-1 items-center small"><label class="check"><input type="radio" name="speed" value="normal" @checked($o->speed === 'normal')> Normal</label><label class="check"><input type="radio" name="speed" value="express" @checked($o->speed === 'express')> Express</label><button class="btn btn-primary btn-sm" id="saveItems"><x-icon name="check"/> Save items</button></div></div>
    <div class="card-body">@include('laundry._grid', ['readonly' => false])</div>
    <div class="card-foot small" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center">
      <span>@if($o->bag_pieces)Counted at pick-up: <b>{{ $o->bag_pieces }}</b> · itemised so far: <b>{{ $o->pieces }}</b>@if($o->pieces !== $o->bag_pieces) — {{ $o->pieces < $o->bag_pieces ? ($o->bag_pieces - $o->pieces).' still to find' : ($o->pieces - $o->bag_pieces).' more than counted' }}@endif @else Items not itemised yet — count the bag and add them here.@endif</span>
      <span class="muted">Prices from the laundry price list · express is +{{ rtrim(rtrim(number_format($expressPct, 2), '0'), '.') }} %</span>
    </div>
  </form>
  @else
  <div class="card flush"><div class="card-head"><h3>Items</h3></div><div class="card-body table-wrap"><table class="table table-compact" id="loLines">
    <thead><tr><th>Item</th><th>Service</th><th class="num">Qty</th><th class="num">Rate</th><th class="num">Amount</th><th>Remark</th></tr></thead><tbody>
    @forelse($o->lines as $l)<tr><td>{{ $l->item_name }}</td><td>{{ \App\Models\LaundryItem::SERVICES[$l->service] ?? $l->service }}</td><td class="num">{{ $l->qty }}</td><td class="num">{{ money($l->rate, false) }}</td><td class="num">{{ money($l->amount, false) }}</td><td class="small muted">{{ $l->remark }}</td></tr>
    @empty<tr><td colspan="6" class="muted center">No items.</td></tr>@endforelse
    </tbody><tfoot><tr><td colspan="2" class="right">{{ $o->pieces }} pcs</td><td colspan="3" class="num">Subtotal {{ money($o->subtotal, false) }}@if((float) $o->express_charge) + express {{ money($o->express_charge, false) }} = {{ money($o->total, false) }} @endif @if($quote['rate'] > 0) + tax {{ money($quote['tax'], false) }}@endif = <b>{{ money($quote['total']) }}</b></td><td></td></tr></tfoot></table></div></div>
  @endif
</div>
<div>
  <div class="card"><div class="card-head"><h3>Progress</h3>@if($o->isOpen())<span class="small muted">{{ $o->ready_at ? 'Waiting for delivery' : ($o->pieces ? 'In the laundry' : 'Awaiting itemisation') }}</span>@endif</div><div class="card-body"><ul class="timeline" id="loTimeline">
    <li class="tl-info"><b>Collected</b><span>{{ $o->collected_at?->format('d M H:i') }}{{ $o->bag_pieces ? ' · '.$o->bag_pieces.' pcs' : '' }}</span></li>
    @if($o->pieces)<li class="tl-info"><b>Itemised</b><span>{{ $o->pieces }} pcs · {{ money($o->total) }}</span></li>@endif
    @if($o->ready_at)<li class="tl-ok"><b>Ready</b><span>{{ $o->ready_at->format('d M H:i') }}</span></li>@endif
    @if($o->delivered_at)<li class="tl-ok"><b>Delivered</b><span>{{ $o->delivered_at->format('d M H:i') }} – {{ $o->settledDirectly() ? 'paid' : ($o->type === 'guest' ? 'charged to the room' : 'paid') }}</span></li>@endif
    @if($o->status === 'cancelled')<li class="tl-bad"><b>Cancelled</b><span>{{ $o->updated_at->format('d M H:i') }}</span></li>@endif
    @if($o->isOpen())<li class="{{ $o->isOverdue() ? 'tl-bad' : 'tl-info' }}"><b>{{ $o->isOverdue() ? 'Overdue' : 'Due back' }}</b><span>{{ $o->promised_at?->format('d M H:i') }} ({{ $o->promised_at?->diffForHumans() }})</span></li>@endif
  </ul>
  @if($o->isOpen())
    @php $loNext = $o->pieces ? ($o->ready_at ? ($o->type === 'walk_in' ? 'Deliver the bag and take payment at the counter.' : 'Deliver — charge the room folio, or take payment at the counter.') : 'Laundry is working it — mark it ready when the last piece is done.') : 'Count the bag and itemise the items — the grid on the left prices as you type.'; @endphp
    <p class="lo-next"><b>Next:</b> {{ $loNext }}</p>
  @endif
  </div></div>
  <div class="card"><div class="card-body small muted">
    <b>How it's charged:</b> on delivery one <i>Laundry</i> line is posted — to the room folio, or, if the guest settles at the counter, to a paid invoice — and it carries the laundry tax.
    <ul class="lo-charge-list">
      <li>Tax: {{ $tax['label'] }} (revenue account 4060)</li>
      <li>Walk-in customers always pay on delivery and get a tax invoice</li>
      <li>Prices always come from the <a href="{{ route('laundry.prices') }}">laundry price list</a>{{ $quote['inclusive'] ? ' — tax-inclusive' : ' — before tax' }}</li>
    </ul>
  </div></div>
</div>
</div>

<div class="modal" id="deliverModal"><div class="modal-box"><form method="post" action="{{ route('laundry.deliver', $o) }}">@csrf
  <div class="modal-head"><h3>Deliver {{ $o->order_no }}</h3><button type="button" class="btn btn-ghost btn-icon" data-close><x-icon name="x"/></button></div>
  <div class="modal-body">
    <p class="m-0 mb-1">{{ $o->pieces }} pcs · {{ money($quote['listed'], false) }}@if($quote['rate'] > 0) + {{ money($quote['tax'], false) }} tax = <b>{{ money($quote['total']) }}</b>@else <b>{{ money($quote['listed']) }}</b>@endif</p>
    <p class="tiny muted m-0 mb-1">{{ $tax['label'] }}{{ $quote['inclusive'] ? ' · prices already include tax' : ' · prices are before tax' }}</p>
    @if($o->type === 'guest')
      <div class="field span-all"><label for="loSettle">Settle</label>
        <select name="settle" id="loSettle" class="input" data-laundry-settle>
          <option value="room" @selected(old('settle', 'room') === 'room')>Charge to room {{ $o->room?->room_no }}'s folio</option>
          <option value="pay" @selected(old('settle') === 'pay')>Pay now – {{ money($quote['total']) }} at the counter, issue an invoice</option>
        </select></div>
    @endif
    <div id="laundryDirect-{{ $o->id }}" data-laundry-direct @if($o->type === 'guest' && old('settle', 'room') === 'room') hidden @endif>
      <div id="laundrySingleTender-{{ $o->id }}" class="form-grid">
        <div class="field"><label>Paid by</label><select name="payment_mode_id" class="input" required data-payment-mode>@foreach($modes as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
        <x-payment-momo-fields mode-name="payment_mode_id"/>
        <div class="field span-all"><label>Reference / transaction ID</label><input name="reference" class="input" maxlength="80" placeholder="optional"></div>
        <x-cheque-fields class="span-all"/>
      </div>
      <x-payment-split-tender widget-id="laundrySplitTender-{{ $o->id }}" single-id="laundrySingleTender-{{ $o->id }}" :modes="$modes" label="payment"/>
    </div>
  </div>
  <div class="modal-foot"><button type="button" class="btn" data-close>Back</button><button class="btn btn-success"><x-icon name="check"/> Deliver</button></div>
</form></div></div>
@push('scripts')
<script>
(() => {
  const modal = document.getElementById('deliverModal');
  const settle = modal?.querySelector('[data-laundry-settle]');
  const direct = modal?.querySelector('[data-laundry-direct]');
  if (!modal || !direct) return;
  const sync = () => {
    const toRoom = Boolean(settle) && settle.value === 'room';
    direct.querySelectorAll('input, select, textarea').forEach((field) => { field.disabled = toRoom; });
    // collapse split tender before hiding, so a stale tender row can't be submitted
    if (toRoom) {
      const panel = direct.querySelector('[data-split-panel]');
      if (panel && !panel.hidden) direct.querySelector('[data-split-toggle]')?.click();
    }
    direct.hidden = toRoom;
  };
  settle?.addEventListener('change', sync);
  sync();
})();
</script>
@endpush
<div class="modal" id="cancelModal"><div class="modal-box"><form method="post" action="{{ route('laundry.cancel', $o) }}">@csrf
  <div class="modal-head"><h3>Cancel {{ $o->order_no }}</h3><button type="button" class="btn btn-ghost btn-icon" data-close><x-icon name="x"/></button></div>
  <div class="modal-body"><div class="field"><label>Reason</label><input name="reason" class="input" required maxlength="190" placeholder="e.g. guest collected the bag back"></div></div>
  <div class="modal-foot"><button type="button" class="btn" data-close>Back</button><button class="btn btn-danger">Cancel order</button></div>
</form></div></div>
@endsection
