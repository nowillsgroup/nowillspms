<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Folio extends Model
{
    use BelongsToHotel;

    protected $table = 'folios';

    protected $guarded = ['id', 'hotel_id'];

    public function activeTransactions()
    {
        return $this->transactions()->where('is_void', false);
    }

    public function totals(?Collection $transactions = null): array
    {
        $tx = ($transactions ?? $this->transactions()->get())->where('is_void', false);
        // Decimal-exact summation via Money (integer minor units) — no float drift.
        $chargesM = Money::zero();
        $taxM = Money::zero();
        foreach ($tx->where('type', 'charge') as $t) {
            $chargesM = $chargesM->add(Money::fromDecimal((string) $t->amount))->add(Money::fromDecimal((string) $t->tax_amount));
            $taxM = $taxM->add(Money::fromDecimal((string) $t->tax_amount));
        }
        $discountM = Money::sum($tx->whereIn('type', ['discount', 'allowance'])->map(fn ($t) => (string) $t->amount)->all());
        $paymentsM = Money::sum($tx->where('type', 'payment')->map(fn ($t) => (string) $t->amount)->all());
        $refundsM = Money::sum($tx->where('type', 'refund')->map(fn ($t) => (string) $t->amount)->all());
        $openingM = Money::sum($tx->where('type', 'opening_balance')->map(fn ($t) => (string) $t->amount)->all());
        $balanceM = $chargesM->add($openingM)->sub($discountM)->sub($paymentsM)->add($refundsM);

        return [
            'charges' => $chargesM->toFloat(), 'opening' => $openingM->toFloat(), 'tax' => $taxM->toFloat(),
            'discount' => $discountM->toFloat(), 'payments' => $paymentsM->toFloat(), 'refunds' => $refundsM->toFloat(),
            'balance' => $balanceM->toFloat(),
        ];
    }

    /** Decimal-exact balance as Money (prefer over balance() for comparisons). */
    public function balanceMoney(?Collection $transactions = null): Money
    {
        return Money::fromFloat($this->totals($transactions)['balance']);
    }

    public function balance(): float
    {
        return $this->totals()['balance'];
    }

    /** Add the active ledger balance to a folio list without hydrating each line per row. */
    public function scopeWithListBalance(Builder $query): Builder
    {
        $balances = FolioTransaction::query()
            ->where('is_void', false)
            ->select('folio_id')
            ->selectRaw("ROUND(SUM(CASE WHEN type = 'charge' THEN amount + tax_amount WHEN type = 'opening_balance' THEN amount WHEN type IN ('discount', 'allowance', 'payment') THEN -amount WHEN type = 'refund' THEN amount ELSE 0 END), 2) as listing_balance")
            ->groupBy('folio_id');

        return $query->leftJoinSub($balances, 'folio_balance_totals', fn ($join) => $join->on('folios.id', '=', 'folio_balance_totals.folio_id'))
            ->select('folios.*')
            ->selectRaw('COALESCE(folio_balance_totals.listing_balance, 0) as listing_balance');
    }

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'invoice_date' => 'date', 'irn_ack_at' => 'datetime', 'irn_next_try_at' => 'datetime', 'irn_retryable' => 'boolean',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function stay()
    {
        return $this->belongsTo(ReservationRoom::class, 'reservation_room_id');
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function consolidatedInto()
    {
        return $this->belongsTo(Folio::class, 'consolidated_into_folio_id');
    }

    public function transactions()
    {
        return $this->hasMany(FolioTransaction::class);
    }

    // ---- v3.0 tax documents (v4.3: GRA VAT / E-VAT)

    public const NOTE_REASONS = ['rate' => 'Rate / tariff correction', 'deficiency' => 'Service deficiency / complaint', 'billing_error' => 'Billing error (wrong charge)',
        'discount' => 'Post-sale discount', 'cancellation' => 'Cancellation / no-show fee waived', 'other' => 'Other'];

    public function isCreditNote(): bool
    {
        return $this->type === 'credit_note';
    }

    public function isInvoiced(): bool
    {
        return $this->invoice_no !== null && $this->status === 'closed';
    }

    public function hasActiveIrn(): bool
    {
        return $this->irn_status === 'generated' && $this->irn;
    }

    public function original()
    {
        return $this->belongsTo(Folio::class, 'original_folio_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(Folio::class, 'original_folio_id')->where('type', 'credit_note');
    }

    public function einvoiceLogs()
    {
        return $this->hasMany(EinvoiceLog::class)->orderByDesc('id');
    }

    /** Taxable value, tax and document value of the charges on this document (negative for credit notes). */
    public function gstTotals(): array
    {
        $c = $this->activeTransactions()->where('type', 'charge')->get();
        $taxableM = Money::sum($c->map(fn ($t) => (string) $t->amount)->all());
        $taxM = Money::sum($c->map(fn ($t) => (string) $t->tax_amount)->all());
        $discM = Money::sum($this->activeTransactions()->whereIn('type', ['discount', 'allowance'])->get()->map(fn ($t) => (string) $t->amount)->all());
        $valueM = $taxableM->add($taxM)->sub($discM);

        return ['taxable' => $taxableM->toFloat(), 'tax' => $taxM->toFloat(), 'discount' => $discM->toFloat(), 'value' => $valueM->toFloat()];
    }

    public function irnBadge(): string
    {
        return match ($this->irn_status) {
            'generated' => '<span class="badge badge-green" title="Stamped by GRA E-VAT">E-VAT stamped</span>',
            'failed' => '<span class="badge badge-red">E-VAT failed</span>',
            'pending' => '<span class="badge badge-amber">E-VAT pending</span>',
            'cancelled' => '<span class="badge badge-slate">stamp cancelled</span>',
            default => '',
        };
    }
}
