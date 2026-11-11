@extends('layouts.app')
@section('title', 'Employee file · '.$e->name)
@section('crumbs') <a href="{{ route('hr.register') }}">HR</a> / <a href="{{ route('masters.index', 'employees') }}">Employees</a> / {{ $e->emp_code }} @endsection
@section('actions')
<button class="btn no-print" type="button" onclick="window.print()"><x-icon name="print"/> Print file</button>
<a class="btn no-print" href="{{ route('masters.edit', ['employees', $e->id]) }}"><x-icon name="edit"/> Edit employee</a>
<a class="btn no-print" href="{{ route('payroll.employee', $e) }}"><x-icon name="wallet"/> Pay setup</a>
@endsection
@push('head')
<style>
.hr-file{display:grid;grid-template-columns:168px minmax(0,1fr);gap:20px;align-items:start}
.hr-photo-wrap{display:grid;gap:8px;justify-items:center}
.hr-photo{display:block;width:168px;height:168px;object-fit:cover;border:1px solid var(--c-border);border-radius:14px;background:var(--c-bg)}
.hr-photo-empty{display:grid;place-items:center;color:#fff;font-size:44px;font-weight:800;letter-spacing:.02em;background:linear-gradient(135deg,#17553a,#2f8a5d)}
.hr-photo-form{display:grid;gap:6px;width:100%}
.hr-photo-form input[type=file]{font-size:.72rem}
.hr-identity h2{margin:0;font-size:1.15rem;letter-spacing:-.01em}
.hr-identity .hr-role{margin:.15rem 0 .6rem;color:var(--c-muted);font-size:.84rem}
.hr-facts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px 16px;margin:0}
.hr-facts div{min-width:0}
.hr-facts dt{color:var(--c-muted);font-size:.64rem;font-weight:750;letter-spacing:.06em;text-transform:uppercase}
.hr-facts dd{margin:1px 0 0;font-size:.82rem;overflow-wrap:anywhere}
.hr-month{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;align-items:center}
.hr-month span{padding:3px 8px;border:1px solid var(--c-border);border-radius:999px;background:var(--c-surface);font-size:.68rem;color:var(--c-text-2)}
.hr-month b{color:var(--c-text)}
.hr-doc-row-form{display:inline}
.hr-drop{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,.8fr) minmax(0,1fr) auto;gap:10px;align-items:end}
.hr-grid{margin-top:1rem}
.hr-main,.hr-side{display:grid;gap:1rem;align-content:start}
.hr-timeline li .hr-tl-date{font-size:.72rem;color:var(--c-muted)}
.timeline li.tl-warn::before{background:#d97706}
.timeline li.tl-muted::before{background:#cbd5e1}
.hr-ob{list-style:none;margin:.6rem 0 0;padding:0;display:grid;gap:.35rem}
.hr-ob li{display:flex;gap:.5rem;align-items:flex-start;font-size:.8rem;padding:.32rem .4rem;border-radius:8px}
.hr-ob li:hover{background:var(--c-bg)}
.hr-ob li .i{width:15px;height:15px;flex:0 0 15px;margin-top:.15rem;color:#94a3b8}
.hr-ob li.done .i{color:#16a34a}
.hr-ob li.done span{color:var(--c-muted);text-decoration:line-through;text-decoration-color:#cbd5e1}
.hr-ob li.overdue .i{color:#dc2626}
.hr-ob li small{display:block;font-size:.66rem;color:var(--c-muted);text-decoration:none}
.hr-ob li.overdue small{color:#dc2626;font-weight:600}
.hr-train-row{display:flex;gap:.55rem;align-items:baseline;padding:.4rem 0;border-bottom:1px dashed var(--c-border)}
.hr-train-row:last-child{border-bottom:0;padding-bottom:0}
.hr-train-row b{font-size:.8rem}
.hr-warn{display:grid;gap:2px;padding:.5rem .65rem;margin-bottom:.55rem;border:1px solid #fcd9a8;background:#fffbeb;border-radius:10px;font-size:.74rem}
.hr-warn .i{width:14px;height:14px;color:#d97706;vertical-align:-2px}
.hr-adv{display:grid;gap:.4rem}
.hr-adv-row{display:flex;justify-content:space-between;gap:.6rem;font-size:.8rem;border-bottom:1px dashed var(--c-border);padding-bottom:.35rem}
.hr-adv-row:last-child{border-bottom:0;padding-bottom:0}
.hr-payslip{display:flex;align-items:center;gap:.65rem}
.hr-payslip .i{width:26px;height:26px;color:var(--c-primary);flex:0 0 26px}
.hr-case{display:flex;gap:.6rem;align-items:flex-start}
.hr-case .i{width:20px;height:20px;flex:0 0 20px;color:#16a34a}
.hr-case.open .i{color:#dc2626}
.hr-appraisal-score{display:flex;align-items:baseline;gap:.6rem;margin-top:.4rem}
.hr-appraisal-score .v{font-size:1.6rem;font-weight:750;letter-spacing:-.02em}
@media(max-width:1100px){.hr-grid{grid-template-columns:1fr}}
@media(max-width:900px){.hr-file{grid-template-columns:1fr}.hr-facts{grid-template-columns:repeat(2,minmax(0,1fr))}.hr-drop{grid-template-columns:1fr}}
@media print{
  .no-print,.hr-photo-form,.hr-doc-row-form,.card.flush .card-body table .right{display:none !important}
  .hr-grid{grid-template-columns:1fr;margin-top:.5rem}
  .card{box-shadow:none;border:1px solid #d4d4d4;break-inside:avoid;margin-bottom:.6rem}
  .hr-ob li.done span{text-decoration:none;color:#111}
}
</style>
@endpush

@section('content')

<div class="card"><div class="card-body hr-file">
  <div class="hr-photo-wrap">
    @if($photoUrl)
      <img class="hr-photo" src="{{ $photoUrl }}" alt="Photograph of {{ $e->name }}" width="168" height="168">
    @else
      <span class="hr-photo hr-photo-empty" role="img" aria-label="No photograph loaded for {{ $e->name }}">{{ $e->initials() }}</span>
    @endif
    @if(can_do('hr.manage'))
      <form class="hr-photo-form no-print" method="post" action="{{ route('hr.employee.photo.save', $e) }}" enctype="multipart/form-data">@csrf
        <label class="sr-only" for="hrPhoto">Photograph for {{ $e->name }}</label>
        <input id="hrPhoto" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
        <button class="btn btn-sm btn-primary"><x-icon name="upload"/> {{ $photoUrl ? 'Replace photo' : 'Load photo' }}</button>
      </form>
      @if($photoUrl)
        <form class="no-print" method="post" action="{{ route('hr.employee.photo.remove', $e) }}">@csrf
          <button class="btn btn-sm btn-ghost"><x-icon name="x"/> Remove</button>
        </form>
      @endif
      <span class="tiny muted no-print">JPEG, PNG or WebP · up to 5 MB</span>
    @endif
  </div>

  <div class="hr-identity">
    <h2>{{ $e->name }} <span class="badge {{ $e->is_active ? 'badge-green' : 'badge-slate' }}">{{ $e->is_active ? 'Active' : 'Inactive' }}</span></h2>
    <p class="hr-role">{{ $e->designation ?? 'Designation not recorded' }}@if($e->department) · {{ $e->department->name }}@endif@if($service) · <b>{{ $service }}</b> in service@endif</p>
    <dl class="hr-facts">
      <div><dt>Employee no.</dt><dd>{{ $e->emp_code }}</dd></div>
      <div><dt>Joined</dt><dd>{{ $e->joining_date ? dmy($e->joining_date) : '—' }}@if($e->leaving_date) · left {{ dmy($e->leaving_date) }}@endif</dd></div>
      <div><dt>Employment</dt><dd>{{ ['permanent' => 'Permanent', 'fixed_term' => 'Fixed-term', 'weekly' => 'Weekly-paid', 'casual' => 'Casual / daily-rated'][$e->employment_type] ?? ucfirst((string) $e->employment_type) }}</dd></div>
      <div><dt>Phone</dt><dd>{{ $e->phone ?? '—' }}</dd></div>
      <div><dt>E-mail</dt><dd>{{ $e->email ?? '—' }}</dd></div>
      <div><dt>TIN</dt><dd>{{ $e->tax_id ?? '—' }}</dd></div>
      <div><dt>SSNIT</dt><dd>{{ $e->social_security_no ?? '—' }}</dd></div>
      <div><dt>Login</dt><dd>{{ $e->user_id ? 'Linked to a user' : 'No login linked' }}</dd></div>
    </dl>
    <div class="hr-month" aria-label="Attendance this month">
      <span>This month · <b>{{ $month->format('F Y') }}</b></span>
      <span>Present <b>{{ $monthStatus['P'] ?? 0 }}</b></span>
      <span>Absent <b>{{ $monthStatus['A'] ?? 0 }}</b></span>
      <span>Leave <b>{{ $monthStatus['L'] ?? 0 }}</b></span>
      <span>Half day <b>{{ $monthStatus['H'] ?? 0 }}</b></span>
      <span>Week off <b>{{ $monthStatus['WO'] ?? 0 }}</b></span>
      @if($attendanceRate !== null)<span>Rate <b>{{ $attendanceRate }}%</b></span>@endif
      <a class="tiny no-print" href="{{ route('hr.register') }}">Open the monthly register</a>
    </div>
  </div>
</div></div>

<div class="grid g-2-1 hr-grid">
<div class="hr-main">

  <div class="card">
    <div class="card-head"><h3>Employment life cycle</h3><span class="small muted">Labour Act 651 trail</span></div>
    <div class="card-body"><ul class="timeline">
      @foreach($lifeCycle as $row)
        <li class="tl-{{ $row['tone'] }}"><b>{{ $row['label'] }}</b><div class="hr-tl-date">{{ $row['detail'] }}</div></li>
      @endforeach
    </ul></div>
  </div>

  <div class="card flush">
  <div class="card-head"><h3>Documents</h3><span class="small muted">{{ collect($docs)->count() }} on file · ID, contracts, certificates</span></div>
  <div class="card-body table-wrap"><table class="table table-compact" id="hrDocs">
    <caption class="sr-only">Documents held in the file of {{ $e->name }}</caption>
    <thead><tr><th scope="col">Document</th><th scope="col">Kind</th><th scope="col">From</th><th scope="col">Status</th><th scope="col" class="num">Size</th><th scope="col">Added</th><th scope="col" class="no-print"><span class="sr-only">Actions</span></th></tr></thead>
    <tbody>
    @forelse($docs as $d)
      <tr>
        <td><b>{{ $d->title }}</b>@if($d->review_note)<div class="tiny muted">{{ $d->review_note }}</div>@endif</td>
        <td class="small">{{ $d->kindLabel() }}</td>
        <td><span class="badge {{ $d->source === 'hr' ? 'badge-blue' : 'badge-slate' }}">{{ $d->sourceLabel() }}</span></td>
        <td><span class="badge badge-{{ $d->statusTone() }}">{{ $d->statusLabel() }}</span></td>
        <td class="num small">{{ $d->sizeLabel() }}</td>
        <td class="small">{{ $d->created_at?->format('d M Y') }}</td>
        <td class="right nowrap no-print">
          <a class="btn btn-sm" href="{{ route('hr.document', $d) }}" target="_blank" rel="noopener" aria-label="Open {{ $d->title }}"><x-icon name="eye"/></a>
          @if(can_do('hr.manage') && $d->status === 'new')
            <form class="hr-doc-row-form" method="post" action="{{ route('hr.ess.doc', $d) }}">@csrf<input type="hidden" name="status" value="verified"><button class="btn btn-sm btn-ghost" aria-label="Verify {{ $d->title }}"><x-icon name="check"/></button></form>
            <form class="hr-doc-row-form" method="post" action="{{ route('hr.ess.doc', $d) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-ghost" aria-label="Reject {{ $d->title }}"><x-icon name="x"/></button></form>
          @endif
          @if(can_do('hr.manage'))
            <form class="hr-doc-row-form" method="post" action="{{ route('hr.document.delete', $d) }}" onsubmit="return confirm('Remove {{ $d->title }} from the file? The stored file is deleted too.')">@csrf<button class="btn btn-sm btn-ghost" aria-label="Remove {{ $d->title }}"><x-icon name="trash"/></button></form>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="7" class="muted small">Nothing on file yet — load the contract, the ID and the certificates below.</td></tr>
    @endforelse
    </tbody></table></div>
</div>

@if(can_do('hr.manage'))
<div class="card hr-load-card no-print">
  <div class="card-head"><h3>Load a document</h3><span class="small muted">PDF, JPEG, PNG or WebP · up to 10 MB · HR-loaded papers are marked verified</span></div>
  <div class="card-body">
    <form class="hr-drop" method="post" action="{{ route('hr.employee.documents.save', $e) }}" enctype="multipart/form-data">@csrf
      <div class="field"><label for="hrDocTitle">Title</label><input class="input" id="hrDocTitle" name="title" maxlength="200" placeholder="Signed contract, Ghana Card, food hygiene certificate"></div>
      <div class="field"><label for="hrDocKind">Kind</label><select class="input" id="hrDocKind" name="kind"><option value="">— Kind —</option>@foreach($kinds as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
      <div class="field"><label for="hrDocFile">File</label><input class="input" id="hrDocFile" type="file" name="file" accept="application/pdf,image/jpeg,image/png,image/webp" required></div>
      <div class="field"><button class="btn btn-primary"><x-icon name="upload"/> Load into the file</button></div>
    </form>
    <p class="tiny muted mt-1">A document the member of staff sends through self-service waits on the <a href="{{ route('hr.ess') }}">self-service desk</a> for verification. What HR loads here is filed as verified.</p>
  </div>
</div>
@endif

</div>

<div class="hr-side">

  @if($onboarding)
  <div class="card">
    <div class="card-head"><h3>Onboarding</h3><span class="small muted">{{ $onboarding['done'] }} of {{ $onboarding['total'] }} done</span></div>
    <div class="card-body">
      <div class="cover-bar {{ $onboarding['pct'] >= 100 ? '' : ($onboarding['pct'] >= 60 ? 'mid' : 'low') }}" role="img" aria-label="Onboarding {{ $onboarding['pct'] }}% complete"><span style="width:{{ $onboarding['pct'] }}%"></span></div>
      <ul class="hr-ob">
        @foreach($onboarding['items'] as $item)
          <li class="{{ $item['done'] ? 'done' : ($item['overdue'] ? 'overdue' : '') }}">
            @if($item['done'])<x-icon name="check"/>@else<x-icon name="clock"/>@endif
            <span>{{ $item['label'] }}<small>{{ $item['done'] ? 'Done' : 'Due '.$item['due'] }}{{ $item['overdue'] ? ' · overdue' : '' }}</small></span>
          </li>
        @endforeach
      </ul>
      @if(can_do('talent.recruit') || can_do('talent.appraise'))
        <a class="tiny no-print" href="{{ route('talent.starter', $e) }}">Open the new-starter checklist</a>
      @endif
    </div>
  </div>
  @endif

  @if(collect($leaveRows)->count())
  <div class="card">
    <div class="card-head"><h3>Leave this year</h3><a class="tiny no-print" href="{{ route('leave.index') }}">Leave desk</a></div>
    <div class="card-body leave-bal">
      @foreach($leaveRows as $lb)
        <div>
          <div class="code">{{ $lb['code'] }} · {{ $lb['name'] }}</div>
          <div class="v {{ $lb['low'] ? 'text-red' : '' }}">{{ $lb['value'] }}</div>
          <div class="meta">{{ $lb['meta'] }}</div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-head"><h3>Training &amp; certificates</h3></div>
    <div class="card-body">
      @if(collect($trainingExpiring)->count())
        <div class="hr-warn"><div><x-icon name="alert"/> <b>Expiring within 90 days</b></div>
          @foreach($trainingExpiring as $t)
            <div>{{ $t['course'] }} — <b>{{ $t['expires'] }}</b> · {{ $t['days'] }} {{ $t['days'] === 1 ? 'day' : 'days' }} left</div>
          @endforeach
        </div>
      @endif
      @forelse($trainingRecent as $t)
        <div class="hr-train-row"><span class="badge badge-{{ $t['statusColor'] }}">{{ $t['statusLabel'] }}</span><div><b>{{ $t['course'] }}</b><div class="tiny muted">{{ $t['completed'] }}</div></div></div>
      @empty
        <p class="muted small" style="margin:0">No training recorded yet.</p>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Salary advances</h3><span class="small muted">Outstanding {{ money($advanceOutstanding) }}</span></div>
    <div class="card-body">
      @if(collect($advanceRows)->count())
        <div class="hr-adv">
          @foreach($advanceRows as $a)
            <div class="hr-adv-row"><div><b>{{ $a['no'] }}</b><div class="tiny muted">{{ $a['date'] }} · of {{ money($a['amount']) }}</div></div><div class="num"><b>{{ money($a['outstanding']) }}</b><div class="tiny muted">outstanding</div></div></div>
          @endforeach
        </div>
      @else
        <p class="muted small" style="margin:0">Nothing outstanding.</p>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Latest appraisal</h3>@if(can_do('talent.appraise'))<a class="tiny no-print" href="{{ route('talent.appraisals') }}">Appraisals</a>@endif</div>
    <div class="card-body">
      @if($appraisal)
        <b>{{ $appraisal['cycle'] }}</b>
        <div class="tiny muted">{{ $appraisal['status'] }}</div>
        @if($appraisal['overall'])
          <div class="hr-appraisal-score"><span class="v">{{ $appraisal['overall'] }}</span><span class="small muted">overall@if($appraisal['band']) · {{ $appraisal['band'] }}@endif</span></div>
        @endif
      @else
        <p class="muted small" style="margin:0">No appraisal on file yet.</p>
      @endif
    </div>
  </div>

  @if($lastPayslip)
  <div class="card">
    <div class="card-body hr-payslip">
      <x-icon name="wallet"/>
      <div style="min-width:0"><b>Last payslip · {{ $lastPayslip['label'] }}</b><div class="tiny muted">{{ $lastPayslip['statusLabel'] }} · amounts stay in payroll</div></div>
      <a class="btn btn-sm no-print" style="margin-left:auto" href="{{ route('payroll.employee', $e) }}">Open</a>
    </div>
  </div>
  @endif

  @if($showCases)
  <div class="card">
    <div class="card-body hr-case {{ $openCases > 0 ? 'open' : '' }}">
      <x-icon name="shield"/>
      <div>
        <b>Confidential matters</b>
        @if($openCases > 0)
          <div class="small">{{ $openCases }} open discipline / grievance {{ $openCases === 1 ? 'case' : 'cases' }} — held under privilege on the talent desk.</div>
          @if(can_do('talent.discipline'))<a class="tiny no-print" href="{{ route('talent.cases') }}">Open the cases desk</a>@endif
        @else
          <div class="small muted">No open discipline or grievance matters.</div>
        @endif
      </div>
    </div>
  </div>
  @endif

</div>
</div>
@endsection
