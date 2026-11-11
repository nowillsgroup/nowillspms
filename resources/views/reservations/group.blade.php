@extends('layouts.app')
@section('title', 'Group Reservation')
@section('crumbs') <a href="{{ route('reservations.index') }}">Reservations</a> / Group Reservation @endsection
@section('content')
<div class="card">
  <div class="card-body" style="padding:.85rem 1rem">
    <div class="flex gap-1" style="align-items:flex-start">
      <span class="badge badge-blue" style="margin-top:.15rem"><x-icon name="users" size="sm"/></span>
      <div>
        <strong>One booking, many rooms — each room keeps its own dates and its own guest.</strong>
        <p class="hint" style="margin:.15rem 0 0">Book for a person or a company (city ledger). Rooms can be added with individual arrival / departure dates — people arrive and leave on different days. Each room’s bill, registration card and invoice name the person assigned to it; the booking itself stays under the person or company it was made for. A company-billed group leaves as <b>one group master bill</b> at the end of the stay.</p>
      </div>
    </div>
  </div>
</div>

@if($errors->any())
  <div class="alert alert-error" role="alert"><x-icon name="alert"/><div><strong>The group reservation needs a correction.</strong>
    <ul style="margin:.3rem 0 0;padding-left:1.2rem">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
  </div></div>
@endif

<form method="post" action="{{ route('reservations.group.store') }}" id="groupForm">
  @csrf
  <div class="grid g-3-1">
    <div>
      <div class="card">
        <div class="card-head"><h3>Group / Company</h3></div>
        <div class="card-body form-grid">
          <x-field name="group_name" label="Group name" :value="old('group_name')" required placeholder="e.g. Nowills Group off-site, Ama's wedding party…" hint="Shown wherever the booking appears. Required for a group."/>
          <x-field name="status" label="Status" type="select" :options="['confirmed' => 'Confirmed', 'tentative' => 'Tentative']" :value="old('status', 'confirmed')" :placeholder="false"/>
          <x-field name="arrival_date" label="Window arrival" type="date" :value="$defaults['arrival_date']" required hint="Used to pre-fill rooms — each room keeps its own dates below."/>
          <x-field name="departure_date" label="Window departure" type="date" :value="$defaults['departure_date']" required/>
          <x-field name="eta" label="ETA" type="time" :value="old('eta')"/>
          <x-field name="business_source_id" label="Business source" type="select" :options="$sources" :value="old('business_source_id')"/>
          <div class="span-all" style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.5rem;align-items:end">
            <x-field name="company_id" label="Company / city ledger" type="select" :options="$companies" :value="old('company_id')" hint="Leave empty to book under a person only."/>
            {{-- Open the account without losing the group in front of you, exactly as the booking desk and the walk-in desk do. --}}
            @if(can_do('credit.manage'))<button type="button" class="btn btn-sm btn-ghost" id="grpCompanyCreate" data-modal="grpCompanyCreateModal" style="margin-bottom:.35rem"><x-icon name="plus" size="sm"/> Create City Ledger / Company</button>@endif
          </div>
          <p class="hint span-all" id="grpCompanyStatus" aria-live="polite" hidden></p>
          <x-field name="billing_instruction" label="Bill to" type="select" :options="['guest' => 'Guest (per room)', 'company' => 'Company — one group master bill']" :value="old('billing_instruction', 'guest')" :placeholder="false" hint="Company billing: rooms keep individual checkouts; every room bill folds into one reservation master bill at checkout."/>
          <x-field name="ota_reference" label="OTA / Voucher ref." :value="old('ota_reference')"/>
          <x-field name="purpose" label="Purpose of visit" type="select" :options="['Leisure'=>'Leisure','Business'=>'Business','Conference'=>'Conference','Wedding'=>'Wedding','Medical'=>'Medical','Other'=>'Other']" :value="old('purpose')"/>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h3>Rooms <span class="small muted" id="groupRoomCount"></span></h3>
          <div class="flex gap-1">
            <button type="button" class="btn btn-sm btn-soft" id="applyWindowDates" title="Copy the window arrival / departure into every room">Apply dates to all rooms</button>
            <button type="button" class="btn btn-sm btn-soft" onclick="addGroupLine()"><x-icon name="plus" size="sm"/> Add room</button>
          </div>
        </div>
        <div class="card-body" id="groupLines" style="display:flex;flex-direction:column;gap:.6rem"></div>
        <div class="card-foot flex" style="justify-content:space-between;align-items:center">
          <span class="small muted">Per-room totals exclude tax. Occupant names are optional now — assign or change them on the reservation page or at check-in.</span>
          <span class="bold">Estimated room total (excl. tax): <span id="groupGrandTotal">0.00</span></span>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h3>Booking contact</h3></div>
        <div class="card-body">
          @include('reservations._guest', ['allowCustomGuestTitle' => true])
          <p class="hint">The booking stays under this person (and the company, if any). Individuals using the rooms are named per room above and on their GRC / bill.</p>
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-head"><h3>Billing</h3></div>
        <div class="card-body">
          <x-field name="discount_pct" label="Discount %" type="number" step="0.01" min="0" max="100" :value="old('discount_pct', 0)"/>
          <div class="form-section">Advance payment <span class="small muted">(optional)</span></div>
          <div id="groupAdvanceSingle">
            <x-field name="advance" label="Amount" type="number" step="0.01" min="0" value="0"/>
            <div class="mt-1"></div>
            <x-field name="advance_mode" label="Mode" type="select" :options="$paymentModes" data-payment-mode/>
            <div class="mt-1"></div>
            <x-payment-momo-fields mode-name="advance_mode"/>
            <div class="mt-1"></div>
            <x-field name="advance_ref" label="Reference / Txn ID"/>
            <x-cheque-fields/>
          </div>
          <p class="hint">One advance posts to the reservation-level advance folio; it is moved into the first room that checks in, exactly as on the booking desk.</p>
        </div>
      </div>
      <div class="card">
        <div class="card-head"><h3>Special Requests</h3></div>
        <div class="card-body">
          <x-field name="special_request" type="textarea" :value="old('special_request')" placeholder="Early check-in for early arrivals, airport pickup, extra beds, dietary needs, rooming list…"/>
        </div>
      </div>
      <button class="btn btn-primary btn-lg btn-block" type="submit"><x-icon name="check"/> Save Group Reservation</button>
      <a href="{{ route('reservations.index') }}" class="btn btn-ghost btn-block mt-1">Cancel</a>
    </div>
  </div>
</form>

@include('reservations._city-ledger-create', [
  'prefix' => 'grpCompanyCreate',
  'action' => route('reservations.company.store'),
])

<template id="groupLineTpl">
  <section class="card group-line" data-line style="border:1px solid var(--c-border)">
    <div class="card-head" style="padding:.45rem .7rem">
      <span class="bold group-line-title">Room</span>
      <span class="small muted group-line-dates"></span>
      <span style="flex:1"></span>
      <span class="small group-line-total"></span>
      <button type="button" class="btn btn-sm btn-ghost group-line-remove" aria-label="Remove this room" title="Remove this room"><x-icon name="x" size="sm"/></button>
    </div>
    <div class="card-body">
      <div class="form-grid">
        <div class="field"><label>Room type <span class="req">*</span></label><select class="input" data-k="room_type_id" required><option value="">Select</option>@foreach($roomTypes as $t)<option value="{{ $t->id }}" data-rate="{{ $t->base_rate }}">{{ $t->name }}</option>@endforeach</select></div>
        <div class="field"><label>Rate plan</label><select class="input" data-k="rate_plan_id"><option value="">Rack rate</option></select></div>
        <div class="field"><label>Room no.</label><select class="input" data-k="room_id"><option value="">Auto-assign</option></select></div>
        <div class="field"><label>Adults</label><input class="input" type="number" min="1" max="10" data-k="adults" value="2"></div>
        <div class="field"><label>Children</label><input class="input" type="number" min="0" max="10" data-k="children" value="0"></div>
        <div class="field"><label>Arrival <span class="req">*</span></label><input class="input" type="date" data-k="arrival_date" required></div>
        <div class="field"><label>Departure <span class="req">*</span></label><input class="input" type="date" data-k="departure_date" required></div>
        <div class="field"><label>Nights</label><input class="input" data-k="nights" readonly tabindex="-1"></div>
        <div class="field"><label>Rate / night <span class="req">*</span></label><input class="input" type="number" step="0.01" min="0" data-k="rate" required></div>
      </div>
      <div class="form-section" style="margin-top:.2rem">Occupant <span class="small muted">(who will sleep in this room — optional)</span></div>
      <div class="form-grid">
        <div class="field"><label>First name</label><input class="input" maxlength="80" data-k="occupant_first_name" placeholder="Assigned at check-in"></div>
        <div class="field"><label>Last name</label><input class="input" maxlength="80" data-k="occupant_last_name"></div>
        <div class="field"><label>Mobile</label><input class="input" maxlength="30" data-k="occupant_phone"></div>
        <div class="field"><label>Email</label><input class="input" type="email" maxlength="150" data-k="occupant_email"></div>
      </div>
    </div>
  </section>
</template>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
const plans = @json($ratePlans->groupBy('room_type_id'));
const initial = @json(old('rooms', [[]]));
const linesHost = document.getElementById('groupLines');
const lineTpl = document.getElementById('groupLineTpl');
const defaultArrival = @json($defaults['arrival_date']);
const defaultDeparture = @json($defaults['departure_date']);
const businessToday = @json(bdate()->toDateString());
const companyField = document.getElementById('f_company_id');
// An account opened from this form joins the picker and is selected straight away,
// so the room availability and the rate quotes refresh for it at once — exactly
// what the booking desk does with its own create dialog.
document.addEventListener('city-ledger-created', event => {
  const account = event.detail;
  if (!companyField || !account?.id) return;
  let option = [...companyField.options].find(item => item.value === String(account.id));
  if (!option) {
    option = document.createElement('option');
    option.value = String(account.id);
    option.textContent = account.name;
    companyField.append(option);
  }
  companyField.value = String(account.id);
  companyField.dispatchEvent(new Event('change', { bubbles: true }));
  const status = document.getElementById('grpCompanyStatus');
  if (status) {
    status.textContent = `${account.name} was created and selected. The direct-billing account is available in Finance → City Ledger.`;
    status.hidden = false;
  }
});

function nextDate(value){
  if (!value) return '';
  const [y, m, d] = value.split('-').map(Number);
  const next = new Date(Date.UTC(y, m - 1, d + 1));
  return `${next.getUTCFullYear()}-${String(next.getUTCMonth() + 1).padStart(2, '0')}-${String(next.getUTCDate()).padStart(2, '0')}`;
}
function lineNights(tr){
  const a = tr.querySelector('[data-k=arrival_date]').value, d = tr.querySelector('[data-k=departure_date]').value;
  if (!a || !d) return 0;
  const start = Date.parse(`${a}T00:00:00Z`), end = Date.parse(`${d}T00:00:00Z`);
  return Number.isFinite(start) && Number.isFinite(end) ? Math.max(0, Math.round((end - start) / 86400000)) : 0;
}
function reindex(){
  [...linesHost.querySelectorAll('[data-line]')].forEach((tr, i) => {
    tr.querySelector('.group-line-title').textContent = `Room ${i + 1}`;
    tr.querySelectorAll('[data-k]').forEach(el => { el.name = `rooms[${i}][${el.dataset.k}]`; });
  });
  document.getElementById('groupRoomCount').textContent = `${linesHost.querySelectorAll('[data-line]').length} room(s)`;
}
function option(label, value = ''){ const el = document.createElement('option'); el.value = value; el.textContent = label; return el; }
function fillPlans(tr, keep){
  const type = tr.querySelector('[data-k=room_type_id]').value, sel = tr.querySelector('[data-k=rate_plan_id]');
  const options = [option('Rack rate')];
  (plans[type] || []).forEach(plan => { const el = option(`${plan.name} – ${plan.rate}`, String(plan.id)); el.dataset.rate = plan.rate; options.push(el); });
  sel.replaceChildren(...options);
  if (keep) sel.value = keep;
}
function lineTotal(tr){ const nights = lineNights(tr), rate = parseFloat(tr.querySelector('[data-k=rate]').value) || 0; return nights * rate; }
function totals(){
  linesHost.querySelectorAll('[data-line]').forEach(tr => {
    tr.querySelector('[data-k=nights]').value = lineNights(tr) || '';
    const a = tr.querySelector('[data-k=arrival_date]').value, d = tr.querySelector('[data-k=departure_date]').value;
    tr.querySelector('.group-line-dates').textContent = a && d ? `${a} → ${d}` : '';
    tr.querySelector('.group-line-total').textContent = lineTotal(tr).toFixed(2);
  });
  const grand = [...linesHost.querySelectorAll('[data-line]')].reduce((sum, tr) => sum + lineTotal(tr), 0);
  document.getElementById('groupGrandTotal').textContent = grand.toFixed(2);
}
function loadRooms(tr, keep){
  const type = tr.querySelector('[data-k=room_type_id]').value, sel = tr.querySelector('[data-k=room_id]');
  const cur = keep ?? tr.dataset.selectedRoom ?? sel.value;
  const a = tr.querySelector('[data-k=arrival_date]').value, d = tr.querySelector('[data-k=departure_date]').value;
  if (!type || !a || !d || d <= a) { sel.replaceChildren(option('Choose room type and dates')); return; }
  const params = new URLSearchParams({ room_type_id: type, from: a, to: d, company_id: companyField?.value || '' });
  sel.disabled = true;
  sel.replaceChildren(option('Checking room availability…'));
  fetch(`{{ route('reservations.api.rooms') }}?${params.toString()}`, { headers: { Accept: 'application/json' } })
    .then(r => r.json())
    .then(list => {
      if (!Array.isArray(list)) throw new Error('Invalid room availability response');
      const options = list.length ? [option('Auto-assign')] : [option('No rooms available for these dates')];
      list.forEach(room => options.push(option(`${room.room_no}${room.hk === 'dirty' ? ' (dirty)' : ''}`, String(room.id))));
      if (cur && !list.some(room => String(room.id) === String(cur))) options.push(option(`#${cur} (current booking)`, String(cur)));
      sel.replaceChildren(...options);
      if (cur) sel.value = String(cur);
      tr.dataset.selectedRoom = sel.value;
    })
    .catch(() => { sel.replaceChildren(option('Could not load rooms — change dates to retry')); })
    .finally(() => { sel.disabled = false; });
}
function quoteRate(tr){
  const type = tr.querySelector('[data-k=room_type_id]').value, a = tr.querySelector('[data-k=arrival_date]').value, d = tr.querySelector('[data-k=departure_date]').value;
  if (!type || !a || !d || d <= a || tr.dataset.rateLocked === '1') return;
  const adults = tr.querySelector('[data-k=adults]').value || 1;
  const params = new URLSearchParams({ room_type_id: type, from: a, to: d, company_id: companyField?.value || '', adults });
  fetch(`{{ route('reservations.api.rates') }}?${params.toString()}`, { headers: { Accept: 'application/json' } })
    .then(r => r.json())
    .then(quote => {
      if (quote && Number.isFinite(Number(quote.rate))) {
        tr.querySelector('[data-k=rate]').value = quote.rate;
        tr.querySelector('[data-k=rate]').title = quote.contract ? `Contract ${quote.contract.code} (public rate ${quote.contract.public_rate})` : (quote.seasons?.length ? 'Season: ' + quote.seasons.join(', ') : 'Rack rate');
        totals();
      }
    })
    .catch(() => {});
}
function addGroupLine(d = {}){
  const tr = lineTpl.content.firstElementChild.cloneNode(true);
  linesHost.appendChild(tr);
  const type = tr.querySelector('[data-k=room_type_id]'), rate = tr.querySelector('[data-k=rate]');
  const aField = tr.querySelector('[data-k=arrival_date]'), dField = tr.querySelector('[data-k=departure_date]');
  aField.value = d.arrival_date || defaultArrival;
  dField.value = d.departure_date || defaultDeparture;
  aField.min = businessToday;
  dField.min = businessToday;
  if (d.adults) tr.querySelector('[data-k=adults]').value = d.adults;
  if (d.children) tr.querySelector('[data-k=children]').value = d.children;
  if (d.occupant_first_name) tr.querySelector('[data-k=occupant_first_name]').value = d.occupant_first_name;
  if (d.occupant_last_name) tr.querySelector('[data-k=occupant_last_name]').value = d.occupant_last_name;
  if (d.occupant_phone) tr.querySelector('[data-k=occupant_phone]').value = d.occupant_phone;
  if (d.occupant_email) tr.querySelector('[data-k=occupant_email]').value = d.occupant_email;
  if (d.room_type_id) { type.value = d.room_type_id; fillPlans(tr, d.rate_plan_id); }
  rate.value = d.rate ?? (type.selectedOptions[0]?.dataset.rate || '');
  const refresh = () => { loadRooms(tr); quoteRate(tr); totals(); };
  type.addEventListener('change', () => {
    tr.dataset.rateLocked = '0';
    fillPlans(tr);
    rate.value = type.selectedOptions[0]?.dataset.rate || '';
    tr.dataset.selectedRoom = '';
    refresh();
  });
  tr.querySelector('[data-k=rate_plan_id]').addEventListener('change', e => {
    tr.dataset.rateLocked = '0';
    rate.value = e.target.selectedOptions[0]?.dataset.rate || type.selectedOptions[0]?.dataset.rate || '';
    quoteRate(tr); totals();
  });
  tr.querySelector('[data-k=room_id]').addEventListener('change', e => { tr.dataset.selectedRoom = e.target.value; });
  [aField, dField].forEach(input => input.addEventListener('change', () => {
    dField.min = aField.value ? nextDate(aField.value) : '';
    if (aField.value && dField.value && dField.value <= aField.value) dField.value = nextDate(aField.value);
    refresh();
  }));
  tr.querySelector('[data-k=adults]').addEventListener('change', () => quoteRate(tr));
  rate.addEventListener('input', () => { tr.dataset.rateLocked = '1'; totals(); });
  tr.querySelector('.group-line-remove').addEventListener('click', () => { tr.remove(); reindex(); totals(); });
  reindex();
  if (d.room_type_id) loadRooms(tr, d.room_id);
  totals();
}
(initial.length ? initial : [{}]).forEach(addGroupLine);
document.getElementById('applyWindowDates').addEventListener('click', () => {
  const a = document.querySelector('[name=arrival_date]').value, d = document.querySelector('[name=departure_date]').value;
  linesHost.querySelectorAll('[data-line]').forEach(tr => {
    tr.querySelector('[data-k=arrival_date]').value = a;
    tr.querySelector('[data-k=departure_date]').value = d;
    tr.dataset.selectedRoom = '';
    loadRooms(tr); quoteRate(tr);
  });
  totals();
});
companyField?.addEventListener('change', () => linesHost.querySelectorAll('[data-line]').forEach(tr => { quoteRate(tr); loadRooms(tr); }));
document.querySelector('[name=arrival_date]')?.addEventListener('change', e => {
  document.querySelector('[name=departure_date]').min = e.target.value ? nextDate(e.target.value) : '';
});
</script>
@endpush
@endsection
