<?php

namespace App\Services;

use App\Models\Folio;
use App\Models\FolioTransaction;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Group master billing. A group reservation billed to a company/city ledger
 * should leave the building under one invoice, not one invoice per room.
 *
 * Each checked-out room's open folio is folded into one reservation-level
 * master folio: the room charges keep their original business date, tax and
 * poster, a reservation_bill_transfers row records where every line came
 * from, and the stay folio stays behind as a permanent, non-invoiced record
 * pointing at the master. The master folio is settled (payment or city-ledger
 * transfer) and then closed as usual, which issues the single invoice.
 *
 * Stays billed to the guest are untouched: they settle and invoice per room
 * exactly as before.
 */
class GroupBillService
{
    /** Fold one stay's open folio into the reservation master folio. Returns the master folio. */
    public static function foldStay(Reservation $reservation, ReservationRoom $stay, ?int $actorId): Folio
    {
        return DB::transaction(function () use ($reservation, $stay, $actorId) {
            $reservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $stay = ReservationRoom::whereKey($stay->id)->lockForUpdate()->firstOrFail();
            if ((int) $stay->reservation_id !== (int) $reservation->id) {
                throw new RuntimeException('A stay does not belong to this reservation.');
            }
            $master = self::masterFor($reservation, true);
            $folio = Folio::where('reservation_id', $reservation->id)
                ->where('reservation_room_id', $stay->id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();
            if (! $folio) {
                // Nothing open for this stay — already settled/closed or folded earlier.
                return $master;
            }
            if ($folio->invoice_no !== null || $folio->consolidated_into_folio_id !== null) {
                throw new RuntimeException('Folio '.$folio->folio_no.' is already invoiced or consolidated.');
            }
            $lines = FolioTransaction::withoutGlobalScope('hotel')->where('folio_id', $folio->id)
                ->orderBy('id')->lockForUpdate()->get();
            if ($lines->contains(fn ($line) => (int) $line->hotel_id !== (int) $reservation->hotel_id)) {
                throw new RuntimeException('A folio contains transactions from a different hotel.');
            }
            if ($lines->contains(fn ($line) => $line->isOpening())) {
                throw new RuntimeException('Opening receivables cannot be moved to the group master bill; keep this room on its own bill.');
            }
            if ($lines->isNotEmpty()) {
                // Acquire a consistent view of the two folios before moving the lines.
                $master = Folio::whereKey($master->id)->lockForUpdate()->firstOrFail();
                $when = now();
                foreach ($lines as $line) {
                    DB::table('reservation_bill_transfers')->insert([
                        'hotel_id' => $line->hotel_id,
                        'reservation_id' => $reservation->id,
                        'folio_transaction_id' => $line->id,
                        'source_folio_id' => $folio->id,
                        'target_folio_id' => $master->id,
                        'actor_user_id' => $actorId,
                        'created_at' => $when,
                    ]);
                }
                FolioTransaction::withoutGlobalScope('hotel')->whereIn('id', $lines->modelKeys())->update(['folio_id' => $master->id]);
            }
            $folio->forceFill(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $actorId,
                'consolidated_into_folio_id' => $master->id])->save();

            return $master->fresh();
        });
    }

    /** Fold every open stay folio of a departed group stay into the master bill. Returns the master folio. */
    public static function foldAll(Reservation $reservation, ?int $actorId): Folio
    {
        $reservation = Reservation::whereKey($reservation->id)->with('rooms')->firstOrFail();
        $master = null;
        foreach ($reservation->rooms as $stay) {
            if (! in_array($stay->status, ['checked_out', 'cancelled', 'no_show'], true)) {
                continue; // in-house and future rooms stay on their own folio until they depart
            }
            $master = self::foldStay($reservation, $stay, $actorId);
        }

        return $master ?? self::masterFor($reservation, true);
    }

    /** The reservation-level master folio, created on demand. */
    public static function masterFor(Reservation $reservation, bool $create = false): Folio
    {
        $master = Folio::where('reservation_id', $reservation->id)
            ->whereNull('reservation_room_id')
            ->where('type', 'master')
            ->where('status', 'open')
            ->whereNull('consolidated_into_folio_id')
            ->lockForUpdate()
            ->first();
        if ($master || ! $create) {
            return $master ?? throw new RuntimeException('No open group master folio for this reservation.');
        }

        return Folio::create([
            'folio_no' => \App\Support\Numbering::next('folio'),
            'reservation_id' => $reservation->id,
            'guest_id' => $reservation->guest_id,
            'company_id' => $reservation->billing_instruction === 'company' ? $reservation->company_id : null,
            'type' => 'master',
            'status' => 'open',
        ]);
    }
}
