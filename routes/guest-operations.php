<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

    // ---- Reservations
    Route::prefix('reservations')->name('reservations.')->controller(Controllers\ReservationController::class)->group(function () {
        // v7.41 Reservation Workspace — the room board
        Route::get('/workspace', [Controllers\ReservationWorkspaceController::class, 'index'])->middleware('perm:reservations.view')->name('workspace');
        Route::get('/workspace/report', [Controllers\ReservationWorkspaceController::class, 'report'])->middleware('perm:reservations.view')->name('workspace.report');
        Route::get('/', 'index')->middleware('perm:reservations.view')->name('index');
        Route::get('/availability', 'availability')->middleware('perm:reservations.view')->name('availability');
        Route::get('/api/rates', 'apiRates')->middleware('perm:reservations.view')->name('api.rates');
        Route::get('/api/free-rooms', 'apiFreeRooms')->middleware('perm:reservations.view')->name('api.rooms');
        Route::get('/api/guests', 'apiGuests')->middleware('perm:reservations.view')->name('api.guests');
        Route::get('/desk/search', [Controllers\ReservationDeskController::class, 'search'])->middleware('perm:reservations.view')->name('desk.search');
        Route::get('/export', [Controllers\ReservationDeskController::class, 'export'])->middleware('perm:reservations.view')->name('export');
        Route::get('/plan/print', [Controllers\ReservationDeskController::class, 'printPlan'])->middleware('perm:reservations.view')->name('plan');
        Route::get('/day-summary/print', [Controllers\ReservationDeskController::class, 'daySummary'])->middleware('perm:reservations.view')->name('daysummary');
        Route::get('/amenities', [Controllers\ReservationDeskController::class, 'amenities'])->middleware('perm:reservations.view')->name('amenities');
        Route::post('/amenities', [Controllers\ReservationDeskController::class, 'storeAmenity'])->middleware('perm:reservations.edit')->name('amenities.store');
        Route::post('/images', [Controllers\ReservationDeskController::class, 'storeImage'])->middleware('perm:reservations.edit')->name('images.store');
        Route::post('/images/{image}/hide', [Controllers\ReservationDeskController::class, 'destroyImage'])->middleware('perm:reservations.edit')->name('images.hide');
        Route::get('/stays/{stay}/card', [Controllers\ReservationDeskController::class, 'stayCard'])->middleware('perm:reservations.view')->name('stay');
        Route::post('/stays/{stay}/checkout-preview', [Controllers\ReservationDeskController::class, 'previewCheckout'])->middleware('perm:frontdesk.checkout')->name('checkout.preview');
        Route::get('/rooms/{room}/card', [Controllers\ReservationDeskController::class, 'roomCard'])->middleware('perm:reservations.view')->name('room');
        Route::post('/stays/{stay}/dates/preview', [Controllers\ReservationDeskController::class, 'previewDates'])->middleware('perm:reservations.view')->name('dates.preview');
        Route::post('/stays/{stay}/dates', [Controllers\ReservationDeskController::class, 'dates'])->middleware('perm:reservations.edit')->name('dates');
        Route::post('/stays/{stay}/place-check', [Controllers\ReservationDeskController::class, 'placeCheck'])->middleware('perm:reservations.view')->name('place.check');
        Route::post('/stays/{stay}/split/preview', [Controllers\ReservationDeskController::class, 'previewSplit'])->middleware('perm:reservations.view')->name('split.preview');
        Route::post('/stays/{stay}/split', [Controllers\ReservationDeskController::class, 'split'])->middleware('perm:reservations.edit')->name('split');
        Route::post('/stays/{stay}/place', [Controllers\ReservationDeskController::class, 'place'])->middleware('perm:reservations.edit,frontdesk.roommove')->name('place');
        Route::post('/outages/preview', [Controllers\ReservationDeskController::class, 'previewOutage'])->middleware('perm:reservations.view')->name('outages.preview');
        Route::post('/outages', [Controllers\ReservationDeskController::class, 'storeOutage'])->middleware('perm:reservations.edit')->name('outages.store');
        Route::post('/outages/{outage}/release', [Controllers\ReservationDeskController::class, 'releaseOutage'])->middleware('perm:reservations.edit')->name('outages.release');
        Route::post('/waitlist', [Controllers\ReservationDeskController::class, 'storeWaitlist'])->middleware('perm:reservations.create')->name('waitlist.store');
        Route::post('/waitlist/{wait}/check', [Controllers\ReservationDeskController::class, 'checkWaitlist'])->middleware('perm:reservations.view')->name('waitlist.check');
        Route::post('/waitlist/{wait}/convert', [Controllers\ReservationDeskController::class, 'convertWaitlist'])->middleware('perm:reservations.create')->name('waitlist.convert');
        Route::get('/desk/tick', [Controllers\ReservationDeskController::class, 'tick'])->middleware('perm:reservations.view')->name('tick');
        Route::post('/images/{image}/primary', [Controllers\ReservationDeskController::class, 'makePrimary'])->middleware('perm:reservations.edit')->name('images.primary');
        Route::post('/waitlist/{wait}/cancel', [Controllers\ReservationDeskController::class, 'cancelWaitlist'])->middleware('perm:reservations.edit')->name('waitlist.cancel');
        Route::post('/desk-views', [Controllers\ReservationDeskController::class, 'saveView'])->middleware('perm:reservations.view')->name('views.store');
        Route::delete('/desk-views/{deskView}', [Controllers\ReservationDeskController::class, 'destroyView'])->middleware('perm:reservations.view')->name('views.destroy');
        Route::get('/create', 'create')->middleware('perm:reservations.create')->name('create');
        // Group reservation: several rooms, one booking, per-room dates and occupants.
        Route::get('/group', [Controllers\GroupReservationController::class, 'create'])->middleware('perm:reservations.create')->name('group');
        Route::post('/group', [Controllers\GroupReservationController::class, 'store'])->middleware('perm:reservations.create')->name('group.store');
        Route::post('/{reservation}/combine-bill', [Controllers\GroupReservationController::class, 'combineBill'])->middleware('perm:folio.split')->name('combine-bill');
        Route::post('/stays/{stay}/occupant', [Controllers\GroupReservationController::class, 'updateOccupant'])->middleware('perm:reservations.edit')->name('occupant');
        // The booking desk can open a city-ledger account without leaving the form.
        Route::post('/company', 'storeCompany')->middleware('perm:credit.manage')->name('company.store');
        Route::post('/', 'store')->middleware('perm:reservations.create')->name('store');
        Route::get('/{reservation}', 'show')->middleware('perm:reservations.view')->name('show');
        Route::get('/{reservation}/print', 'print')->middleware('perm:reservations.view')->name('print');
        Route::get('/{reservation}/pdf', 'downloadConfirmationPdf')->middleware('perm:reservations.view')->name('pdf');
        Route::get('/{reservation}/edit', 'edit')->middleware('perm:reservations.edit')->name('edit');
        Route::put('/{reservation}', 'update')->middleware('perm:reservations.edit')->name('update');
        Route::post('/{reservation}/cancel', 'cancel')->middleware('perm:reservations.cancel')->name('cancel');
        Route::post('/{reservation}/email', [Controllers\EmailController::class, 'reservation'])->middleware('perm:reservations.view')->name('email');
        Route::post('/{reservation}/checkin-link', [Controllers\EmailController::class, 'precheckin'])->middleware('perm:reservations.edit')->name('checkin-link');
        Route::post('/{reservation}/message', [Controllers\MessageController::class, 'sendForReservation'])->middleware('perm:messages.send')->name('message');
        Route::post('/{reservation}/no-show', 'noShow')->middleware('perm:reservations.cancel')->name('noshow');
        Route::post('/{reservation}/confirm', 'confirm')->middleware('perm:reservations.edit')->name('confirm');
        Route::post('/{reservation}/advance-preview', 'previewAdvance')->middleware('perm:folio.view')->name('advance.preview');
        Route::post('/{reservation}/advance', 'advance')->middleware('perm:folio.payment')->name('advance');
        Route::post('/{reservation}/assign', 'assignRooms')->middleware('perm:reservations.edit')->name('assign');
    });

    // ---- Billing / Folios
    Route::prefix('folios')->name('folios.')->controller(Controllers\FolioController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:folio.view')->name('index');
        Route::get('/invoices', 'invoices')->middleware('perm:folio.view')->name('invoices');
        Route::get('/transactions', 'transactions')->middleware('perm:folio.view')->name('transactions');
        Route::get('/non-resident', 'walkIn')->middleware('perm:folio.post')->name('walkin');
        Route::post('/non-resident', 'storeWalkIn')->middleware('perm:folio.post')->name('walkin.store');
        Route::get('/{folio}', 'show')->middleware('perm:folio.view')->name('show');
        Route::get('/{folio}/invoice', 'invoice')->middleware('perm:folio.view')->name('invoice');
        Route::get('/{folio}/statement', 'statement')->middleware('perm:folio.view')->name('statement');
        Route::post('/{folio}/email', [Controllers\EmailController::class, 'folio'])->middleware('perm:folio.view')->name('email');
        Route::post('/{folio}/charge-preview', 'previewCharge')->middleware('perm:folio.view')->name('charge.preview');
        Route::post('/{folio}/charge', 'charge')->middleware('perm:folio.post')->name('charge');
        Route::post('/{folio}/payment-preview', 'previewPayment')->middleware('perm:folio.view')->name('payment.preview');
        Route::post('/{folio}/payment', 'payment')->middleware('perm:folio.payment')->name('payment');
        Route::post('/{folio}/discount', 'discount')->middleware('perm:folio.discount')->name('discount');
        Route::post('/{folio}/transfer', 'transfer')->middleware('perm:folio.payment')->name('transfer');
        Route::post('/{folio}/move', 'move')->middleware('perm:folio.split')->name('move');
        Route::post('/{folio}/fixed-charges', 'addFixed')->middleware('perm:folio.post')->name('fixed.store');
        Route::delete('/fixed-charges/{fixedCharge}', 'removeFixed')->middleware('perm:folio.post')->name('fixed.destroy');
        Route::post('/{folio}/close', 'close')->middleware('perm:folio.payment')->name('close');
        Route::post('/{folio}/reopen', 'reopen')->middleware('perm:folio.void')->name('reopen');
        Route::get('/transaction/{tx}/receipt', 'receipt')->middleware('perm:folio.view')->name('receipt');
        Route::post('/transaction/{tx}/void', 'void')->middleware('perm:folio.void')->name('void');
    });

    // ---- v3.0 → v4.3 Ghana GRA VAT compliance: E-VAT stamping, credit notes, cancellations, VAT & Tourism Levy return
    Route::prefix('vat')->name('gst.')->controller(Controllers\GstController::class)->group(function () {
        Route::middleware('perm:gst.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/e-vat', 'einvoices')->name('einvoices');
            Route::get('/credit-notes', 'creditNotes')->name('credit_notes');
            Route::get('/return', 'vatReturn')->name('return');
            Route::get('/return/export', 'exportReturn')->name('return.export');
            Route::get('/documents/{folio}/payload', 'payload')->name('payload');
        });
        Route::middleware('perm:gst.manage')->group(function () {
            Route::post('/return/file', 'file')->name('file');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
            Route::post('/e-vat/retry', 'retryAll')->name('retry');
            Route::post('/documents/{folio}/e-vat', 'generate')->name('generate');
            Route::post('/restaurant-bills/{order}/e-vat', 'stampPos')->whereNumber('order')->name('pos_stamp'); // v4.4
            Route::post('/restaurant-bills/{order}/buyer', 'posBuyer')->whereNumber('order')->name('pos_buyer');
            Route::post('/documents/{folio}/buyer', 'refreshBuyer')->name('buyer');
            Route::post('/documents/{folio}/cancel', 'cancel')->name('cancel');
        });
        Route::middleware('perm:folio.credit_note')->group(function () {
            Route::get('/documents/{folio}/credit-note', 'creditNoteForm')->name('credit_note.create');
            Route::post('/documents/{folio}/credit-note', 'storeCreditNote')->name('credit_note.store');
        });
    });

    // ---- Guests & CRM
    Route::post('/guests/{guest}/documents', [Controllers\GrcController::class, 'upload'])->middleware('perm:frontdesk.checkin,guests.manage')->name('guests.documents.store');
    Route::get('/guest-documents/{document}', [Controllers\GrcController::class, 'document'])->middleware('perm:guests.view,frontdesk.checkin')->name('guests.documents.show');
    Route::delete('/guest-documents/{document}', [Controllers\GrcController::class, 'destroyDocument'])->middleware('perm:guests.manage')->name('guests.documents.destroy');
    Route::resource('guests', Controllers\GuestController::class)->except(['destroy'])->middleware('perm:guests.view');
    Route::get('/feedback', [Controllers\ReputationController::class, 'responses'])->middleware('perm:feedback.view')->name('feedback.index');

    // ---- v3.2 restaurant table reservations, floor plan & waitlist
    Route::prefix('dining')->name('tb.')->controller(Controllers\TableBookingController::class)->group(function () {
        Route::middleware('perm:tables.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/floor', 'floor')->name('floor');
            Route::get('/bookings/{tres}', 'show')->whereNumber('tres')->name('show');
            Route::get('/availability', 'availability')->name('availability');
            Route::get('/lookup', 'lookup')->name('lookup');
        });
        Route::middleware('perm:tables.book')->group(function () {
            Route::post('/bookings', 'store')->name('store');
            Route::put('/bookings/{tres}', 'update')->name('update');
            Route::post('/bookings/{tres}/confirm', 'confirm')->name('confirm');
            Route::post('/bookings/{tres}/seat', 'seat')->middleware('waiter.shift')->name('seat');
            Route::post('/bookings/{tres}/cancel', 'cancel')->name('cancel');
            Route::post('/bookings/{tres}/no-show', 'noShow')->name('noshow');
            Route::post('/bookings/{tres}/reinstate', 'reinstate')->name('reinstate');
            Route::post('/bookings/{tres}/resend', 'resend')->name('resend');
            Route::post('/bookings/{tres}/notify', 'notify')->name('notify');
            Route::post('/walk-in', 'walkIn')->middleware('waiter.shift')->name('walkin');
            Route::post('/waitlist', 'waitlistStore')->name('waitlist.store');
            Route::post('/tables/{ptable}/clear', 'clear')->name('clear');
        });
        Route::middleware('perm:tables.manage')->group(function () {
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
            Route::post('/floor/layout', 'layout')->name('layout');
            Route::post('/tables/{ptable}/block', 'block')->name('block');
        });
    });

    // ---- v3.3 guest transport & fleet desk
    Route::prefix('transport')->name('tr.')->controller(Controllers\TransportController::class)->group(function () {
        Route::middleware('perm:transport.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/trips', 'trips')->name('trips');
            Route::get('/trips/{trip}', 'show')->whereNumber('trip')->name('show');
            Route::get('/trips/{trip}/sheet', 'sheet')->whereNumber('trip')->name('sheet');
            Route::get('/trips/{trip}/options', 'options')->whereNumber('trip')->name('options');
            Route::get('/fleet/papers', 'fleet')->name('fleet');
            Route::get('/expenses', 'expenses')->name('expenses');
        });
        Route::middleware('perm:transport.book')->group(function () {
            Route::get('/trips/create', 'create')->name('create');
            Route::post('/trips', 'store')->name('store');
            Route::put('/trips/{trip}', 'update')->name('update');
            Route::post('/trips/{trip}/confirm', 'confirm')->name('confirm');
            Route::post('/trips/{trip}/assign', 'assign')->name('assign');
            Route::post('/trips/{trip}/unassign', 'unassign')->name('unassign');
            Route::post('/trips/{trip}/start', 'start')->name('start');
            Route::post('/trips/{trip}/onboard', 'onboard')->name('onboard');
            Route::post('/trips/{trip}/complete', 'complete')->name('complete');
            Route::post('/trips/{trip}/no-show', 'noShow')->name('noshow');
            Route::post('/trips/{trip}/cancel', 'cancel')->name('cancel');
            Route::post('/trips/{trip}/resend', 'resend')->name('resend');
            Route::post('/trips/{trip}/charge', 'charge')->name('charge');
            Route::post('/stays/{stay}/charges', 'chargeStay')->name('stay.charge');
        });
        Route::middleware('perm:transport.drive,transport.book')->group(function () {
            Route::get('/my', 'my')->name('my');
            Route::post('/my/{trip}/{action}', 'myAction')->whereNumber('trip')->where('action', 'start|onboard|complete|noshow')->name('my.action');
            Route::post('/my/expense', 'myFuel')->name('my.expense');
        });
        Route::middleware('perm:transport.manage')->group(function () {
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
            Route::post('/expenses', 'storeExpense')->name('expenses.store');
            Route::delete('/expenses/{vexp}', 'destroyExpense')->name('expenses.destroy');
        });
        // Full fleet management: GPS tracking, fuel efficiency, workshop planning
        Route::prefix('fleet')->name('fleet.')->controller(Controllers\FleetController::class)->group(function () {
            Route::middleware('perm:transport.view')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/vehicles/{vehicle}', 'vehicle')->whereNumber('vehicle')->name('vehicle');
                Route::get('/export/vehicles', 'exportVehicles')->name('export.vehicles');
            });
            Route::middleware('perm:transport.track')->group(function () {
                Route::get('/tracking', 'tracking')->name('tracking');
                Route::get('/tracking.json', 'feed')->name('feed');
                Route::get('/export/gps', 'exportGps')->name('export.gps');
            });
            Route::middleware('perm:transport.fleet')->group(function () {
                Route::post('/trackers', 'trackerStore')->name('trackers.store');
                Route::post('/trackers/{tracker}/token', 'trackerRotate')->name('trackers.token');
                Route::post('/trackers/{tracker}/toggle', 'trackerToggle')->name('trackers.toggle');
                Route::post('/trackers/{tracker}/window', 'trackerWindow')->name('trackers.window');
                Route::get('/maintenance', 'maintenance')->name('maintenance');
                Route::post('/maintenance/plans', 'planStore')->name('plans.store');
                Route::post('/maintenance/plans/{plan}/retire', 'planDelete')->name('plans.retire');
                Route::post('/maintenance/records', 'recordStore')->name('records.store');
                Route::post('/maintenance/records/{record}/start', 'recordStart')->name('records.start');
                Route::post('/maintenance/records/{record}/complete', 'recordComplete')->name('records.complete');
                Route::post('/maintenance/records/{record}/cancel', 'recordCancel')->name('records.cancel');
                Route::get('/fuel', 'fuel')->name('fuel');
                Route::post('/fuel', 'fuelStore')->name('fuel.store');
                Route::put('/fuel/{expense}', 'fuelUpdate')->whereNumber('expense')->name('fuel.update');
                Route::post('/fuel/{expense}/delete', 'fuelDestroy')->whereNumber('expense')->name('fuel.destroy');
                Route::get('/export/fuel', 'exportFuel')->name('export.fuel');
                Route::get('/export/work-orders', 'exportMaintenance')->name('export.work_orders');
                Route::get('/export/inspections', 'exportInspections')->name('export.inspections');
                Route::post('/geofences', 'geofenceStore')->name('geofences.store');
                Route::post('/geofences/{geofence}/toggle', 'geofenceToggle')->name('geofences.toggle');
                Route::post('/geofences/{geofence}/delete', 'geofenceDelete')->name('geofences.delete');
                // v10.12 `{event}` collided with the shared Route::model('event', BanquetEvent) binding — the dispatcher
                // then injected a blank FleetGeofenceEvent and the save() below INSERTed a row missing geofence_id. Renamed.
                Route::post('/geofence-events/{geofenceEvent}/ack', 'geofenceAck')->name('geofences.ack');
                Route::get('/work-order/{record}/print', 'workOrderPrint')->whereNumber('record')->name('work-order.print');
                Route::get('/cost-statement', 'costStatement')->name('cost-statement');
                Route::get('/cost-statement/export', 'exportStatement')->name('cost-statement.export');
                Route::post('/inspections', 'inspectionStore')->name('inspections.store');
                Route::post('/incidents', 'incidentStore')->name('incidents.store');
                Route::post('/incidents/{vehicleIncident}/status', 'incidentStatus')->whereNumber('vehicleIncident')->name('incidents.status'); // renamed: {incident} was shadowed by Route::model('incident', SecurityIncident)
                Route::get('/settings', 'settings')->name('settings');
                Route::post('/settings', 'saveSettings')->name('settings.save');
            });
        });
    });

    // ---- v3.4 foreign guests: Form C (FRRO) & forex desk
    Route::prefix('foreign')->name('fg.')->group(function () {
        Route::controller(Controllers\FormCController::class)->group(function () {
            Route::middleware('perm:formc.view')->group(function () {
                Route::get('/form-c', 'index')->name('formc');
                Route::get('/form-c/export', 'export')->name('formc.export');
                Route::get('/form-c/{formc}', 'show')->whereNumber('formc')->name('formc.show');
                Route::get('/form-c/{formc}/print', 'print')->whereNumber('formc')->name('formc.print');
            });
            Route::middleware('perm:formc.manage')->group(function () {
                Route::put('/form-c/{formc}', 'update')->whereNumber('formc')->name('formc.update');
                Route::post('/form-c/{formc}/submit', 'submit')->whereNumber('formc')->name('formc.submit');
                Route::post('/form-c/{formc}/departure', 'departure')->whereNumber('formc')->name('formc.departure');
                Route::post('/form-c/{formc}/exempt', 'exempt')->whereNumber('formc')->name('formc.exempt');
                Route::post('/form-c/{formc}/reopen', 'reopen')->whereNumber('formc')->name('formc.reopen');
                Route::post('/stays/{stay}/form-c', 'create')->name('formc.create');
                Route::post('/guests/{guest}/nationality', 'nationality')->name('nationality');
            });
            Route::middleware('perm:formc.manage,forex.manage')->group(function () {
                Route::get('/settings', 'settings')->name('settings');
                Route::post('/settings', 'saveSettings')->name('settings.save');
            });
        });
        Route::controller(Controllers\ForexController::class)->group(function () {
            Route::middleware('perm:forex.view')->group(function () {
                Route::get('/forex', 'index')->name('fx');
                Route::get('/forex/{fx}', 'show')->whereNumber('fx')->name('fx.show');
                Route::get('/forex/{fx}/certificate', 'certificate')->whereNumber('fx')->name('fx.certificate');
                Route::get('/rates', 'rates')->name('rates');
                Route::get('/position', 'position')->name('position');
                Route::get('/surrenders/{surrender}', 'showSurrender')->whereNumber('surrender')->name('surrender.show');
            });
            Route::middleware('perm:forex.exchange')->group(function () {
                Route::get('/forex/new', 'create')->name('fx.create');
                Route::get('/forex/quote', 'quote')->name('fx.quote');
                Route::post('/forex', 'store')->name('fx.store');
            });
            Route::middleware('perm:forex.manage')->group(function () {
                Route::post('/forex/{fx}/void', 'void')->whereNumber('fx')->name('fx.void');
                Route::post('/rates', 'saveRates')->name('rates.save');
                Route::post('/rates/copy', 'copyRates')->name('rates.copy');
                Route::post('/surrenders', 'surrender')->name('surrender.store');
            });
        });
    });

    // ---- v3.5 front desk operations: log book, hand-over, wake-up calls, messages & parcels, traces
    Route::prefix('desk')->name('desk.')->controller(Controllers\FrontDeskOpsController::class)->group(function () {
        Route::middleware('perm:logbook.view')->group(function () {
            Route::get('/shift', 'shiftSession')->name('shift');
            Route::post('/shift/{session}/accept', 'acceptShiftHandover')->whereNumber('session')->name('shift.accept');
            Route::get('/logbook', 'logbook')->name('logbook');
            Route::get('/logbook/{logentry}', 'showEntry')->whereNumber('logentry')->name('logbook.show');
            Route::post('/logbook/acknowledge', 'acknowledge')->name('logbook.ack');
            Route::get('/handover', 'handover')->name('handover');
            Route::get('/handover/print', 'handoverPrint')->name('handover.print');
        });
        Route::middleware('perm:logbook.write')->group(function () {
            Route::post('/shift/start', 'startShiftSession')->name('shift.start');
            Route::post('/shift/{session}/close', 'closeShiftSession')->whereNumber('session')->name('shift.close');
            Route::post('/logbook', 'storeEntry')->name('logbook.store');
            Route::post('/logbook/{logentry}/comment', 'comment')->whereNumber('logentry')->name('logbook.comment');
            Route::post('/logbook/{logentry}/resolve', 'resolve')->whereNumber('logentry')->name('logbook.resolve');
            Route::post('/logbook/{logentry}/reopen', 'reopen')->whereNumber('logentry')->name('logbook.reopen');
        });
        Route::middleware('perm:logbook.manage')->group(function () {
            Route::post('/logbook/{logentry}/pin', 'pin')->whereNumber('logentry')->name('logbook.pin');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
        Route::middleware('perm:desk.wakeup')->group(function () {
            Route::get('/wakeups', 'wakeups')->name('wakeups');
            Route::get('/wakeups/print', 'wakeupsPrint')->name('wakeups.print');
            Route::post('/wakeups', 'storeWakeup')->name('wakeups.store');
            Route::post('/wakeups/{wakeup}/attempt', 'attempt')->whereNumber('wakeup')->name('wakeups.attempt');
            Route::post('/wakeups/{wakeup}/cancel', 'cancelWakeup')->whereNumber('wakeup')->name('wakeups.cancel');
        });
        Route::middleware('perm:desk.messages')->group(function () {
            Route::get('/messages', 'messages')->name('messages');
            Route::post('/messages', 'storeMessage')->name('messages.store');
            Route::get('/messages/{gmsg}/slip', 'slip')->whereNumber('gmsg')->name('messages.slip');
            Route::post('/messages/{gmsg}/notify', 'notify')->whereNumber('gmsg')->name('messages.notify');
            Route::post('/messages/{gmsg}/deliver', 'deliver')->whereNumber('gmsg')->name('messages.deliver');
            Route::post('/messages/{gmsg}/close', 'closeMessage')->whereNumber('gmsg')->name('messages.close');
        });
        Route::middleware('perm:desk.traces')->group(function () {
            Route::get('/traces', 'traces')->name('traces');
            Route::post('/traces', 'storeTrace')->name('traces.store');
            Route::post('/traces/{trace}/done', 'traceDone')->whereNumber('trace')->name('traces.done');
            Route::post('/traces/{trace}/cancel', 'traceCancel')->whereNumber('trace')->name('traces.cancel');
        });
    });

    // ---- v3.6 budgeting, forecasting & budget-vs-actual
    Route::controller(Controllers\BudgetController::class)->name('budgets.')->group(function () {
        Route::middleware('perm:budget.view')->group(function () {
            Route::get('/budgets', 'index')->name('index');
            Route::get('/budgets/cost-centres', 'costCentres')->name('costcentres');   // v8.1
            Route::get('/budgets/{budget}', 'show')->whereNumber('budget')->name('show');
            Route::get('/budgets/{budget}/export', 'export')->whereNumber('budget')->name('export');
            Route::get('/budgets/{budget}/variance', 'variance')->whereNumber('budget')->name('variance');   // v8.2
            Route::get('/budget-vs-actual', 'bva')->name('bva');
            Route::post('/budget-vs-actual/comments', 'comment')->name('comment');
            Route::delete('/budget-vs-actual/comments/{bcomment}', 'deleteComment')->whereNumber('bcomment')->name('comment.delete');
        });
        Route::middleware('perm:budget.manage')->group(function () {
            Route::post('/budgets', 'store')->name('store');
            Route::post('/budgets/{budget}/grid', 'saveGrid')->whereNumber('budget')->name('grid');
            Route::post('/budgets/{budget}/spread', 'spread')->whereNumber('budget')->name('spread');
            Route::post('/budgets/{budget}/adjust', 'adjust')->whereNumber('budget')->name('adjust');
            Route::post('/budgets/{budget}/stats', 'saveStats')->whereNumber('budget')->name('stats');
            Route::post('/budgets/{budget}/import', 'import')->whereNumber('budget')->name('import');
            Route::post('/budgets/{budget}/variance', 'explain')->whereNumber('budget')->name('explain');
            Route::post('/budgets/{budget}/submit', 'submit')->whereNumber('budget')->name('submit');
            Route::post('/budgets/{budget}/forecast', 'forecast')->whereNumber('budget')->name('forecast');
            Route::delete('/budgets/{budget}', 'destroy')->whereNumber('budget')->name('destroy');
        });
        Route::middleware('perm:budget.approve')->group(function () {
            Route::post('/budgets/settings', 'saveSettings')->name('settings');
            Route::post('/budgets/{budget}/variance/signoff', 'signOff')->whereNumber('budget')->name('signoff');
            Route::post('/budget-variance/comments/{bcomment}/accept', 'acceptExplanation')->whereNumber('bcomment')->name('comment.accept');
            Route::post('/budgets/{budget}/approve', 'approve')->whereNumber('budget')->name('approve');
            Route::post('/budgets/{budget}/return', 'returnDraft')->whereNumber('budget')->name('return');
            Route::post('/budgets/{budget}/reopen', 'reopen')->whereNumber('budget')->name('reopen');
            Route::post('/budgets/{budget}/activate', 'activate')->whereNumber('budget')->name('activate');
        });
    });

    // ---- v3.7 hotel system interfaces: PABX call accounting, door-lock key cards & master keys, interface hub
    Route::controller(Controllers\CallController::class)->name('calls.')->group(function () {
        Route::middleware('perm:calls.view')->group(function () {
            Route::get('/calls', 'index')->name('index');
            Route::get('/calls/rate', 'rate')->name('rate');
        });
        Route::middleware('perm:calls.manage')->group(function () {
            Route::post('/calls/import', 'import')->name('import');
            Route::post('/calls/manual', 'store')->name('store');
            Route::post('/calls/reprocess', 'reprocess')->name('reprocess');
            Route::post('/calls/{call}/post', 'post')->whereNumber('call')->name('post');
            Route::post('/calls/{call}/waive', 'waive')->whereNumber('call')->name('waive');
            Route::post('/calls/extensions', 'saveExtension')->name('extensions.store');
            Route::post('/calls/extensions/{ext}', 'saveExtension')->whereNumber('ext')->name('extensions.update');
            Route::post('/calls/extensions-sync', 'syncExtensions')->name('extensions.sync');
            Route::post('/calls/tariffs', 'saveTariff')->name('tariffs.store');
            Route::post('/calls/tariffs/{tariff}', 'saveTariff')->whereNumber('tariff')->name('tariffs.update');
        });
        Route::post('/stay/{stay}/phone-cos', 'cos')->whereNumber('stay')->middleware('perm:calls.manage,keys.issue')->name('cos');
    });
    Route::controller(Controllers\KeyCardController::class)->name('keys.')->group(function () {
        Route::get('/key-cards', 'index')->middleware('perm:keys.view')->name('index');
        Route::middleware('perm:keys.issue')->group(function () {
            Route::post('/key-cards/issue', 'issue')->name('issue');
            Route::post('/key-cards/read', 'read')->name('read');
            Route::post('/key-cards/{card}/lost', 'lost')->whereNumber('card')->name('lost');
            Route::post('/key-cards/{card}/return', 'returnCard')->whereNumber('card')->name('return');
        });
        Route::middleware('perm:keys.master')->group(function () {
            Route::post('/master-keys', 'saveKey')->name('master.store');
            Route::post('/master-keys/{mkey}', 'saveKey')->whereNumber('mkey')->name('master.update');
            Route::post('/master-keys/{mkey}/out', 'signOut')->whereNumber('mkey')->name('master.out');
            Route::post('/key-loans/{loan}/in', 'signIn')->whereNumber('loan')->name('master.in');
            Route::post('/key-loans/{loan}/lost', 'lostKey')->whereNumber('loan')->name('master.lost');
        });
    });
    Route::controller(Controllers\InterfaceController::class)->name('interfaces.')->middleware('perm:interfaces.manage')->group(function () {
        Route::get('/interfaces', 'index')->name('index');
        Route::post('/interfaces/settings', 'saveSettings')->name('settings');
        Route::post('/interfaces/token', 'token')->name('token');
        Route::post('/interfaces/commands/{icmd}/retry', 'retry')->whereNumber('icmd')->name('retry');
        Route::post('/interfaces/commands/retry-all', 'retryAll')->name('retry_all');
    });
    // ---- v7.55 Robita smart door lock module
    Route::controller(Controllers\RobitaController::class)->name('robita.')->middleware('perm:robita.manage')->group(function () {
        Route::get('/robita', 'index')->name('index');
        Route::get('/robita/devices', 'devices')->name('devices');
        Route::post('/robita/devices', 'register')->name('devices.store');
        Route::post('/robita/devices/{device}', 'update')->whereNumber('device')->name('devices.update');
        Route::post('/robita/devices/{device}/lock', 'toggleLock')->whereNumber('device')->name('devices.lock');
        Route::post('/robita/devices/{device}/force-lock', 'forceLock')->whereNumber('device')->name('devices.force_lock');
        Route::post('/robita/devices/{device}/force-unlock', 'forceUnlock')->whereNumber('device')->name('devices.force_unlock');
        Route::post('/robita/devices/{device}/status', 'toggleStatus')->whereNumber('device')->name('devices.status');
        Route::get('/robita/events', 'events')->name('events');
        Route::post('/robita/devices/{device}/credential', 'issueCredential')->whereNumber('device')->name('devices.credential');
        Route::post('/robita/credentials/{credential}/cancel', 'cancelCredential')->whereNumber('credential')->name('credentials.cancel');
        Route::get('/robita/settings', 'settings')->name('settings');
        Route::post('/robita/settings', 'settings')->name('settings.store');
        Route::post('/robita/settings/forget-legacy-cloud', 'forgetLegacyCloudConfig')->name('settings.forget_legacy_cloud');
        Route::post('/robita/tokens', 'regenerateTokens')->name('tokens');
        Route::post('/robita/poll', 'poll')->name('poll');
    });
    // webhook push endpoint – token in URL, no CSRF

    // ---- v3.8 gift vouchers & stored-value cards
    Route::controller(Controllers\GiftVoucherController::class)->name('vouchers.')->group(function () {
        Route::middleware('perm:vouchers.view')->group(function () {
            Route::get('/vouchers', 'index')->name('index');
            Route::get('/vouchers/{gvoucher}', 'show')->whereNumber('gvoucher')->name('show');
            Route::get('/vouchers/{gvoucher}/print', 'printCard')->whereNumber('gvoucher')->name('print');
            Route::post('/vouchers/lookup', 'lookup')->middleware('throttle:60,1')->name('lookup');
        });
        Route::middleware('perm:vouchers.sell')->group(function () {
            Route::post('/vouchers/sell', 'sell')->name('sell');
            Route::post('/vouchers/{gvoucher}/reload', 'reload')->whereNumber('gvoucher')->name('reload');
            Route::post('/vouchers/{gvoucher}/email', 'email')->whereNumber('gvoucher')->name('email');
        });
        Route::post('/vouchers/{gvoucher}/redeem', 'redeemFolio')->whereNumber('gvoucher')->middleware('perm:vouchers.redeem')->name('redeem');
        Route::middleware('perm:vouchers.manage')->group(function () {
            Route::post('/vouchers/{gvoucher}/suspend', 'suspend')->whereNumber('gvoucher')->name('suspend');
            Route::post('/vouchers/{gvoucher}/unsuspend', 'unsuspend')->whereNumber('gvoucher')->name('unsuspend');
            Route::post('/vouchers/{gvoucher}/cancel', 'cancel')->whereNumber('gvoucher')->name('cancel');
            Route::post('/vouchers/promo', 'promo')->name('promo');
            Route::post('/vouchers/types', 'saveType')->name('types.store');
            Route::post('/vouchers/types/{gvtype}', 'saveType')->whereNumber('gvtype')->name('types.update');
            Route::post('/vouchers/settings', 'saveSettings')->name('settings');
            Route::post('/vouchers/run', 'runNow')->name('run');
        });
    });

    // ---- v4.0 API & webhooks (developer console)
    Route::controller(Controllers\DeveloperController::class)->name('developers.')->group(function () {
        Route::middleware('perm:api.view,api.manage')->group(function () {
            Route::get('/developers', 'index')->name('index');
            Route::get('/developers/deliveries/{delivery}', 'delivery')->whereNumber('delivery')->name('delivery');
        });
        Route::middleware('perm:api.manage')->group(function () {
            Route::post('/developers/clients', 'storeClient')->name('clients.store');
            Route::post('/developers/clients/{apiclient}', 'updateClient')->whereNumber('apiclient')->name('clients.update');
            Route::post('/developers/clients/{apiclient}/rotate', 'rotateClient')->whereNumber('apiclient')->name('clients.rotate');
            Route::post('/developers/clients/{apiclient}/revoke', 'revokeClient')->whereNumber('apiclient')->name('clients.revoke');
            Route::post('/developers/webhooks', 'storeWebhook')->name('webhooks.store');
            Route::post('/developers/webhooks/{webhook}', 'updateWebhook')->whereNumber('webhook')->name('webhooks.update');
            Route::post('/developers/webhooks/{webhook}/rotate', 'rotateWebhook')->whereNumber('webhook')->name('webhooks.rotate');
            Route::post('/developers/webhooks/{webhook}/toggle', 'toggleWebhook')->whereNumber('webhook')->name('webhooks.toggle');
            Route::post('/developers/webhooks/{webhook}/ping', 'pingWebhook')->whereNumber('webhook')->name('webhooks.ping');
            Route::post('/developers/webhooks/{webhook}/delete', 'deleteWebhook')->whereNumber('webhook')->name('webhooks.delete');
            Route::post('/developers/deliveries/{delivery}/retry', 'retryDelivery')->whereNumber('delivery')->name('deliveries.retry');
            Route::post('/developers/deliver', 'deliverNow')->name('deliver');
            Route::post('/developers/settings', 'settings')->name('settings');
        });
    });

    // ---- v3.9 engineering: utilities & energy
    Route::controller(Controllers\UtilityController::class)->name('utilities.')->group(function () {
        Route::middleware('perm:utilities.view,utilities.record')->group(function () {
            Route::get('/utilities', 'index')->name('index');
            Route::get('/utilities/meters/{umeter}', 'meter')->whereNumber('umeter')->name('meter');
        });
        Route::middleware('perm:utilities.record')->group(function () {
            Route::post('/utilities/readings', 'saveReadings')->name('readings');
            Route::post('/utilities/meters/{umeter}/reading', 'saveReading')->whereNumber('umeter')->name('reading');
            Route::post('/utilities/readings/{mreading}/ack', 'acknowledge')->whereNumber('mreading')->name('ack');
        });
        Route::middleware('perm:utilities.manage')->group(function () {
            Route::post('/utilities/readings/{mreading}/delete', 'deleteReading')->whereNumber('mreading')->name('reading.delete');
            Route::post('/utilities/meters', 'saveMeter')->name('meters.store');
            Route::post('/utilities/meters/{umeter}', 'saveMeter')->whereNumber('umeter')->name('meters.update');
            Route::post('/utilities/bills', 'saveBill')->name('bills.store');
            Route::post('/utilities/bills/{ubill}', 'saveBill')->whereNumber('ubill')->name('bills.update');
            Route::post('/utilities/bills/{ubill}/post', 'postBill')->whereNumber('ubill')->name('bills.post');
            Route::post('/utilities/bills/{ubill}/rate', 'adoptRate')->whereNumber('ubill')->name('bills.rate');
            Route::post('/utilities/settings', 'saveSettings')->name('settings');
        });
    });

    // ---- v3.9 engineering: licences & statutory compliance
    Route::controller(Controllers\ComplianceController::class)->name('compliance.')->group(function () {
        Route::middleware('perm:compliance.view')->group(function () {
            Route::get('/compliance', 'index')->name('index');
            Route::get('/compliance/print', 'printRegister')->name('print');
            Route::get('/compliance/{citem}', 'show')->whereNumber('citem')->name('show');
            Route::get('/compliance/{citem}/document', 'document')->whereNumber('citem')->name('document');
            Route::get('/compliance/events/{cevent}/document', 'eventDocument')->whereNumber('cevent')->name('event.document');
        });
        Route::middleware('perm:compliance.manage')->group(function () {
            Route::post('/compliance', 'store')->name('store');
            Route::post('/compliance/{citem}', 'update')->whereNumber('citem')->name('update');
            Route::post('/compliance/{citem}/apply', 'apply')->whereNumber('citem')->name('apply');
            Route::post('/compliance/{citem}/renew', 'renew')->whereNumber('citem')->name('renew');
            Route::post('/compliance/{citem}/inspect', 'inspect')->whereNumber('citem')->name('inspect');
            Route::post('/compliance/{citem}/upload', 'upload')->whereNumber('citem')->name('upload');
            Route::post('/compliance/{citem}/archive', 'archive')->whereNumber('citem')->name('archive');
            Route::post('/compliance/template', 'template')->name('template');
            Route::post('/compliance/alerts', 'runAlerts')->name('alerts');
            Route::post('/compliance/settings', 'saveSettings')->name('settings');
        });
    });

    // ---- v3.1 guest feedback & online reputation
    Route::prefix('reputation')->name('rep.')->group(function () {
        Route::controller(Controllers\ReputationController::class)->group(function () {
            Route::middleware('perm:feedback.view')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/responses/{feedback}', 'response')->name('responses.show');
                Route::post('/responses/{feedback}/reviewed', 'markReviewed')->name('responses.reviewed');
            });
            Route::middleware('perm:feedback.manage')->group(function () {
                Route::get('/settings', 'settings')->name('settings');
                Route::post('/settings', 'saveSettings')->name('settings.save');
                Route::post('/templates', 'storeTemplate')->name('templates.store');
                Route::put('/templates/{rtpl}', 'updateTemplate')->name('templates.update');
                Route::delete('/templates/{rtpl}', 'destroyTemplate')->name('templates.destroy');
            });
        });
        Route::controller(Controllers\ReviewController::class)->prefix('reviews')->name('reviews.')->group(function () {
            Route::middleware('perm:feedback.view')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{review}', 'show')->whereNumber('review')->name('show');
            });
            Route::middleware('perm:feedback.manage')->group(function () {
                Route::post('/', 'store')->name('store');
                Route::post('/import', 'import')->name('import');
            Route::post('/pull', 'pull')->name('pull');
            });
            Route::middleware('perm:feedback.respond')->group(function () {
                Route::post('/{review}/draft', 'draft')->name('draft');
                Route::post('/{review}/submit', 'submit')->name('submit');
                Route::post('/{review}/posted', 'posted')->name('posted');
                Route::post('/{review}/ignore', 'ignore')->name('ignore');
                Route::post('/{review}/restore', 'restore')->name('restore');
                Route::post('/{review}/link', 'link')->name('link');
            });
            Route::middleware('perm:feedback.approve')->group(function () {
                Route::post('/{review}/approve', 'approve')->name('approve');
                Route::post('/{review}/return', 'returnDraft')->name('return');
            });
        });
        Route::controller(Controllers\SurveyController::class)->group(function () {
            Route::middleware('perm:feedback.view')->group(function () {
                Route::get('/surveys', 'index')->name('surveys.index');
                Route::get('/surveys/{survey}/preview', 'preview')->name('surveys.preview');
                Route::get('/invitations', 'invitations')->name('invitations');
            });
            Route::middleware('perm:feedback.manage')->group(function () {
                Route::post('/surveys', 'store')->name('surveys.store');
                Route::get('/surveys/{survey}/edit', 'edit')->name('surveys.edit');
                Route::put('/surveys/{survey}', 'update')->name('surveys.update');
                Route::post('/invitations', 'invite')->name('invitations.store');
                Route::post('/invitations/{invitation}/send', 'sendNow')->name('invitations.send');
            });
        });
        Route::controller(Controllers\CaseController::class)->prefix('cases')->name('cases.')->middleware('perm:feedback.cases')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('/from-feedback/{feedback}', 'fromFeedback')->name('from_feedback');
            Route::post('/from-review/{review}', 'fromReview')->name('from_review');
            Route::get('/{fcase}', 'show')->whereNumber('fcase')->name('show');
            Route::post('/{fcase}/assign', 'assign')->name('assign');
            Route::post('/{fcase}/start', 'start')->name('start');
            Route::post('/{fcase}/note', 'note')->name('note');
            Route::post('/{fcase}/contact', 'contact')->name('contact');
            Route::post('/{fcase}/resolve', 'resolve')->name('resolve');
            Route::post('/{fcase}/close', 'close')->name('close');
            Route::post('/{fcase}/reopen', 'reopen')->name('reopen');
        });
    });

    // ---- Offline-first workspace and authenticated browser outbox
    Route::prefix('offline')->name('offline.')->controller(Controllers\OfflineSyncController::class)->middleware('throttle:120,1')->group(function () {
        // The hub is what the service worker falls back to, so it carries no permission of its own.
        Route::get('/', 'hub')->name('hub');
        // Which screens this login may keep on this device, so preparation is one press.
        Route::get('/preparation', 'preparation')->name('preparation');
        // The outbox itself: everything this device still holds, and what a person must decide.
        Route::get('/queue', 'queue')->name('queue');
        // After an outage: what the devices sent, what it created and what needs a person.
        Route::get('/reconciliation', 'reconciliation')
            ->middleware('perm:offline.desktop.view,frontdesk.view,housekeeping.manage')->name('reconciliation');
        // v7.78 offline ordering: menu, tables and running orders on the device, orders queued and replayed.
        Route::get('/pos', 'posWorkspace')->middleware('perm:pos.order,pos.view')->name('pos');
        Route::get('/pos/snapshot', 'posSnapshot')->middleware('perm:pos.order,pos.view')->name('pos.snapshot');
        Route::get('/housekeeping', 'workspace')->middleware('perm:housekeeping.view,housekeeping.attendant,housekeeping.manage')->name('housekeeping');
        Route::get('/housekeeping/snapshot', 'housekeepingSnapshot')->middleware('perm:housekeeping.view,housekeeping.attendant,housekeeping.manage')->name('housekeeping.snapshot');
        Route::get('/guest-requests', 'guestRequests')->middleware('perm:requests.view,requests.manage')->name('guest_requests');
        // Offline front desk: check a reserved guest in and post folio charges; payments stay online.
        Route::get('/frontdesk', 'frontDeskWorkspace')->middleware('perm:frontdesk.view')->name('frontdesk');
        Route::get('/frontdesk/snapshot', 'frontDeskSnapshot')->middleware('perm:frontdesk.view')->name('frontdesk.snapshot');
        Route::get('/guest-requests/snapshot', 'guestRequestSnapshot')->middleware('perm:requests.view,requests.manage')->name('guest_requests.snapshot');
        // One outbox for every offline slice: any of the screens below may push, and the
        // service checks the permission for each operation again.
        Route::post('/sync', 'sync')->middleware('perm:housekeeping.manage,housekeeping.attendant,requests.manage,pos.order,pos.view,frontdesk.view,frontdesk.checkin,folio.post')->name('sync');
        // v7.39 desktop offline mode — multi-day cache-and-sync
        Route::prefix('desktop')->name('desktop.')->controller(Controllers\DesktopSyncController::class)->group(function () {
            Route::get('/', 'workspace')->middleware('perm:offline.desktop.view')->name('workspace');
            Route::get('/pack', 'pack')->middleware('perm:offline.desktop.view')->name('pack');
            Route::get('/delta', 'delta')->middleware('perm:offline.desktop.view')->name('delta');
            Route::post('/sync', 'sync')->middleware('perm:offline.desktop.sync')->name('sync');
        });
    });

    // ---- Housekeeping / Maintenance / Lost & Found
    Route::prefix('housekeeping')->name('housekeeping.')->controller(Controllers\HousekeepingController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:housekeeping.view')->name('index');
        Route::post('/status', 'updateStatus')->middleware('perm:housekeeping.manage')->name('status');
        Route::get('/tasks', 'tasks')->middleware('perm:housekeeping.view')->name('tasks');
        Route::post('/tasks', 'storeTask')->middleware('perm:housekeeping.manage')->name('tasks.store');
        Route::post('/tasks/generate', 'generate')->middleware('perm:housekeeping.manage')->name('tasks.generate');
        Route::post('/tasks/{task}', 'updateTask')->middleware('perm:housekeeping.manage')->name('tasks.update');
    });
    // Room-attendant mobile screen + supervisor inspection
    Route::prefix('hk')->name('hk.')->controller(Controllers\HkMobileController::class)->group(function () {
        Route::get('/my', 'my')->middleware('perm:housekeeping.attendant,housekeeping.manage')->name('my');
        foreach (['start', 'dnd', 'complete', 'minibar', 'issue', 'found'] as $a) {
            Route::post("/tasks/{task}/{$a}", $a)->middleware('perm:housekeeping.attendant,housekeeping.manage')->name($a);
        }
        Route::post('/tasks/{task}/inspect', 'inspect')->middleware('perm:housekeeping.manage')->name('inspect');
    });

    // ---- Spa & activities
    Route::prefix('spa')->name('spa.')->controller(Controllers\SpaController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:spa.view')->name('index');
        Route::post('/', 'store')->middleware('perm:spa.manage')->name('store');
        Route::put('/{appt}', 'update')->middleware('perm:spa.manage')->name('update');
        Route::post('/{appt}/status', 'status')->middleware('perm:spa.manage')->name('status');
        Route::post('/{appt}/settle', 'settle')->middleware('perm:spa.manage')->name('settle');
    });

    Route::middleware('perm:maintenance.manage')->group(function () {
        Route::get('/maintenance', [Controllers\MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('/maintenance', [Controllers\MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::post('/maintenance/{ticket}', [Controllers\MaintenanceController::class, 'update'])->name('maintenance.update');
    });
    // ---- v3.5 lost & found (full lifecycle)
    Route::prefix('lost-found')->name('lostfound.')->controller(Controllers\LostFoundController::class)->middleware('perm:lostfound.manage')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{item}', 'show')->whereNumber('item')->name('show');
        Route::put('/{item}', 'update')->whereNumber('item')->name('update');
        Route::get('/{item}/tag', 'tag')->whereNumber('item')->name('tag');
        Route::get('/{item}/receipt', 'receipt')->whereNumber('item')->name('receipt');
        Route::post('/{item}/return', 'returnItem')->whereNumber('item')->name('return');
        Route::post('/{item}/dispose', 'dispose')->whereNumber('item')->name('dispose');
        Route::post('/reports', 'report')->name('report');
        Route::get('/reports/{lreport}', 'showReport')->whereNumber('lreport')->name('report.show');
        Route::post('/reports/{lreport}/match/{item}', 'match')->whereNumber('lreport')->whereNumber('item')->name('report.match');
        Route::post('/reports/{lreport}/close', 'closeReport')->whereNumber('lreport')->name('report.close');
    });
