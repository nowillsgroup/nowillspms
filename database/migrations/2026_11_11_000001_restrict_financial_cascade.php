<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Financial tables must not cascade-delete when a hotel is deleted.
 * Hotels are archived, never deleted — but if a hotel row is ever removed,
 * the ledger history must survive (restrict), not vanish silently.
 *
 * This migration drops the cascade FKs on hotel_id for financial tables and
 * re-creates them as restrictOnDelete. Fresh installs get the same effect
 * from the original migrations (edited to restrict); this covers existing DBs.
 */
return new class extends Migration
{
    /** @var string[] financial tables whose hotel_id FK must restrict */
    private array $tables = [
        'folios', 'folio_transactions',
        'gl_journals', 'gl_journal_lines',
        'supplier_invoices', 'supplier_invoice_lines', 'supplier_payments',
        'online_payments', 'online_payment_events', 'gateway_payouts',
        'vouchers', 'voucher_lines',
        'close_periods', 'close_period_lines',
        'treasury_accounts', 'treasury_transfers', 'cheque_books', 'cheques_received',
        'owner_fees', 'owner_statements',
        'billing_reconciliations', 'billing_reconciliation_items',
        'gift_vouchers', 'gift_voucher_redemptions',
        'petty_floats', 'petty_vouchers', 'expense_claims',
        'einvoice_logs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'hotel_id')) {
                continue;
            }
            // Drop existing FK (name is conventional: {table}_hotel_id_foreign).
            $fk = $table.'_hotel_id_foreign';
            try {
                Schema::table($table, function (Blueprint $t) use ($fk) {
                    $t->dropForeign($fk);
                });
            } catch (\Throwable) {
                // FK may not exist (SQLite / already restricted) — try generic drop.
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('hotel_id')->references('id')->on('hotels')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Reverting to cascade would re-introduce the audit-trail risk; keep restrict.
    }
};
