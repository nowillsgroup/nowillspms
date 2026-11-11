<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

    // ---- POS
    Route::prefix('pos')->name('pos.')->controller(Controllers\PosController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->middleware('perm:pos.view')->name('dashboard');
        Route::get('/', 'index')->middleware('perm:pos.view')->middleware('waiter.shift')->name('index');
        Route::get('/live-orders', 'liveOrders')->middleware('perm:pos.view')->name('live-orders');
        Route::get('/live-orders/feed', 'liveOrdersFeed')->middleware('perm:pos.view')->name('live-feed');
        Route::get('/guests', 'guestSearch')->middleware('perm:pos.order')->middleware('waiter.shift')->name('guests');
        Route::get('/unsettled-invoices', 'unsettledInvoices')->middleware('perm:pos.settle')->name('unsettled');
        Route::get('/orders', 'orders')->middleware('perm:pos.view')->name('orders');
        Route::post('/orders', 'open')->middleware('perm:pos.order')->middleware('waiter.shift')->name('open');
        Route::get('/orders/{order}', 'terminal')->middleware('perm:pos.view')->middleware('waiter.shift')->name('terminal');
        Route::post('/orders/{order}/claim', 'claim')->middleware('perm:pos.order')->middleware('waiter.shift')->name('claim');
        Route::post('/orders/{order}/kot', 'kot')->middleware('perm:pos.order')->middleware('waiter.shift')->name('kot');
        Route::post('/orders/{order}/fire', 'fireHeld')->middleware('perm:pos.order')->middleware('waiter.shift')->name('fire');
        Route::post('/orders/{order}/items/{line}/cancel', 'cancelItem')->middleware('perm:pos.void')->middleware('waiter.shift')->name('item.cancel');
        Route::post('/orders/{order}/items/{line}/comp', 'compItem')->middleware('perm:pos.settle')->middleware('waiter.shift')->name('item.comp');
        Route::post('/orders/{order}/allergies', 'allergies')->middleware('perm:pos.order')->middleware('waiter.shift')->name('allergies'); // v5.0
        Route::post('/orders/{order}/details', 'updateDetails')->middleware('perm:pos.order')->middleware('waiter.shift')->name('details');
        Route::post('/orders/{order}/move-table', 'moveTable')->middleware('perm:pos.order')->middleware('waiter.shift')->name('move');
        Route::post('/orders/{order}/merge', 'merge')->middleware('perm:pos.settle')->middleware('waiter.shift')->name('merge');
        Route::post('/orders/{order}/split', 'split')->middleware('perm:pos.settle')->middleware('waiter.shift')->name('split');
        Route::post('/orders/{order}/items/{line}/served', 'served')->middleware('perm:pos.order')->middleware('waiter.shift')->name('item.served');
        Route::post('/orders/{order}/bill', 'bill')->middleware('perm:pos.settle')->middleware('waiter.shift')->name('bill');
        Route::post('/orders/{order}/settle', 'settle')->middleware('perm:pos.settle')->middleware('waiter.shift')->name('settle');
        Route::post('/orders/{order}/delivery-status', 'deliveryStatus')->middleware('perm:pos.order')->middleware('waiter.shift')->name('delivery.status');
        Route::post('/orders/{order}/refund', 'refund')->middleware('perm:pos.refund')->middleware('waiter.shift')->name('refund');
        Route::post('/orders/{order}/void', 'void')->middleware('perm:pos.void')->middleware('waiter.shift')->name('void');
        Route::get('/orders/{order}/print', 'printBill')->middleware('perm:pos.view')->name('print');
        Route::get('/orders/{order}/kot/{kot}', 'printKot')->middleware('perm:pos.view,pos.kitchen')->middleware('waiter.shift')->name('printkot');
        Route::post('/orders/{order}/kot/{kot}/print', 'reprintKot')->middleware('perm:pos.order,pos.kitchen')->middleware('waiter.shift')->name('printkot.send');
        Route::get('/printers', [Controllers\KotPrinterController::class, 'index'])->middleware('perm:pos.printers')->name('printers');
        Route::post('/printers', [Controllers\KotPrinterController::class, 'store'])->middleware('perm:pos.printers')->name('printers.store');
        Route::put('/printers/{printer}', [Controllers\KotPrinterController::class, 'update'])->middleware('perm:pos.printers')->name('printers.update');
        Route::delete('/printers/{printer}', [Controllers\KotPrinterController::class, 'destroy'])->middleware('perm:pos.printers')->name('printers.destroy');
        Route::post('/printers/routing', [Controllers\KotPrinterController::class, 'routing'])->middleware('perm:pos.printers')->name('printers.routing');
        Route::get('/printers/{printer}/test', [Controllers\KotPrinterController::class, 'test'])->middleware('perm:pos.printers')->name('printers.test');
        Route::post('/printers/{printer}/test', [Controllers\KotPrinterController::class, 'sendTest'])->middleware('perm:pos.printers')->name('printers.test.send');
        Route::post('/printers/jobs/{job}/retry', [Controllers\KotPrinterController::class, 'retry'])->middleware('perm:pos.printers,pos.kitchen')->name('printers.retry');
        Route::post('/orders/{order}/accept', 'acceptGuest')->middleware('perm:pos.order')->middleware('waiter.shift')->name('accept');
        Route::post('/orders/{order}/reject', 'rejectGuest')->middleware('perm:pos.void')->middleware('waiter.shift')->name('reject');
        Route::get('/qr-codes', 'qrSheet')->middleware('perm:pos.view')->name('qr');
        Route::post('/qr-codes/{table}/regenerate', 'qrRegenerate')->middleware('perm:masters.manage')->name('qr.regenerate');
        Route::get('/modifiers', [Controllers\PosModifierController::class, 'index'])->middleware('perm:masters.manage')->name('modifiers');
        Route::post('/modifiers/groups', [Controllers\PosModifierController::class, 'storeGroup'])->middleware('perm:masters.manage')->name('modifiers.groups.store');
        Route::put('/modifiers/groups/{group}', [Controllers\PosModifierController::class, 'updateGroup'])->middleware('perm:masters.manage')->name('modifiers.groups.update');
        Route::delete('/modifiers/groups/{group}', [Controllers\PosModifierController::class, 'destroyGroup'])->middleware('perm:masters.manage')->name('modifiers.groups.destroy');
        Route::post('/modifiers', [Controllers\PosModifierController::class, 'storeModifier'])->middleware('perm:masters.manage')->name('modifiers.store');
        Route::put('/modifiers/{modifier}', [Controllers\PosModifierController::class, 'updateModifier'])->middleware('perm:masters.manage')->name('modifiers.update');
        Route::delete('/modifiers/{modifier}', [Controllers\PosModifierController::class, 'destroyModifier'])->middleware('perm:masters.manage')->name('modifiers.destroy');
        // {item} is globally bound to LostFoundItem; use a unique key for MenuItem model binding here.
        Route::post('/modifiers/items/{menuItem}/groups', [Controllers\PosModifierController::class, 'assign'])->middleware('perm:masters.manage')->name('modifiers.assign');
    });

    // ---- Banquet
    // v2.6 guest marketing & CRM
    Route::prefix('crm')->name('crm.')->group(function () {
        Route::get('/', [Controllers\CrmController::class, 'index'])->middleware('perm:crm.view')->name('index');
        Route::get('/settings', [Controllers\CrmController::class, 'settings'])->middleware('perm:crm.send')->name('settings');
        Route::post('/settings', [Controllers\CrmController::class, 'saveSettings'])->middleware('perm:crm.send')->name('settings.save');
        Route::post('/refresh-stats', [Controllers\CrmController::class, 'refreshStats'])->middleware('perm:crm.manage')->name('refresh');
        Route::post('/segments/preview', [Controllers\SegmentController::class, 'preview'])->middleware('perm:crm.view')->name('segments.preview');
        Route::get('/segments/{segment}/export', [Controllers\SegmentController::class, 'export'])->middleware('perm:crm.manage')->name('segments.export');
        Route::resource('segments', Controllers\SegmentController::class)->middleware('perm:crm.view');
        Route::prefix('campaigns')->name('campaigns.')->controller(Controllers\CampaignController::class)->group(function () {
            Route::get('/', 'index')->middleware('perm:crm.view')->name('index');
            Route::get('/create', 'create')->middleware('perm:crm.manage')->name('create');
            Route::post('/', 'store')->middleware('perm:crm.manage')->name('store');
            Route::get('/{campaign}', 'show')->middleware('perm:crm.view')->name('show');
            Route::get('/{campaign}/edit', 'edit')->middleware('perm:crm.manage')->name('edit');
            Route::put('/{campaign}', 'update')->middleware('perm:crm.manage')->name('update');
            Route::post('/{campaign}/test', 'test')->middleware('perm:crm.manage', 'throttle:10,1')->name('test');
            Route::post('/{campaign}/duplicate', 'duplicate')->middleware('perm:crm.manage')->name('duplicate');
            Route::post('/{campaign}/send', 'send')->middleware('perm:crm.send')->name('send');
            Route::post('/{campaign}/schedule', 'schedule')->middleware('perm:crm.send')->name('schedule');
            Route::post('/{campaign}/unschedule', 'unschedule')->middleware('perm:crm.send')->name('unschedule');
            Route::post('/{campaign}/cancel', 'cancel')->middleware('perm:crm.manage')->name('cancel');
        });
        Route::prefix('automations')->name('journeys.')->controller(Controllers\JourneyController::class)->group(function () {
            Route::get('/', 'index')->middleware('perm:crm.view')->name('index');
            Route::get('/create', 'create')->middleware('perm:crm.manage')->name('create');
            Route::post('/', 'store')->middleware('perm:crm.manage')->name('store');
            Route::get('/{journey}', 'show')->middleware('perm:crm.view')->name('show');
            Route::get('/{journey}/edit', 'edit')->middleware('perm:crm.manage')->name('edit');
            Route::put('/{journey}', 'update')->middleware('perm:crm.manage')->name('update');
            Route::post('/{journey}/toggle', 'toggle')->middleware('perm:crm.send')->name('toggle');
            Route::post('/{journey}/run', 'run')->middleware('perm:crm.send')->name('run');
            Route::post('/{journey}/test', 'test')->middleware('perm:crm.manage', 'throttle:10,1')->name('test');
        });
        Route::prefix('promo-codes')->name('promos.')->controller(Controllers\PromoCodeController::class)->group(function () {
            Route::get('/', 'index')->middleware('perm:crm.view')->name('index');
            Route::get('/create', 'create')->middleware('perm:crm.manage')->name('create');
            Route::post('/', 'store')->middleware('perm:crm.manage')->name('store');
            Route::get('/{promo}/edit', 'edit')->middleware('perm:crm.manage')->name('edit');
            Route::put('/{promo}', 'update')->middleware('perm:crm.manage')->name('update');
            Route::post('/{promo}/toggle', 'toggle')->middleware('perm:crm.manage')->name('toggle');
        });
    });
    // v2.7: corporate & travel-agent rate contracts (allotments, blackouts, production) + credit control
    Route::prefix('contracts')->name('contracts.')->controller(Controllers\ContractController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:contracts.view')->name('index');
        Route::get('/create', 'create')->middleware('perm:contracts.manage')->name('create');
        Route::post('/', 'store')->middleware('perm:contracts.manage')->name('store');
        Route::get('/{contract}', 'show')->middleware('perm:contracts.view')->name('show');
        Route::get('/{contract}/edit', 'edit')->middleware('perm:contracts.manage')->name('edit');
        Route::put('/{contract}', 'update')->middleware('perm:contracts.manage')->name('update');
        Route::delete('/{contract}', 'destroy')->middleware('perm:contracts.manage')->name('destroy');
        Route::get('/{contract}/letter', 'letter')->middleware('perm:contracts.view')->name('letter');
        Route::post('/{contract}/activate', 'activate')->middleware('perm:contracts.approve')->name('activate');
        Route::post('/{contract}/suspend', 'suspend')->middleware('perm:contracts.approve')->name('suspend');
        Route::post('/{contract}/renew', 'renew')->middleware('perm:contracts.manage')->name('renew');
    });
    Route::prefix('credit')->name('credit.')->controller(Controllers\CreditController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:credit.view')->name('index');
        Route::post('/review', 'review')->middleware('perm:credit.manage')->name('review');
        Route::get('/{company}', 'show')->middleware('perm:credit.view')->name('show');
        Route::post('/{company}/hold', 'hold')->middleware('perm:credit.manage')->name('hold');
        Route::post('/{company}/release', 'release')->middleware('perm:credit.manage')->name('release');
        Route::post('/{company}/terms', 'terms')->middleware('perm:credit.manage')->name('terms');
        Route::post('/{company}/statement', 'statement')->middleware('perm:credit.manage', 'throttle:10,1')->name('statement');
    });
    Route::post('/guests/{guest}/consent', [Controllers\GuestController::class, 'consent'])->middleware('perm:guests.manage,crm.manage')->name('guests.consent');

    Route::prefix('banquet')->name('banquet.')->controller(Controllers\BanquetController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:banquet.view')->name('index');
        Route::get('/calendar', 'calendar')->middleware('perm:banquet.view')->name('calendar');
        Route::get('/diary', 'diary')->middleware('perm:banquet.view')->name('diary');
        Route::get('/function-sheet', 'sheet')->middleware('perm:banquet.view')->name('sheet');
        Route::get('/settings', 'settings')->middleware('perm:banquet.setup')->name('settings');
        Route::post('/settings', 'saveSettings')->middleware('perm:banquet.setup')->name('settings.save');
        Route::get('/availability', 'availability')->middleware('perm:banquet.view')->name('availability');
        Route::get('/create', 'create')->middleware('perm:banquet.manage')->name('create');
        Route::post('/', 'store')->middleware('perm:banquet.manage')->name('store');
        Route::get('/{event}', 'show')->middleware('perm:banquet.view')->name('show');
        Route::get('/{event}/edit', 'edit')->middleware('perm:banquet.manage')->name('edit');
        Route::put('/{event}', 'update')->middleware('perm:banquet.manage')->name('update');
        Route::post('/{event}/tentative', 'tentative')->middleware('perm:banquet.manage')->name('tentative');
        Route::post('/{event}/confirm', 'confirm')->middleware('perm:banquet.manage')->name('confirm');
        Route::post('/{event}/lost', 'lost')->middleware('perm:banquet.manage')->name('lost');
        Route::post('/{event}/reopen', 'reopen')->middleware('perm:banquet.manage')->name('reopen');
        Route::post('/{event}/cancel', 'cancel')->middleware('perm:banquet.manage')->name('cancel');
        Route::post('/{event}/actuals', 'actuals')->middleware('perm:banquet.manage')->name('actuals');
        Route::post('/{event}/deposit', 'deposit')->middleware('perm:banquet.billing')->name('deposit');
        Route::post('/{event}/refund', 'refund')->middleware('perm:banquet.billing')->name('refund');
        Route::post('/{event}/bill', 'bill')->middleware('perm:banquet.billing')->name('bill');
        Route::get('/{event}/print', 'print')->middleware('perm:banquet.view')->name('print');
        Route::get('/{event}/menu', 'menu')->middleware('perm:banquet.view')->name('menu');
        Route::get('/{event}/quote', 'quote')->middleware('perm:banquet.view')->name('quote');
    });

    // ---- Inventory & purchasing
    Route::prefix('inventory')->group(function () {
        Route::get('/', [Controllers\InventoryController::class, 'index'])->middleware('perm:inventory.view')->name('inventory.index');
        Route::get('/ledger', [Controllers\InventoryController::class, 'ledger'])->middleware('perm:inventory.view')->name('inventory.ledger');
        Route::get('/movement', [Controllers\InventoryController::class, 'movement'])->middleware('perm:inventory.manage')->name('inventory.movement');
        Route::post('/movement', [Controllers\InventoryController::class, 'storeMovement'])->middleware('perm:inventory.manage')->name('inventory.movement.store');
        Route::controller(Controllers\StockTakeController::class)->group(function () {
            Route::get('/stock-takes', 'index')->middleware('perm:inventory.view')->name('stocktakes.index');
            Route::post('/stock-takes', 'store')->middleware('perm:inventory.manage')->name('stocktakes.store');
            Route::get('/stock-takes/{stocktake}', 'show')->middleware('perm:inventory.view')->name('stocktakes.show');
            Route::put('/stock-takes/{stocktake}', 'update')->middleware('perm:inventory.manage')->name('stocktakes.update');
            Route::post('/stock-takes/{stocktake}/cancel', 'cancel')->middleware('perm:inventory.manage')->name('stocktakes.cancel');
            Route::get('/stock-takes/{stocktake}/print', 'print')->middleware('perm:inventory.view')->name('stocktakes.print');
            Route::get('/reorder', 'reorder')->middleware('perm:inventory.view')->name('inventory.reorder');
            Route::post('/reorder', 'reorderStore')->middleware('perm:inventory.manage')->name('inventory.reorder.store');
        });
        Route::get('/purchases', [Controllers\PurchaseController::class, 'index'])->middleware('perm:inventory.view')->name('purchases.index');
        Route::get('/purchases/create', [Controllers\PurchaseController::class, 'create'])->middleware('perm:inventory.manage')->name('purchases.create');
        Route::get('/purchases/fx-rate', [Controllers\PurchaseController::class, 'fxRate'])->middleware('perm:inventory.manage')->name('purchases.fx-rate');
        Route::post('/purchases', [Controllers\PurchaseController::class, 'store'])->middleware('perm:inventory.manage')->name('purchases.store');
        Route::get('/purchases/{po}', [Controllers\PurchaseController::class, 'show'])->middleware('perm:inventory.view')->name('purchases.show');
        Route::post('/purchases/{po}/status', [Controllers\PurchaseController::class, 'status'])->middleware('perm:inventory.manage,purchasing.approve,purchasing.approve_high')->name('purchases.status');
        // v4.8 Procurement II – partial goods receipts, short-close, GRN print
        Route::post('/purchases/{po}/receive', [Controllers\PurchaseController::class, 'receive'])->middleware('perm:inventory.manage')->name('purchases.receive');
        Route::post('/purchases/{po}/close', [Controllers\PurchaseController::class, 'close'])->middleware('perm:inventory.manage')->name('purchases.close');
        Route::get('/goods-receipts/{grn}/print', [Controllers\PurchaseController::class, 'grn'])->middleware('perm:inventory.view')->name('grn.print');
        Route::get('/goods-receipts', [Controllers\ProcurementController::class, 'grns'])->middleware('perm:inventory.view')->name('grn.index');
        Route::get('/supplier-scorecard', [Controllers\ProcurementController::class, 'scorecard'])->middleware('perm:inventory.view')->name('proc.scorecard');
        Route::post('/purchasing/settings', [Controllers\ProcurementController::class, 'settings'])->middleware('perm:settings.manage')->name('proc.settings');
        Route::get('/supplier-prices', [Controllers\ProcurementController::class, 'prices'])->middleware('perm:inventory.view')->name('prices.index');
        Route::post('/supplier-prices', [Controllers\ProcurementController::class, 'savePrice'])->middleware('perm:purchasing.prices')->name('prices.store');
        Route::post('/supplier-prices/{sprice}', [Controllers\ProcurementController::class, 'savePrice'])->middleware('perm:purchasing.prices')->name('prices.update');
        Route::post('/supplier-prices/{sprice}/end', [Controllers\ProcurementController::class, 'expirePrice'])->middleware('perm:purchasing.prices')->name('prices.end');
        Route::prefix('rfqs')->name('rfq.')->group(function () {
            Route::get('/', [Controllers\ProcurementController::class, 'rfqs'])->middleware('perm:inventory.view')->name('index');
            Route::get('/create', [Controllers\ProcurementController::class, 'create'])->middleware('perm:purchasing.rfq')->name('create');
            Route::post('/', [Controllers\ProcurementController::class, 'store'])->middleware('perm:purchasing.rfq')->name('store');
            Route::get('/{rfq}', [Controllers\ProcurementController::class, 'show'])->middleware('perm:inventory.view')->name('show');
            Route::get('/{rfq}/edit', [Controllers\ProcurementController::class, 'edit'])->middleware('perm:purchasing.rfq')->name('edit');
            Route::post('/{rfq}/update', [Controllers\ProcurementController::class, 'update'])->middleware('perm:purchasing.rfq')->name('update');
            Route::post('/{rfq}/send', [Controllers\ProcurementController::class, 'send'])->middleware('perm:purchasing.rfq')->name('send');
            Route::post('/{rfq}/suppliers', [Controllers\ProcurementController::class, 'addSupplier'])->middleware('perm:purchasing.rfq')->name('supplier');
            Route::post('/{rfq}/invites/{invite}/resend', [Controllers\ProcurementController::class, 'resend'])->middleware('perm:purchasing.rfq')->name('resend');
            Route::get('/{rfq}/invites/{invite}/quote', [Controllers\ProcurementController::class, 'quoteForm'])->middleware('perm:purchasing.rfq')->name('quote');
            Route::post('/{rfq}/invites/{invite}/quote', [Controllers\ProcurementController::class, 'saveQuote'])->middleware('perm:purchasing.rfq')->name('quote.save');
            Route::post('/{rfq}/close', [Controllers\ProcurementController::class, 'close'])->middleware('perm:purchasing.rfq')->name('close');
            Route::post('/{rfq}/cancel', [Controllers\ProcurementController::class, 'cancel'])->middleware('perm:purchasing.rfq')->name('cancel');
            Route::post('/{rfq}/award', [Controllers\ProcurementController::class, 'award'])->middleware('perm:purchasing.rfq')->name('award');
            Route::get('/{rfq}/print', [Controllers\ProcurementController::class, 'printRfq'])->middleware('perm:inventory.view')->name('print');
            Route::get('/{rfq}/comparative', [Controllers\ProcurementController::class, 'printComparative'])->middleware('perm:inventory.view')->name('comparative');
        });
    });

    // ---- Accounts
    Route::prefix('accounts')->name('accounts.')->controller(Controllers\AccountsController::class)->middleware('perm:accounts.view')->group(function () {
        Route::get('/vouchers', 'vouchers')->name('vouchers');
        Route::post('/vouchers', 'storeVoucher')->middleware('perm:accounts.manage')->name('vouchers.store');
        Route::delete('/vouchers/{voucher}', 'deleteVoucher')->middleware('perm:accounts.manage')->name('vouchers.delete');
        Route::get('/city-ledger', 'cityLedger')->name('cityledger');
        Route::get('/receivables', 'receivables')->name('receivables'); // v8.9 company receivables aged, with the documents
        Route::get('/city-ledger/{company}', 'companyLedger')->name('company');
        Route::post('/city-ledger/{company}/receipt', 'companyReceipt')->middleware('perm:accounts.manage')->name('company.receipt');
        Route::get('/cash-book', 'cashBook')->name('cashbook');
        Route::get('/profit-loss', 'pnl')->name('pnl');
        Route::get('/commissions', [Controllers\CommissionController::class, 'index'])->name('commissions');
        Route::post('/commissions/pay', [Controllers\CommissionController::class, 'pay'])->middleware('perm:accounts.manage')->name('commissions.pay');
        Route::post('/commissions/{commission}/void', [Controllers\CommissionController::class, 'void'])->middleware('perm:accounts.manage')->name('commissions.void');
    });

    // ---- HR
    Route::get('/hr/attendance', [Controllers\HrController::class, 'attendance'])->middleware('perm:hr.manage')->name('hr.attendance');
    Route::post('/hr/attendance', [Controllers\HrController::class, 'saveAttendance'])->middleware('perm:hr.manage')->name('hr.attendance.save');
    Route::get('/hr/settings', [Controllers\HrController::class, 'settings'])->middleware('perm:hr.manage')->name('hr.settings');
    Route::post('/hr/settings', [Controllers\HrController::class, 'saveSettings'])->middleware('perm:hr.manage')->name('hr.settings.save');
    Route::get('/hr/register', [Controllers\HrController::class, 'register'])->middleware('perm:hr.manage')->name('hr.register');

    // the employee file: photograph, papers, and the review of what staff send in
    Route::controller(Controllers\HrController::class)->prefix('hr/employees')->name('hr.employee.')->group(function () {
        Route::get('/{employee}', 'employee')->whereNumber('employee')->middleware('perm:hr.manage,hr.view,payroll.view')->name('file');
        Route::get('/{employee}/photo', 'employeePhoto')->whereNumber('employee')->middleware('perm:hr.manage,hr.view,payroll.view')->name('photo');
        Route::post('/{employee}/photo', 'savePhoto')->whereNumber('employee')->middleware('perm:hr.manage')->name('photo.save');
        Route::post('/{employee}/photo/remove', 'removePhoto')->whereNumber('employee')->middleware('perm:hr.manage')->name('photo.remove');
        Route::post('/{employee}/documents', 'uploadDocument')->whereNumber('employee')->middleware('perm:hr.manage')->name('documents.save');
    });
    Route::get('/hr/documents/{doc}', [Controllers\HrController::class, 'document'])->whereNumber('doc')
        ->middleware('perm:hr.manage,hr.view,payroll.view')->name('hr.document');
    Route::post('/hr/documents/{doc}/delete', [Controllers\HrController::class, 'deleteDocument'])->whereNumber('doc')
        ->middleware('perm:hr.manage')->name('hr.document.delete');
    // v7.43 public holidays – the gazetted Ghana calendar behind the roster, the register, leave and payroll
    Route::get('/hr/holidays', [Controllers\HrController::class, 'holidays'])->middleware('perm:hr.manage,roster.view,payroll.view')->name('hr.holidays');
    Route::post('/hr/holidays', [Controllers\HrController::class, 'saveHolidays'])->middleware('perm:hr.manage')->name('hr.holidays.save');
    Route::post('/hr/holidays/reset', [Controllers\HrController::class, 'resetHolidays'])->middleware('perm:hr.manage')->name('hr.holidays.reset');
    // v7.40 ESS review desk
    Route::controller(Controllers\EssController::class)->middleware('perm:hr.manage')->group(function () {
        Route::get('/hr/ess', 'desk')->name('hr.ess');
        Route::post('/hr/ess/requests/{hrReq}', 'decide')->whereNumber('hrReq')->name('hr.ess.decide'); // renamed: {req} was shadowed by Route::model('req', Requisition)
        Route::post('/hr/ess/documents/{doc}', 'reviewDoc')->whereNumber('doc')->name('hr.ess.doc');
        Route::post('/hr/ess/announce', 'announce')->name('hr.ess.announce');
    });

    // v2.0 leave & payroll
    Route::prefix('hr/leave')->name('leave.')->controller(Controllers\LeaveController::class)->middleware('perm:hr.leave')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:60,1')->name('store');
        Route::post('/{lreq}/approve', 'approve')->whereNumber('lreq')->name('approve');
        Route::post('/{lreq}/reject', 'reject')->whereNumber('lreq')->name('reject');
        Route::post('/{lreq}/cancel', 'cancel')->whereNumber('lreq')->name('cancel');
    });
    Route::prefix('payroll')->name('payroll.')->controller(Controllers\PayrollController::class)->middleware('perm:payroll.view')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware(['perm:payroll.manage', 'throttle:20,1'])->name('store');
        Route::get('/runs/{prun}', 'show')->whereNumber('prun')->name('show');
        Route::post('/runs/{prun}/recalculate', 'recalc')->whereNumber('prun')->middleware('perm:payroll.manage')->name('recalc');
        Route::post('/runs/{prun}/approve', 'approve')->whereNumber('prun')->middleware('perm:payroll.approve')->name('approve');
        Route::post('/runs/{prun}/reopen', 'reopen')->whereNumber('prun')->middleware('perm:payroll.approve')->name('reopen');
        Route::post('/runs/{prun}/pay', 'pay')->whereNumber('prun')->middleware('perm:payroll.approve')->name('pay');
        Route::delete('/runs/{prun}', 'destroy')->whereNumber('prun')->middleware('perm:payroll.manage')->name('destroy');
        Route::get('/runs/{prun}/bank-advice', 'bankAdvice')->whereNumber('prun')->name('bankadvice');
        Route::get('/runs/{prun}/payslips', 'printAll')->whereNumber('prun')->name('print');
        Route::get('/payslips/{slip}', 'payslip')->whereNumber('slip')->name('payslip');
        Route::post('/payslips/{slip}/lines', 'addLine')->whereNumber('slip')->middleware('perm:payroll.manage')->name('lines.add');
        Route::delete('/lines/{pline}', 'removeLine')->whereNumber('pline')->middleware('perm:payroll.manage')->name('lines.remove');
        Route::get('/components', 'components')->middleware('perm:payroll.manage')->name('components');
        Route::post('/components', 'saveComponent')->middleware('perm:payroll.manage')->name('components.store');
        Route::post('/components/{pcomp}', 'saveComponent')->whereNumber('pcomp')->middleware('perm:payroll.manage')->name('components.update');
        Route::post('/settings', 'saveSettings')->middleware('perm:payroll.manage')->name('settings');
        Route::get('/employees/{employee}', 'employee')->whereNumber('employee')->middleware('perm:payroll.manage')->name('employee');
        Route::post('/employees/{employee}', 'saveEmployee')->whereNumber('employee')->middleware('perm:payroll.manage')->name('employee.save');
        Route::get('/advances', 'advances')->name('advances');
        Route::post('/advances', 'storeAdvance')->middleware(['perm:payroll.manage', 'throttle:30,1'])->name('advances.store');
        Route::post('/advances/{sadv}/cancel', 'cancelAdvance')->whereNumber('sadv')->middleware('perm:payroll.manage')->name('advances.cancel');
        // v4.5 Ghana statutory returns: SSNIT Tier 1, Tier 2, PAYE
        Route::get('/statutory', [Controllers\StatutoryController::class, 'index'])->name('statutory');
        Route::get('/statutory/{kind}/export', [Controllers\StatutoryController::class, 'export'])->where('kind', 'ssnit|tier2|paye')->name('statutory.export');
        Route::post('/statutory/remit', [Controllers\StatutoryController::class, 'remit'])->middleware(['perm:payroll.approve', 'throttle:30,1'])->name('statutory.remit');
        Route::post('/statutory/remittances/{srem}/cancel', [Controllers\StatutoryController::class, 'cancel'])->whereNumber('srem')->middleware('perm:payroll.approve')->name('statutory.cancel');
        Route::post('/statutory/settings', [Controllers\StatutoryController::class, 'settings'])->middleware('perm:payroll.manage')->name('statutory.settings');
    });
    Route::prefix('me')->name('me.')->controller(Controllers\SelfServiceController::class)->middleware('perm:hr.self')->group(function () {
        Route::get('/hr', 'index')->name('hr');
        Route::post('/leave', 'apply')->middleware('throttle:20,1')->name('leave');
        Route::post('/leave/{lreq}/cancel', 'cancel')->whereNumber('lreq')->name('leave.cancel');
        Route::get('/payslips/{slip}', 'payslip')->whereNumber('slip')->name('payslip');
        Route::get('/roster', 'roster')->name('roster'); // v4.9
        Route::post('/clock', 'clock')->middleware('throttle:10,1')->name('clock');
        Route::post('/swaps', 'requestSwap')->middleware('throttle:20,1')->name('swap');
        Route::post('/swaps/{swap}/respond', 'respondSwap')->whereNumber('swap')->name('swap.respond');
        Route::post('/swaps/{swap}/cancel', 'cancelSwap')->whereNumber('swap')->name('swap.cancel');
        Route::post('/pin', 'pin')->middleware('throttle:5,1')->name('pin');
    });
    // v7.40 employee self-service: profile change requests, documents, announcements
    Route::prefix('me')->name('me.')->controller(Controllers\EssController::class)->middleware('perm:hr.self')->group(function () {
        Route::get('/documents/{doc}', 'documentFile')->whereNumber('doc')->name('documents.file');
        Route::get('/profile', 'profile')->name('profile');
        Route::post('/profile', 'profileUpdate')->middleware('throttle:20,1')->name('profile.update');
        Route::get('/documents', 'documents')->name('documents');
        Route::post('/documents', 'documentUpload')->middleware('throttle:20,1')->name('documents.upload');
        Route::get('/announcements', 'announcements')->name('announcements');
    });

    // v4.9 duty roster & time clock
    Route::prefix('hr/roster')->name('roster.')->controller(Controllers\WorkforceController::class)->middleware('perm:roster.view')->group(function () {
        Route::get('/', 'roster')->name('index');
        Route::get('/print', 'printRoster')->name('print');
        Route::middleware('perm:roster.manage')->group(function () {
            Route::post('/', 'saveRoster')->name('save');
            Route::post('/copy', 'copyRoster')->name('copy');
            Route::post('/publish', 'publishRoster')->name('publish');
            Route::post('/templates', 'saveTemplate')->name('template');
            Route::post('/templates/{stpl}/toggle', 'toggleTemplate')->whereNumber('stpl')->name('template.toggle');
            Route::post('/swaps/{swap}', 'decideSwap')->whereNumber('swap')->name('swap');
            Route::post('/settings', 'rosterSettings')->name('settings');
        });
    });
    Route::prefix('hr/time-clock')->name('clock.')->controller(Controllers\WorkforceController::class)->group(function () {
        Route::get('/kiosk', 'kiosk')->middleware('perm:timeclock.kiosk')->name('kiosk');
        Route::post('/kiosk', 'kioskPunch')->middleware(['perm:timeclock.kiosk', 'throttle:30,1'])->name('kiosk.punch');
        Route::get('/', 'clock')->middleware('perm:roster.view')->name('index');
        Route::middleware('perm:timeclock.approve')->group(function () {
            Route::post('/punches', 'savePunch')->name('punch');
            Route::post('/punches/{punch}', 'updatePunch')->whereNumber('punch')->name('punch.update');
            Route::post('/punches/{punch}/delete', 'deletePunch')->whereNumber('punch')->name('punch.delete');
            Route::post('/approve', 'approveDay')->name('approve');
            Route::post('/pin/{employee}', 'setPin')->whereNumber('employee')->name('pin');
            Route::post('/settings', 'clockSettings')->name('settings');
        });
    });
    // v4.9 service charge & tips (tronc)
    Route::prefix('accounts/service-charge')->name('sc.')->controller(Controllers\ServiceChargeController::class)->middleware('perm:servicecharge.manage,servicecharge.approve')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{scdist}', 'show')->whereNumber('scdist')->name('show');
        Route::get('/{scdist}/print', 'print')->whereNumber('scdist')->name('print');
        Route::post('/{scdist}/cancel', 'cancel')->whereNumber('scdist')->name('cancel');
        Route::middleware('perm:servicecharge.manage')->group(function () {
            Route::post('/tips', 'storeTip')->name('tip');
            Route::post('/tips/{tip}/cancel', 'cancelTip')->whereNumber('tip')->name('tip.cancel');
            Route::post('/', 'store')->name('store');
            Route::post('/{scdist}/recompute', 'recompute')->whereNumber('scdist')->name('recompute');
            Route::post('/{scdist}/lines/{scline}', 'exclude')->whereNumber('scdist')->whereNumber('scline')->name('exclude');
            Route::post('/points', 'points')->name('points');
        });
        Route::middleware('perm:servicecharge.approve')->group(function () {
            Route::post('/{scdist}/approve', 'approve')->whereNumber('scdist')->name('approve');
            Route::post('/{scdist}/post', 'post')->whereNumber('scdist')->name('post');
            Route::post('/settings', 'settings')->name('settings');
        });
    });

    // ---- Night audit
    Route::middleware('perm:nightaudit.run')->group(function () {
        Route::get('/night-audit', [Controllers\NightAuditController::class, 'index'])->name('nightaudit.index');
        Route::post('/night-audit', [Controllers\NightAuditController::class, 'run'])->name('nightaudit.run');
        Route::get('/night-audit/{audit}', [Controllers\NightAuditController::class, 'show'])->name('nightaudit.show');
    });

    // ---- Reports
    // v1.8 accounts payable
    // v7.55 billing-vs-collection folio workpaper (non-posting)
    Route::prefix('accounts/billing-reconciliation')->name('billingrec.')->controller(Controllers\BillingReconciliationController::class)->middleware('perm:billingrec.view')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('perm:billingrec.manage')->name('store');
        Route::get('/{billingReconciliation}', 'show')->whereNumber('billingReconciliation')->name('show');
        Route::get('/{billingReconciliation}/print', 'print')->whereNumber('billingReconciliation')->name('print');
        Route::post('/{billingReconciliation}/refresh', 'refresh')->whereNumber('billingReconciliation')->middleware('perm:billingrec.manage')->name('refresh');
        Route::put('/{billingReconciliation}/items/{billingReconciliationItem}', 'updateItem')->whereNumber('billingReconciliation')->whereNumber('billingReconciliationItem')
            ->middleware('perm:billingrec.manage')->name('items.update');
        Route::get('/{billingReconciliation}/items/{billingReconciliationItem}/evidence', 'evidence')->whereNumber('billingReconciliation')->whereNumber('billingReconciliationItem')->name('items.evidence');
        Route::post('/{billingReconciliation}/finalise', 'finalise')->whereNumber('billingReconciliation')->middleware('perm:billingrec.approve')->name('finalise');
        Route::post('/{billingReconciliation}/reopen', 'reopen')->whereNumber('billingReconciliation')->middleware('perm:billingrec.approve')->name('reopen');
    });

    // v4.6 bank & Mobile Money reconciliation
    Route::prefix('accounts/bank-reconciliation')->name('bankrec.')->controller(Controllers\BankRecController::class)->middleware('perm:bankrec.view')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/new', 'create')->middleware('perm:bankrec.manage')->name('create');
        Route::post('/', 'store')->middleware(['perm:bankrec.manage', 'throttle:20,1'])->name('store');
        Route::post('/settings', 'settings')->middleware('perm:bankrec.approve')->name('settings');
        Route::get('/{bstmt}', 'show')->whereNumber('bstmt')->name('show');
        Route::get('/{bstmt}/print', 'print')->whereNumber('bstmt')->name('print');
        Route::post('/{bstmt}/auto-match', 'autoMatch')->whereNumber('bstmt')->middleware('perm:bankrec.manage')->name('automatch');
        Route::post('/{bstmt}/book-fees', 'bookFees')->whereNumber('bstmt')->middleware('perm:bankrec.manage')->name('bookfees');
        Route::post('/{bstmt}/match', 'match')->whereNumber('bstmt')->middleware('perm:bankrec.manage')->name('match');
        Route::post('/{bstmt}/lines', 'addLine')->whereNumber('bstmt')->middleware('perm:bankrec.manage')->name('lines.add');
        Route::post('/{bstmt}/closing', 'closing')->whereNumber('bstmt')->middleware('perm:bankrec.manage')->name('closing');
        Route::post('/{bstmt}/finalise', 'finalise')->whereNumber('bstmt')->middleware('perm:bankrec.approve')->name('finalise');
        Route::post('/{bstmt}/reopen', 'reopen')->whereNumber('bstmt')->middleware('perm:bankrec.approve')->name('reopen');
        Route::delete('/{bstmt}', 'destroy')->whereNumber('bstmt')->middleware('perm:bankrec.manage')->name('destroy');
        Route::post('/lines/{bline}/unmatch', 'unmatch')->whereNumber('bline')->middleware('perm:bankrec.manage')->name('lines.unmatch');
        Route::post('/lines/{bline}/ignore', 'ignore')->whereNumber('bline')->middleware('perm:bankrec.manage')->name('lines.ignore');
        Route::post('/lines/{bline}/unignore', 'unignore')->whereNumber('bline')->middleware('perm:bankrec.manage')->name('lines.unignore');
        Route::post('/lines/{bline}/entry', 'entry')->whereNumber('bline')->middleware('perm:bankrec.manage')->name('lines.entry');
        Route::delete('/lines/{bline}', 'deleteLine')->whereNumber('bline')->middleware('perm:bankrec.manage')->name('lines.delete');
    });

    // v4.7 petty cash (imprest) & staff expense claims
    Route::prefix('accounts/petty-cash')->name('petty.')->controller(Controllers\PettyCashController::class)->middleware('perm:petty.view')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/floats', 'storeFloat')->middleware('perm:petty.approve')->name('floats.store');
        Route::post('/settings', 'settings')->middleware('perm:petty.approve')->name('settings');
        Route::get('/{pfloat}', 'show')->whereNumber('pfloat')->name('show');
        Route::put('/{pfloat}', 'updateFloat')->whereNumber('pfloat')->middleware('perm:petty.approve')->name('floats.update');
        Route::post('/{pfloat}/active', 'toggleFloat')->whereNumber('pfloat')->middleware('perm:petty.approve')->name('floats.active');
        Route::post('/{pfloat}/voucher', 'expense')->whereNumber('pfloat')->middleware(['perm:petty.manage,petty.approve', 'throttle:60,1'])->name('expense');
        Route::post('/{pfloat}/iou', 'iou')->whereNumber('pfloat')->middleware('perm:petty.manage,petty.approve')->name('iou');
        Route::post('/{pfloat}/count', 'count')->whereNumber('pfloat')->middleware('perm:petty.manage,petty.approve')->name('count');
        Route::post('/{pfloat}/replenish', 'replenish')->whereNumber('pfloat')->middleware('perm:petty.manage,petty.approve')->name('replenish');
        Route::get('/txn/{ptxn}/print', 'printTxn')->whereNumber('ptxn')->name('txn.print');
        Route::get('/txn/{ptxn}/receipt', 'receipt')->whereNumber('ptxn')->name('txn.receipt');
        Route::post('/txn/{ptxn}/settle', 'settle')->whereNumber('ptxn')->middleware('perm:petty.manage,petty.approve')->name('txn.settle');
        Route::post('/txn/{ptxn}/approve', 'approve')->whereNumber('ptxn')->middleware('perm:petty.approve')->name('txn.approve');
        Route::post('/txn/{ptxn}/reject', 'reject')->whereNumber('ptxn')->middleware('perm:petty.approve')->name('txn.reject');
        Route::post('/txn/{ptxn}/void', 'void')->whereNumber('ptxn')->middleware('perm:petty.manage,petty.approve')->name('txn.void');
        Route::get('/replenishment/{prepl}/print', 'printRepl')->whereNumber('prepl')->name('repl.print');
        Route::post('/replenishment/{prepl}/approve', 'approveRepl')->whereNumber('prepl')->middleware('perm:petty.approve')->name('repl.approve');
        Route::post('/replenishment/{prepl}/reject', 'rejectRepl')->whereNumber('prepl')->middleware('perm:petty.approve')->name('repl.reject');
        Route::post('/replenishment/{prepl}/pay', 'payRepl')->whereNumber('prepl')->middleware('perm:petty.approve')->name('repl.pay');
        Route::post('/count/{pcount}/approve', 'approveCount')->whereNumber('pcount')->middleware('perm:petty.approve')->name('count.approve');
        Route::post('/count/{pcount}/reject', 'rejectCount')->whereNumber('pcount')->middleware('perm:petty.approve')->name('count.reject');
    });
    // v7.40 Car Rental — self-drive fleet + booking desk
    Route::prefix('rentals')->name('rentals.')->controller(Controllers\RentalController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:rentals.view')->name('index');
        Route::get('/fleet', 'fleet')->middleware('perm:rentals.view')->name('fleet');
        Route::middleware('perm:rentals.manage')->group(function () {
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
            Route::post('/', 'store')->name('store');
            Route::post('/vehicles', 'vehicleStore')->name('vehicles.store');
            Route::post('/vehicles/{rentalVehicle}', 'vehicleUpdate')->whereNumber('rentalVehicle')->name('vehicles.update'); // renamed: {vehicle} was shadowed by Route::model('vehicle', Vehicle)
            Route::post('/{rental}/pickup', 'pickup')->whereNumber('rental')->name('pickup');
            Route::post('/{rental}/return', 'return')->whereNumber('rental')->name('return');
            Route::post('/{rental}/cancel', 'cancel')->whereNumber('rental')->name('cancel');
            Route::post('/{rental}/bill', 'bill')->whereNumber('rental')->name('bill');
        });
    });

    Route::prefix('accounts/expense-claims')->name('claims.')->controller(Controllers\ExpenseClaimController::class)->middleware('perm:claims.submit,claims.approve,claims.pay')->group(function () {
        Route::get('/', 'index')->middleware('perm:claims.approve,claims.pay')->name('index');
        Route::get('/mine', 'mine')->name('mine');
        Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
        Route::post('/settings', 'settings')->middleware('perm:claims.approve')->name('settings');
        Route::get('/{eclaim}', 'show')->whereNumber('eclaim')->name('show');
        Route::get('/{eclaim}/print', 'print')->whereNumber('eclaim')->name('print');
        Route::put('/{eclaim}', 'update')->whereNumber('eclaim')->name('update');
        Route::delete('/{eclaim}', 'destroy')->whereNumber('eclaim')->name('destroy');
        Route::post('/{eclaim}/lines', 'addLine')->whereNumber('eclaim')->middleware('throttle:60,1')->name('lines.add');
        Route::delete('/lines/{ecline}', 'deleteLine')->whereNumber('ecline')->name('lines.delete');
        Route::get('/lines/{ecline}/receipt', 'receipt')->whereNumber('ecline')->name('lines.receipt');
        Route::post('/{eclaim}/submit', 'submit')->whereNumber('eclaim')->name('submit');
        Route::post('/{eclaim}/approve', 'approve')->whereNumber('eclaim')->middleware('perm:claims.approve')->name('approve');
        Route::post('/{eclaim}/reject', 'reject')->whereNumber('eclaim')->middleware('perm:claims.approve')->name('reject');
        Route::post('/{eclaim}/pay', 'pay')->whereNumber('eclaim')->middleware('perm:claims.pay')->name('pay');
    });

    Route::prefix('ap')->name('ap.')->controller(Controllers\ApController::class)->middleware('perm:ap.view')->group(function () {
        Route::get('/bills', 'bills')->name('bills');
        Route::get('/bills/create', 'create')->middleware('perm:ap.manage')->name('bills.create');
        Route::get('/bills/fx-rate', 'fxRate')->middleware('perm:ap.manage')->name('bills.fx-rate');
        Route::post('/bills', 'store')->middleware(['perm:ap.manage', 'throttle:60,1'])->name('bills.store');
        Route::get('/bills/{bill}', 'show')->whereNumber('bill')->name('bills.show');
        Route::post('/bills/{bill}/approve', 'approve')->whereNumber('bill')->middleware('perm:ap.approve')->name('bills.approve');
        Route::post('/bills/{bill}/cancel', 'cancel')->whereNumber('bill')->middleware('perm:ap.manage')->name('bills.cancel');
        Route::post('/bills/{bill}/apply', 'apply')->whereNumber('bill')->middleware('perm:ap.pay')->name('bills.apply');
        Route::delete('/allocations/{alloc}', 'unallocate')->whereNumber('alloc')->middleware('perm:ap.pay')->name('allocations.delete');
        Route::get('/payments', 'payments')->name('payments');
        Route::get('/payments/fx-rate', 'payFxRate')->middleware('perm:ap.pay')->name('payments.fx-rate'); // v7.54 board on the selected settlement date
        Route::get('/payments/create', 'payCreate')->middleware('perm:ap.pay')->name('payments.create');
        Route::post('/payments', 'payStore')->middleware(['perm:ap.pay', 'throttle:60,1'])->name('payments.store');
        Route::get('/payments/{spay}', 'payShow')->whereNumber('spay')->name('payments.show');
        Route::post('/payments/{spay}/cancel', 'payCancel')->whereNumber('spay')->middleware('perm:ap.pay')->name('payments.cancel');
        Route::get('/ageing', 'ageing')->name('ageing');
        Route::get('/suppliers/{supplier}/statement', 'statement')->whereNumber('supplier')->name('statement');
        // v4.4 Ghana withholding tax
        Route::get('/wht', [Controllers\WhtController::class, 'index'])->name('wht');
        Route::get('/wht/export', [Controllers\WhtController::class, 'export'])->name('wht.export');
        Route::get('/wht/quote', [Controllers\WhtController::class, 'quote'])->middleware('perm:ap.pay')->name('wht.quote');
        Route::post('/wht/file', [Controllers\WhtController::class, 'file'])->middleware('perm:ap.approve')->name('wht.file');
        Route::post('/wht/settings', [Controllers\WhtController::class, 'settings'])->middleware('perm:ap.approve')->name('wht.settings');
        Route::get('/payments/{spay}/wht-certificate', [Controllers\WhtController::class, 'certificate'])->whereNumber('spay')->name('payments.certificate');
    });

    // v1.6 general ledger
    Route::prefix('gl')->name('gl.')->controller(Controllers\GlController::class)->middleware('perm:gl.view')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/accounts/{head}', 'ledger')->whereNumber('head')->name('ledger');
        Route::get('/journals', 'journals')->name('journals');
        Route::get('/journals/create', 'create')->middleware('perm:gl.journal')->name('journals.create');
        Route::post('/journals', 'store')->middleware(['perm:gl.journal', 'throttle:30,1'])->name('journals.store');
        Route::get('/journals/{journal}', 'show')->whereNumber('journal')->name('journals.show');
        Route::post('/journals/{journal}/reverse', 'reverse')->whereNumber('journal')->middleware('perm:gl.journal')->name('journals.reverse');
        Route::post('/journals/{journal}/allocate', 'allocate')->whereNumber('journal')->middleware('perm:gl.journal')->name('journals.allocate');   // v8.1
        Route::post('/sync', 'sync')->middleware(['perm:gl.manage', 'throttle:10,1'])->name('sync');
        Route::post('/lock', 'lock')->middleware('perm:gl.manage')->name('lock');
        Route::get('/mapping', 'mapping')->middleware('perm:gl.manage')->name('mapping');
        Route::post('/mapping', 'saveMapping')->middleware('perm:gl.manage')->name('mapping.save');
    });
