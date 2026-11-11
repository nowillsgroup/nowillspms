<?php

namespace App\Http\Controllers;

use App\Models\BusinessSource;
use App\Models\Company;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\PaymentMode;
use App\Models\RateContract;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\ActivityLogger;
use App\Services\AvailabilityService;
use App\Services\ContractService;
use App\Services\FolioService;
use App\Services\GroupBillService;
use App\Services\ReservationAmendmentService;
use App\Support\Numbering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Group reservations: several rooms on one booking, made for a person or a
 * company, where every room keeps its own dates and its own occupant.
 *
 * The booking itself is the same shape as an individual reservation (booking_type
 * 'group'), so everything downstream — tape chart, arrivals, GRC, night audit,
 * folios — already understands it. What is different here:
 *
 *  - each room line carries its own arrival / departure dates (people arrive on
 *    different days and leave on different days);
 *  - each room line names (or is later assigned) the guest who will sleep in it,
 *    and that stay's folio, GRC and invoice bear the occupant's name;
 *  - a group billed to a company/city ledger folds every room's bill into one
 *    reservation-level master folio at checkout, so the invoice shows every
 *    room and who slept in it — while still being one bill for the company.
 */
class GroupReservationController extends Controller
{
    public function create(Request $request)
    {
        $defaultArrival = $request->input('arrival', bdate()->toDateString());
        $defaultDeparture = $request->input('departure', bdate()->addDay()->toDateString());

        return view('reservations.group', [
            'roomTypes' => RoomType::where('is_active', true)->orderBy('base_rate')->get(),
            'ratePlans' => RatePlan::where('is_active', true)->get(),
            'sources' => BusinessSource::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'companies' => Company::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'paymentModes' => PaymentMode::where('is_active', true)->where('type', '!=', 'city_ledger')->pluck('name', 'id'),
            'defaults' => ['arrival_date' => $defaultArrival, 'departure_date' => $defaultDeparture],
            'lines' => old('rooms', [[]]),
            'guest' => new Guest,
        ]);
    }

    protected function rules(): array
    {
        return [
            'guest_id' => 'nullable|integer',
            'guest.title' => 'nullable|string|max:30',
            'guest.custom_title' => 'required_if:guest.title,__other__|nullable|string|max:30|not_in:__other__',
            'guest.first_name' => 'required|string|max:80',
            'guest.last_name' => 'nullable|string|max:80',
            'guest.phone' => 'required|string|max:30',
            'guest.email' => 'nullable|email|max:150',
            'guest.nationality' => 'nullable|string|max:60',
            'guest.city' => 'nullable|string|max:80',
            'guest.id_type' => 'nullable|string|max:30',
            'guest.id_number' => 'nullable|string|max:60',
            'group_name' => 'required|string|max:150',
            'company_id' => 'nullable|integer|owned:companies',
            'business_source_id' => 'nullable|integer|owned:business_sources',
            'billing_instruction' => 'required|in:guest,company',
            'arrival_date' => ['required', 'date', 'after_or_equal:'.bdate()->toDateString()],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
            'eta' => 'nullable|date_format:H:i',
            'status' => 'required|in:confirmed,tentative',
            'ota_reference' => 'nullable|string|max:60',
            'purpose' => 'nullable|string|max:60',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'special_request' => 'nullable|string|max:2000',
            'rooms' => 'required|array|min:1|max:50',
            'rooms.*.room_type_id' => 'required|integer|owned:room_types',
            'rooms.*.rate_plan_id' => 'nullable|integer|owned:rate_plans',
            'rooms.*.room_id' => 'nullable|integer|owned:rooms',
            'rooms.*.rate' => 'required|numeric|min:0|max:9999999',
            'rooms.*.adults' => 'required|integer|min:1|max:10',
            'rooms.*.children' => 'nullable|integer|min:0|max:10',
            'rooms.*.arrival_date' => ['required', 'date', 'after_or_equal:'.bdate()->toDateString()],
            // Laravel does not interpolate wildcards in after:, so the per-line
            // ordering is enforced with a closure against the sibling input.
            'rooms.*.departure_date' => ['required', 'date', function (string $attribute, $value, \Closure $fail) {
                $index = explode('.', $attribute)[1] ?? null;
                $arrival = (string) ($index !== null ? request()->input("rooms.{$index}.arrival_date") : '');
                if ($arrival !== '' && strcmp((string) $value, $arrival) <= 0) {
                    $fail('Each room’s departure must be after its arrival.');
                }
            }],
            'rooms.*.occupant_first_name' => 'nullable|string|max:80',
            'rooms.*.occupant_last_name' => 'nullable|string|max:80',
            'rooms.*.occupant_phone' => 'nullable|string|max:30',
            'rooms.*.occupant_email' => 'nullable|email|max:150',
            'advance' => 'nullable|numeric|min:0',
            'advance_mode' => 'nullable|integer|owned:payment_modes',
            'advance_ref' => 'nullable|string|max:80',
            'cheque_bank_name' => 'nullable|string|max:100', 'cheque_number' => 'nullable|string|max:20', 'cheque_bank_branch' => 'nullable|string|max:100',
        ];
    }

    protected function resolveGuestTitle(array $data): array
    {
        if (($data['guest']['title'] ?? null) === '__other__') {
            $data['guest']['title'] = trim((string) ($data['guest']['custom_title'] ?? ''));
        }
        unset($data['guest']['custom_title']);

        return $data;
    }

    /** The booking contact — same rules the walk-in and booking desks apply. */
    protected function upsertGuest(array $g, ?int $guestId, ?int $companyId): Guest
    {
        $guest = $guestId ? Guest::find($guestId) : null;
        if ($guest?->isPrivacyLocked()) {
            $guest = null;
        }
        if (! $guest && ! empty($g['phone'])) {
            $guest = Guest::whereNull('privacy_status')->where('phone', $g['phone'])->where('first_name', $g['first_name'])->first();
        }
        $guest ??= new Guest;
        $guest->fill(array_filter($g, fn ($v) => $v !== null && $v !== ''));
        if ($companyId && ! $guest->company_id) {
            $guest->company_id = $companyId;
        }
        $guest->save();
        if ($guest->is_blacklisted) {
            throw ValidationException::withMessages(['guest.first_name' => 'This guest is blacklisted. Reservation blocked.']);
        }

        return $guest;
    }

    /**
     * The bed (occupant) for a room line. Occupants with no booking-time name
     * are left unassigned — the desk assigns them at check-in.
     */
    protected function occupantGuest(array $line, ?int $companyId): ?Guest
    {
        $first = trim((string) ($line['occupant_first_name'] ?? ''));
        if ($first === '') {
            return null;
        }
        $last = trim((string) ($line['occupant_last_name'] ?? ''));
        $phone = trim((string) ($line['occupant_phone'] ?? ''));
        $email = trim((string) ($line['occupant_email'] ?? ''));

        $guest = null;
        if ($phone !== '') {
            $guest = Guest::whereNull('privacy_status')->where('phone', $phone)->where('first_name', $first)->first();
        }
        if (! $guest && $email !== '') {
            $guest = Guest::whereNull('privacy_status')->where('email', $email)->where('first_name', $first)->first();
        }
        $guest ??= new Guest;
        $guest->fill(array_filter([
            'first_name' => $first, 'last_name' => $last ?: null,
            'phone' => $phone ?: null, 'email' => $email ?: null,
            'company_id' => $companyId,
        ], fn ($v) => $v !== null && $v !== ''));
        $guest->save();

        return $guest;
    }

    /** Extra adult charge for a line (single / double basis, or the company contract). */
    protected function lineExtra(array $line, ?RateContract $contract = null): float
    {
        $type = RoomType::find($line['room_type_id']);
        $plan = ! empty($line['rate_plan_id']) ? RatePlan::find($line['rate_plan_id']) : null;
        $extraAdults = max(0, (int) $line['adults'] - (int) ($type->base_occupancy ?? 2));
        $contractExtra = ! $plan && $contract ? $contract->rates->where('room_type_id', $type->id)->whereNotNull('extra_adult_rate')->first()?->extra_adult_rate : null;

        return $extraAdults * (float) ($plan->extra_adult_rate ?? $contractExtra ?? $type->extra_adult_rate);
    }

    /** The contract and bill-to-company guards (same policy as the booking desk). */
    protected function contractAndCredit(array $data): ?RateContract
    {
        if (($data['billing_instruction'] ?? 'guest') === 'company' && empty($data['company_id'])) {
            throw ValidationException::withMessages(['company_id' => 'Choose the company to bill to the city ledger, or set Bill to Guest.']);
        }
        $company = ! empty($data['company_id']) ? Company::find($data['company_id']) : null;
        if ($company && ($data['billing_instruction'] ?? 'guest') === 'company') {
            if (! $company->direct_billing) {
                throw ValidationException::withMessages(['billing_instruction' => "{$company->name} is not approved for direct billing – choose Bill to Guest."]);
            }
            if ($company->onHold() && ! can_do('credit.override')) {
                throw ValidationException::withMessages(['billing_instruction' => "{$company->name} is on credit hold".($company->credit_hold_reason ? " ({$company->credit_hold_reason})" : '').' – choose Bill to Guest or ask Accounts to release it.']);
            }
        }

        return ContractService::forCompany($company?->id, $data['arrival_date'])?->load('rates', 'blackouts');
    }

    protected function lockInventory(array $lines): void
    {
        $typeIds = collect($lines)->pluck('room_type_id')->filter()->map(fn ($id) => (int) $id);
        $roomIds = collect($lines)->pluck('room_id')->filter()->map(fn ($id) => (int) $id);
        if ($roomIds->isNotEmpty()) {
            $typeIds = $typeIds->merge(Room::whereIn('id', $roomIds)->pluck('room_type_id'));
        }
        AvailabilityService::lockRoomInventory($typeIds->unique()->values());
    }

    /**
     * Availability for lines whose dates differ from each other. A plain
     * reservation-level check would let two 8-room holds over the same fortnight
     * through, so every distinct date segment is checked against the remaining
     * inventory.
     */
    protected function checkGroupInventory(array $lines, ?int $contractId = null): void
    {
        foreach ($lines as $line) {
            $type = RoomType::findOrFail((int) $line['room_type_id']);
            $quote = $contractId
                ? ContractService::quote(RateContract::with('rates', 'blackouts')->findOrFail($contractId), $type, $line['arrival_date'], $line['departure_date'], 1)
                : \App\Services\RateService::quote($type, $line['arrival_date'], $line['departure_date']);
            if ($quote['stop_sell']) {
                throw ValidationException::withMessages(['rooms' => "{$type->name} is closed for sale on the selected dates."]);
            }
            if (! $quote['min_stay_ok']) {
                throw ValidationException::withMessages(['rooms' => "A minimum stay of {$quote['min_stay']} night(s) applies to {$type->name}."]);
            }
        }

        $byType = [];
        foreach ($lines as $line) {
            $byType[(int) $line['room_type_id']][] = $line;
        }
        foreach ($byType as $typeId => $typeLines) {
            $bounds = collect($typeLines)->flatMap(fn ($l) => [$l['arrival_date'], $l['departure_date']])->unique()->sort()->values();
            for ($i = 0; $i + 1 < $bounds->count(); $i++) {
                $segFrom = $bounds[$i];
                $segTo = $bounds[$i + 1];
                $need = count(array_filter($typeLines, fn ($l) => $l['arrival_date'] < $segTo && $l['departure_date'] > $segFrom));
                if ($need === 0) {
                    continue;
                }
                $avail = AvailabilityService::typeAvailable($typeId, $segFrom, $segTo, null, $contractId);
                if ($need > $avail) {
                    $name = RoomType::find($typeId)?->name ?? 'room type';
                    throw ValidationException::withMessages(['rooms' => "Only {$avail} {$name} room(s) available for {$segFrom} – {$segTo} (requested {$need}). Overbooking is not allowed."]);
                }
            }
        }

        $ids = collect($lines)->pluck('room_id')->filter()->map(fn ($id) => (int) $id);
        if ($ids->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages(['rooms' => 'The same room is assigned twice.']);
        }
        foreach (collect($lines)->filter(fn ($line) => ! empty($line['room_id'])) as $line) {
            $roomId = (int) $line['room_id'];
            if (! AvailabilityService::roomAvailableForReservation($roomId, $line['arrival_date'], $line['departure_date'], null)) {
                $roomNo = Room::whereKey($roomId)->value('room_no') ?? $roomId;
                throw ValidationException::withMessages(['rooms' => "Room {$roomNo} is not available for its selected dates."]);
            }
        }
    }

    public function store(Request $request)
    {
        $data = $this->resolveGuestTitle($request->validate($this->rules()));
        if (($data['advance'] ?? 0) > 0 && empty($data['advance_mode'])) {
            throw ValidationException::withMessages(['advance_mode' => 'Enter an amount and choose a payment mode.']);
        }
        if (($data['advance'] ?? 0) > 0) {
            $mode = \App\Models\PaymentMode::find((int) $data['advance_mode']);
            if (! $mode || ! $mode->is_active || $mode->type === 'city_ledger') {
                throw ValidationException::withMessages(['advance_mode' => 'Choose an active direct payment mode.']);
            }
        }
        $contract = $this->contractAndCredit($data);

        $overallArrival = collect($data['rooms'])->min('arrival_date');
        $overallDeparture = collect($data['rooms'])->max('departure_date');

        $reservation = DB::transaction(function () use ($data, $contract, $overallArrival, $overallDeparture) {
            $this->lockInventory($data['rooms']);
            $this->checkGroupInventory($data['rooms'], $contract?->id);
            $guest = $this->upsertGuest($data['guest'], $data['guest_id'] ?? null, $data['company_id'] ?? null);

            $res = Reservation::create([
                'reservation_no' => Numbering::next('reservation'),
                'guest_id' => $guest->id,
                'booking_type' => 'group',
                'group_name' => trim($data['group_name']),
                'arrival_date' => $overallArrival,
                'departure_date' => $overallDeparture,
                'eta' => $data['eta'] ?? null,
                'adults' => collect($data['rooms'])->sum('adults'),
                'children' => collect($data['rooms'])->sum('children'),
                'created_by' => auth()->id(),
                'rate_contract_id' => $contract?->id,
            ] + collect($data)->only(['company_id', 'business_source_id', 'status', 'ota_reference', 'purpose', 'billing_instruction', 'discount_pct', 'special_request'])->all());

            foreach ($data['rooms'] as $line) {
                $occupant = $this->occupantGuest($line, $data['company_id'] ?? null);
                $res->rooms()->create([
                    'room_type_id' => $line['room_type_id'],
                    'room_id' => $line['room_id'] ?? null,
                    'rate_plan_id' => $line['rate_plan_id'] ?? null,
                    'guest_id' => $occupant?->id,
                    'arrival_date' => $line['arrival_date'],
                    'departure_date' => $line['departure_date'],
                    'rate' => $line['rate'],
                    'adults' => $line['adults'],
                    'children' => $line['children'] ?? 0,
                    'extra_charge' => $this->lineExtra($line, $contract),
                    'status' => 'reserved',
                ]);
            }

            if (($data['advance'] ?? 0) > 0) {
                $folio = FolioService::openStandalone(['reservation_id' => $res->id, 'guest_id' => $guest->id, 'type' => 'guest']);
                FolioService::payment($folio, (float) $data['advance'], (int) ($data['advance_mode'] ?? 0), $data['advance_ref'] ?? null, 'advance', 'payment', [
                    'bank_name' => $data['cheque_bank_name'] ?? null, 'number' => $data['cheque_number'] ?? null, 'bank_branch' => $data['cheque_bank_branch'] ?? null,
                ]);
            }

            $createdState = ReservationAmendmentService::snapshot($res);
            ReservationAmendmentService::record($res, 'created', null, $createdState, null, 'front_desk');
            ActivityLogger::log('reservations', 'create', "Created group reservation {$res->reservation_no} ({$res->group_name}) — {$res->rooms()->count()} room(s)", $res);

            return $res;
        });

        try {
            \App\Services\MailService::autoConfirm($reservation);
            \App\Services\MessageService::autoConfirm($reservation);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "Group reservation {$reservation->reservation_no} created for {$reservation->rooms->count()} room(s). Each room keeps its own dates and occupant — assign or change occupants any time before check-in.");
    }

    /** Assign / change / clear the person using one room of a group reservation. */
    public function updateOccupant(Request $request, ReservationRoom $stay)
    {
        abort_unless(can_do('reservations.edit'), 403);
        $stay->loadMissing('reservation');
        abort_unless($stay->reservation?->booking_type === 'group', 404);
        if ($request->boolean('clear')) {
            $stay->update(['guest_id' => null]);
            // Keep the room bill pointed at the booking contact again.
            Folio::where('reservation_room_id', $stay->id)->where('status', 'open')
                ->update(['guest_id' => $stay->reservation->guest_id]);
            ActivityLogger::log('reservations', 'occupant', "Unassigned the occupant of stay #{$stay->id} ({$stay->reservation?->reservation_no})", $stay);

            return back()->with('success', 'Occupant unassigned. The room will be assigned at check-in.');
        }
        $data = $request->validate([
            'occupant.first_name' => 'required|string|max:80',
            'occupant.last_name' => 'nullable|string|max:80',
            'occupant.phone' => 'nullable|string|max:30',
            'occupant.email' => 'nullable|email|max:150',
            'occupant.id_type' => 'nullable|string|max:30',
            'occupant.id_number' => 'nullable|string|max:60',
        ]);

        $guest = $stay->guest_id ? Guest::whereKey($stay->guest_id)->first() : null;
        if ($guest?->isPrivacyLocked()) {
            $guest = null; // never rewrite a restricted profile; start a fresh one
        }
        if (! $guest && ! empty($data['occupant']['phone'])) {
            $guest = Guest::whereNull('privacy_status')->where('phone', $data['occupant']['phone'])
                ->where('first_name', $data['occupant']['first_name'])->first();
        }
        $guest ??= new Guest;
        $guest->fill(array_filter($data['occupant'], fn ($v) => $v !== null && $v !== ''));
        if ($stay->reservation->company_id && ! $guest->company_id) {
            $guest->company_id = $stay->reservation->company_id;
        }
        $guest->save();
        $stay->update(['guest_id' => $guest->id]);
        // The open room folio (if any — the stay may not have checked in yet) carries
        // the occupant too, so the bill / invoice names follow mid-stay changes.
        Folio::where('reservation_room_id', $stay->id)->where('status', 'open')
            ->update(['guest_id' => $guest->id]);
        ActivityLogger::log('reservations', 'occupant', "Assigned {$guest->full_name} to stay #{$stay->id} ({$stay->reservation?->reservation_no})", $stay);

        return back()->with('success', $guest->full_name.' assigned to '.($stay->room?->room_no ? 'Room '.$stay->room->room_no : 'stay').'. The room bill and registration card will name them.');
    }

    /**
     * Prepare the combined group bill: fold any still-open (already departed)
     * room folios into the reservation master folio. Check-out does this
     * automatically for company-billed groups; this action catches anything left.
     */
    public function combineBill(Reservation $reservation)
    {
        abort_unless(can_do('folio.split'), 403);
        abort_unless($reservation->booking_type === 'group', 422);
        $stays = $reservation->rooms()->get(['id', 'status']);
        $allDeparted = $stays->count() >= 2
            && $stays->every(fn ($s) => in_array($s->status, ['checked_out', 'cancelled', 'no_show'], true));
        if (! $allDeparted) {
            return back()->with('error', 'The combined bill is available once every room has checked out, cancelled or been marked no-show.');
        }
        try {
            $master = GroupBillService::foldAll($reservation, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        ActivityLogger::log('folio', 'combine', "Prepared the combined group bill {$master->folio_no} for {$reservation->reservation_no}", $reservation);

        return redirect()->route('folios.show', $master)
            ->with('success', 'Combined group bill prepared. Review it, settle the balance (city-ledger transfer or payment) and close it to issue the one invoice.');
    }
}
