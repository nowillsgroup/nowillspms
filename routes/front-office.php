<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

    Route::controller(Controllers\Auth\TwoFactorController::class)->prefix('profile/two-factor')->name('profile.2fa')->group(function () {
        Route::get('/', 'show');
        Route::post('/', 'enable')->middleware('throttle:10,1')->name('.enable');
        Route::post('/recovery-codes', 'recovery')->name('.recovery');
        Route::post('/disable', 'disable')->name('.disable');
    });
    Route::post('/switch-hotel', [LoginController::class, 'switchHotel'])->name('switch.hotel');
    Route::get('/profile', [Controllers\UserController::class, 'profile'])->name('profile');
    Route::post('/profile', [Controllers\UserController::class, 'updateProfile'])->name('profile.update');

    Route::get('/Dashboard', [Controllers\DashboardController::class, 'index'])->middleware('perm:dashboard.view')->name('dashboard');
    Route::get('/search', [Controllers\DashboardController::class, 'search'])->name('search');
    Route::get('/search/suggest', [Controllers\DashboardController::class, 'suggest'])->name('search.suggest');

    // v7.55 notification centre — the bell in the top bar. Derived from live data,
    // so there is nothing to mark read: fix the item and it disappears.
    Route::get('/notifications', [Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/group-dashboard', [Controllers\GroupDashboardController::class, 'index'])->middleware('perm:group.dashboard')->name('group.dashboard');

    /* ---- v10.0 Multi-tenant & multi-property management
       The register and the property page are readable by anyone with properties.view inside the
       properties they can reach; every write is gated by properties.manage *and* by the per-property
       check in the controller, so a property administrator can never edit a sibling property. */
    Route::prefix('properties')->name('properties.')->controller(Controllers\PropertyController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:properties.view')->name('index');
        // v10.10 the register as a file: same rows, same filters, same licence coverage as the screen
        Route::get('/export', 'export')->middleware('perm:properties.view')->name('export');
        Route::get('/new', 'create')->middleware('perm:properties.manage')->name('create');
        Route::post('/', 'store')->middleware('perm:properties.manage')->name('store');
        Route::get('/{hotel}', 'show')->whereNumber('hotel')->middleware('perm:properties.view')->name('show');
        Route::get('/{hotel}/edit', 'edit')->whereNumber('hotel')->middleware('perm:properties.manage')->name('edit');
        Route::put('/{hotel}', 'update')->whereNumber('hotel')->middleware('perm:properties.manage')->name('update');
        Route::post('/{hotel}/activate', 'activate')->whereNumber('hotel')->middleware('perm:properties.manage')->name('activate');
        Route::post('/{hotel}/suspend', 'suspend')->whereNumber('hotel')->middleware('perm:properties.manage')->name('suspend');
        Route::post('/{hotel}/resume', 'resume')->whereNumber('hotel')->middleware('perm:properties.manage')->name('resume');
        Route::post('/{hotel}/archive', 'archive')->whereNumber('hotel')->middleware('perm:properties.manage')->name('archive');
        Route::post('/{hotel}/transfer', 'transfer')->whereNumber('hotel')->middleware('perm:properties.manage')->name('transfer');
        // v10.5 narrow the licence at one property (group administrator or platform administrator)
        Route::post('/{hotel}/licence', 'licence')->whereNumber('hotel')->middleware('perm:properties.manage')->name('licence');
        Route::post('/{hotel}/access', 'grant')->whereNumber('hotel')->middleware('perm:properties.manage')->name('access.grant');
        Route::delete('/{hotel}/access/{user}', 'revoke')->whereNumber('hotel')->whereNumber('user')->middleware('perm:properties.manage')->name('access.revoke');
    });

    /* The group account itself: identity, inheritance defaults, entitlement, tenant suspension. */
    Route::prefix('group')->name('group.')->controller(Controllers\HotelGroupController::class)->group(function () {
        Route::get('/', 'edit')->middleware('perm:properties.manage')->name('account');
        Route::post('/', 'update')->middleware('perm:properties.manage')->name('account.update');
        Route::post('/licence', 'licence')->middleware('perm:properties.manage')->name('licence');
        Route::post('/suspend', 'suspend')->middleware('perm:properties.manage')->name('suspend');
        Route::post('/resume', 'resume')->middleware('perm:properties.manage')->name('resume');
    });

    /* v10.1 the tenant's people: created once at group level, with a home property and any number of
       additional grants. Every write is gated by properties.manage and by the per-group check in the
       controller, so a group administrator can staff their own tenant and no other. */
    Route::prefix('group/people')->name('group.people.')->controller(Controllers\GroupPeopleController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:properties.view')->name('index');
        Route::post('/', 'store')->middleware('perm:properties.manage')->name('store');
        Route::put('/{user}', 'update')->whereNumber('user')->middleware('perm:properties.manage')->name('update');
        Route::post('/{user}/toggle', 'toggle')->whereNumber('user')->middleware('perm:properties.manage')->name('toggle');
    });

    // ---- Rate management (seasonal pricing, weekend rates, min-stay, stop-sell)
    Route::prefix('rates')->name('rates.')->middleware('perm:reservations.rates')->controller(Controllers\RateController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::delete('/{override}', 'destroy')->name('destroy');
    });

    // ---- v1.9 revenue management (forecast, pickup, dynamic pricing)
    // v4.2 security & gate management
    Route::prefix('security')->name('security.')->group(function () {
        Route::controller(Controllers\SecurityController::class)->group(function () {
            Route::get('/visitors', 'index')->middleware('perm:security.view')->name('visitors');
            Route::middleware('perm:security.visitors')->group(function () {
                Route::post('/visitors', 'store')->name('visitors.store');
                Route::post('/visitors/{visitor}/approve', 'approve')->name('visitors.approve');
                Route::post('/visitors/{visitor}/deny', 'deny')->name('visitors.deny');
                Route::post('/visitors/{visitor}/arrive', 'arrive')->name('visitors.arrive');
                Route::post('/visitors/{visitor}/out', 'checkOut')->name('visitors.out');
                Route::post('/visitors/{visitor}/cancel', 'cancel')->name('visitors.cancel');
                Route::post('/visitors/{visitor}/extend', 'extend')->name('visitors.extend');
                Route::get('/visitors/{visitor}/badge', 'badge')->name('visitors.badge');
                Route::post('/stay/{stay}/visitor-policy', 'policy')->name('policy');
            });
            Route::middleware('perm:security.manage')->group(function () {
                Route::post('/watchlist', 'storeWatch')->name('watch.store');
                Route::post('/watchlist/{watch}', 'updateWatch')->name('watch.update');
                Route::post('/watchlist/{watch}/toggle', 'toggleWatch')->name('watch.toggle');
                Route::post('/settings', 'settings')->name('settings');
                Route::post('/run', 'run')->middleware('throttle:6,1')->name('run');
            });
        });
        Route::controller(Controllers\GatePassController::class)->prefix('gate-passes')->name('gp.')->group(function () {
            Route::get('/', 'index')->middleware('perm:security.view,security.gatepass')->name('index');
            Route::get('/new', 'create')->middleware('perm:security.gatepass')->name('create');
            Route::post('/', 'store')->middleware('perm:security.gatepass')->name('store');
            Route::get('/{gatepass}', 'show')->middleware('perm:security.view,security.gatepass')->name('show');
            Route::get('/{gatepass}/print', 'print')->middleware('perm:security.view,security.gatepass')->name('print');
            Route::post('/{gatepass}/cancel', 'cancel')->middleware('perm:security.gatepass,security.approve')->name('cancel');
            Route::middleware('perm:security.approve')->group(function () {
                Route::post('/{gatepass}/approve', 'approve')->name('approve');
                Route::post('/{gatepass}/reject', 'reject')->name('reject');
                Route::post('/{gatepass}/close', 'close')->name('close');
                Route::post('/{gatepass}/extend', 'extend')->name('extend');
            });
            Route::middleware('perm:security.gate')->group(function () {
                Route::post('/{gatepass}/out', 'out')->name('out');
                Route::post('/{gatepass}/receive', 'receive')->name('receive');
            });
        });
        Route::controller(Controllers\IncidentController::class)->prefix('incidents')->group(function () {
            Route::get('/', 'index')->middleware('perm:security.view,security.incidents')->name('incidents');
            Route::post('/', 'store')->middleware('perm:security.incidents')->name('incidents.store');
            Route::get('/{incident}', 'show')->middleware('perm:security.view,security.incidents')->name('incidents.show');
            Route::middleware('perm:security.incidents')->group(function () {
                Route::post('/{incident}', 'update')->name('incidents.update');
                Route::post('/{incident}/note', 'note')->name('incidents.note');
            });
            Route::middleware('perm:security.manage')->group(function () {
                Route::post('/{incident}/close', 'close')->name('incidents.close');
                Route::post('/{incident}/reopen', 'reopen')->name('incidents.reopen');
            });
        });
    });

    // v4.1 upselling & pre-arrival offers
    Route::prefix('upselling')->name('upsell.')->controller(Controllers\UpsellController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:upsell.view')->name('index');
        Route::middleware('perm:upsell.sell')->group(function () {
            Route::post('/stay/{stay}/sell', 'sell')->name('sell');
            Route::post('/orders/{uorder}/confirm', 'confirm')->name('confirm');
            Route::post('/orders/{uorder}/decline', 'decline')->name('decline');
            Route::post('/orders/{uorder}/cancel', 'cancel')->name('cancel');
            Route::post('/reservations/{reservation}/send', 'sendOffers')->middleware('throttle:10,1')->name('send');
        });
        Route::middleware('perm:upsell.manage')->group(function () {
            Route::post('/offers', 'storeOffer')->name('offers.store');
            Route::post('/offers/{uoffer}', 'updateOffer')->name('offers.update');
            Route::post('/offers/{uoffer}/toggle', 'toggleOffer')->name('offers.toggle');
            Route::post('/offers/{uoffer}/delete', 'destroyOffer')->name('offers.delete');
            Route::post('/run', 'run')->middleware('throttle:6,1')->name('run');
            Route::post('/settings', 'settings')->name('settings');
        });
    });
    Route::prefix('revenue')->name('revenue.')->controller(Controllers\RevenueController::class)->group(function () {
        Route::middleware('perm:revenue.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/pricing', 'pricing')->name('pricing');
            Route::get('/rules', 'rules')->name('rules');
        });
        Route::middleware('perm:revenue.manage')->group(function () {
            Route::post('/pricing/publish', 'publish')->name('publish');
            Route::post('/pricing/reset', 'reset')->name('reset');
            Route::post('/settings', 'settings')->name('settings');
            Route::post('/limits', 'limits')->name('limits');
            Route::get('/rules/create', 'createRule')->name('rules.create');
            Route::post('/rules', 'storeRule')->name('rules.store');
            Route::get('/rules/{yrule}/edit', 'editRule')->name('rules.edit');
            Route::put('/rules/{yrule}', 'updateRule')->name('rules.update');
            Route::post('/rules/{yrule}/toggle', 'toggleRule')->name('rules.toggle');
            Route::delete('/rules/{yrule}', 'destroyRule')->name('rules.destroy');
            Route::post('/snapshot', 'snapshot')->name('snapshot');
        });
    });

    // ---- Kitchen display
    Route::middleware('perm:pos.kitchen')->controller(Controllers\KitchenController::class)->prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/feed', 'feed')->name('feed');
        Route::post('/bump', 'bump')->name('bump');
        Route::get('/station', 'station')->name('station'); // v7.6 one PC, one kitchen printer
        Route::get('/station/jobs', 'stationJobs')->name('station.jobs');
        Route::post('/station/claim', 'stationClaim')->name('station.claim');
        Route::post('/station/ack', 'stationAck')->name('station.ack');
    });

    // ---- Group room blocks (allotments)
    Route::prefix('blocks')->name('blocks.')->controller(Controllers\BlockController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:reservations.view')->name('index');
        Route::get('/create', 'create')->middleware('perm:reservations.create')->name('create');
        Route::post('/', 'store')->middleware('perm:reservations.create')->name('store');
        Route::get('/{block}', 'show')->middleware('perm:reservations.view')->name('show');
        Route::get('/{block}/edit', 'edit')->middleware('perm:reservations.edit')->name('edit');
        Route::put('/{block}', 'update')->middleware('perm:reservations.edit')->name('update');
        Route::post('/{block}/pickup-preview', 'previewPickup')->middleware('perm:reservations.view')->name('pickup.preview');
        Route::post('/{block}/pickup', 'pickup')->middleware('perm:reservations.create')->name('pickup');
        Route::post('/{block}/status', 'status')->middleware('perm:reservations.edit')->name('status');
    });

    // ---- v2.8 two-way channel manager (OTA connectivity)
    Route::prefix('channel-manager')->name('cm.')->group(function () {
        Route::controller(Controllers\ChannelManagerController::class)->group(function () {
            Route::middleware('perm:channels.view')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/inbox', 'inbox')->name('inbox');
                Route::get('/inbox/{chmsg}', 'message')->name('message');
                Route::get('/sync-log', 'pushes')->name('pushes');
                Route::get('/parity', 'parity')->name('parity');
            });
            Route::post('/inbox/{chmsg}/resolve', 'resolve')->middleware('perm:channels.resolve')->name('resolve');
            Route::post('/reservations/{reservation}/resolve', 'resolveReservation')->middleware('perm:channels.resolve')->name('reservation.resolve');
            Route::post('/inbox/{chmsg}/reprocess', 'reprocess')->middleware('perm:channels.manage')->name('reprocess');
        });
        Route::get('/connections/{chconn}', [Controllers\ChannelConnectionController::class, 'show'])->middleware('perm:channels.view')->whereNumber('chconn')->name('connections.show');
        Route::prefix('connections')->name('connections.')->middleware('perm:channels.manage')->controller(Controllers\ChannelConnectionController::class)->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{chconn}/edit', 'edit')->name('edit');
            Route::put('/{chconn}', 'update')->name('update');
            Route::delete('/{chconn}', 'destroy')->name('destroy');
            Route::post('/{chconn}/mapping', 'mapping')->name('mapping');
            Route::post('/{chconn}/test', 'test')->name('test');
            Route::post('/{chconn}/restrictions', 'restrict')->name('restrict');
            Route::delete('/{chconn}/restrictions/{chrestr}', 'unrestrict')->name('unrestrict');
            Route::post('/{chconn}/push', 'push')->name('push');
            Route::post('/{chconn}/rotate', 'rotate')->name('rotate');
            Route::post('/{chconn}/simulate', 'simulate')->name('simulate');
            Route::post('/{chconn}/pull', 'pull')->name('pull');
        });
    });

    // ---- v2.9 guest data privacy & compliance (DPDP / GDPR)
    Route::post('/guests/{guest}/reveal', [Controllers\PrivacyController::class, 'reveal'])->middleware(['perm:pii.reveal', 'throttle:30,1'])->name('guests.reveal');
    Route::prefix('privacy')->name('privacy.')->group(function () {
        Route::controller(Controllers\PrivacyController::class)->group(function () {
            Route::middleware('perm:privacy.view')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/access-log', 'accessLog')->name('access');
                Route::get('/retention', 'retention')->name('retention');
            });
            Route::get('/guests/{guest}/export', 'guestExport')->middleware('perm:privacy.handle')->name('guests.export');
            Route::middleware('perm:privacy.erase')->group(function () {
                Route::post('/retention/settings', 'saveSettings')->name('settings');
                Route::post('/retention/run', 'runRetention')->name('retention.run');
            });
        });
        Route::controller(Controllers\PrivacyRequestController::class)->prefix('requests')->name('requests.')->group(function () {
            Route::middleware('perm:privacy.handle')->group(function () {
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::post('/{preq}/verify', 'verify')->name('verify');
                Route::post('/{preq}/link', 'link')->name('link');
                Route::post('/{preq}/note', 'note')->name('note');
                Route::post('/{preq}/assign', 'assign')->name('assign');
                Route::post('/{preq}/extend', 'extend')->name('extend');
                Route::post('/{preq}/consent', 'consent')->name('consent');
                Route::post('/{preq}/complete', 'complete')->name('complete');
                Route::post('/{preq}/reject', 'reject')->name('reject');
                Route::get('/{preq}/export', 'export')->name('export');
                Route::get('/{preq}/report', 'report')->name('report');
            });
            Route::get('/{preq}', 'show')->middleware('perm:privacy.view')->whereNumber('preq')->name('show');
            Route::post('/{preq}/erase', 'erase')->middleware('perm:privacy.erase')->name('erase');
        });
        Route::controller(Controllers\PrivacyBreachController::class)->prefix('breaches')->name('breaches.')->group(function () {
            Route::middleware('perm:privacy.view')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{breach}', 'show')->whereNumber('breach')->name('show');
            });
            Route::middleware('perm:privacy.breach')->group(function () {
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{breach}/edit', 'edit')->name('edit');
                Route::put('/{breach}', 'update')->name('update');
                Route::post('/{breach}/contain', 'contain')->name('contain');
                Route::post('/{breach}/notify', 'notify')->name('notify');
                Route::post('/{breach}/subjects', 'subjects')->name('subjects');
                Route::post('/{breach}/close', 'close')->name('close');
            });
        });
    });

    // ---- Channel manager: iCal feeds (v1.2)
    Route::prefix('channels')->name('channels.')->middleware('perm:channels.manage')->controller(Controllers\ChannelController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{channel}', 'update')->name('update');
        Route::delete('/{channel}', 'destroy')->name('destroy');
        Route::post('/{channel}/sync', 'sync')->name('sync');
        Route::post('/{channel}/regenerate', 'regenerate')->name('regenerate');
    });

    // ---- Recipes (F&B costing & automatic stock consumption)
    Route::prefix('recipes')->name('recipes.')->middleware('perm:inventory.manage')->controller(Controllers\RecipeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{menuItem}', 'edit')->name('edit');
        Route::put('/{menuItem}', 'update')->name('update');
    });

    // ---- Cashiering
    // ---- Online payments (v2.1)
    Route::prefix('payments')->name('payments.')->controller(Controllers\OnlinePaymentController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:payments.view')->name('index');
        Route::post('/', 'store')->middleware('perm:payments.request')->name('store');
        Route::post('/collect', 'collect')->middleware('perm:payments.request')->name('collect');
        Route::get('/payouts', 'payouts')->middleware('perm:payments.settle')->name('payouts');
        Route::post('/payouts', 'storePayout')->middleware('perm:payments.settle')->name('payouts.store');
        Route::post('/payouts/{gpo}/delete', 'destroyPayout')->middleware('perm:payments.settle')->whereNumber('gpo')->name('payouts.destroy');
        Route::get('/settings', 'settings')->middleware('perm:payments.settings')->name('settings');
        Route::post('/settings', 'saveSettings')->middleware('perm:payments.settings')->name('settings.save');
        Route::get('/{olp}', 'show')->middleware('perm:payments.view')->whereNumber('olp')->name('show');
        Route::post('/{olp}/send', 'send')->middleware('perm:payments.request')->whereNumber('olp')->name('send');
        Route::post('/{olp}/cancel', 'cancel')->middleware('perm:payments.request')->whereNumber('olp')->name('cancel');
        Route::post('/{olp}/check', 'check')->middleware('perm:payments.view')->whereNumber('olp')->name('check');
        Route::post('/{olp}/refund', 'refund')->middleware('perm:payments.refund')->whereNumber('olp')->name('refund');
        Route::post('/{olp}/accept', 'accept')->middleware('perm:payments.refund')->whereNumber('olp')->name('accept');
    });
    // ---- Laundry & linen (v2.2)
    // v2.4 store requisitions (indents), issue notes, department budgets & sub-store pars
    Route::prefix('requisitions')->name('req.')->controller(Controllers\RequisitionController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:requisitions.view')->name('index');
        Route::get('/create', 'create')->middleware('perm:requisitions.raise')->name('create');
        Route::post('/', 'store')->middleware('perm:requisitions.raise')->name('store');
        Route::get('/setup', 'setup')->middleware('perm:requisitions.view')->name('setup');
        Route::put('/setup/departments/{rdept}', 'updateDepartment')->middleware('perm:requisitions.manage')->name('dept.update');
        Route::post('/setup/settings', 'saveSettings')->middleware('perm:requisitions.manage')->name('settings');
        Route::get('/pars', 'pars')->middleware('perm:requisitions.view')->name('pars');
        Route::post('/pars', 'savePars')->middleware('perm:requisitions.manage')->name('pars.save');
        Route::post('/issues/{sissue}/acknowledge', 'acknowledge')->middleware('perm:requisitions.raise')->name('ack');
        Route::get('/issues/{sissue}/print', 'printIssue')->middleware('perm:requisitions.view')->name('issue.print');
        Route::get('/{req}', 'show')->middleware('perm:requisitions.view')->name('show');
        Route::get('/{req}/edit', 'edit')->middleware('perm:requisitions.raise')->name('edit');
        Route::put('/{req}', 'update')->middleware('perm:requisitions.raise')->name('update');
        Route::get('/{req}/print', 'print')->middleware('perm:requisitions.view')->name('print');
        Route::post('/{req}/submit', 'submit')->middleware('perm:requisitions.raise')->name('submit');
        Route::post('/{req}/approve', 'approve')->middleware('perm:requisitions.approve')->name('approve');
        Route::post('/{req}/reject', 'reject')->middleware('perm:requisitions.approve')->name('reject');
        Route::post('/{req}/issue', 'issue')->middleware('perm:requisitions.issue')->name('issue');
        Route::post('/{req}/close', 'close')->middleware('perm:requisitions.issue')->name('close');
        Route::post('/{req}/cancel', 'cancel')->middleware('perm:requisitions.raise')->name('cancel');
        Route::post('/{req}/repeat', 'repeat')->middleware('perm:requisitions.raise')->name('repeat');
        Route::post('/{req}/purchase', 'purchase')->middleware('perm:inventory.manage')->name('purchase');
    });

    // v2.3 fixed assets & preventive maintenance
    Route::prefix('assets')->group(function () {
        Route::middleware('perm:assets.view')->controller(Controllers\AssetController::class)->group(function () {
            Route::get('/', 'index')->name('assets.index');
            Route::get('/export', 'export')->name('assets.export');
            Route::get('/tags', 'tags')->name('assets.tags');
            Route::get('/categories', 'categories')->name('assets.categories');
        });
        Route::middleware('perm:assets.manage')->controller(Controllers\AssetController::class)->group(function () {
            Route::get('/create', 'create')->name('assets.create');
            Route::post('/', 'store')->name('assets.store');
            Route::post('/categories', 'saveCategory')->name('assets.categories.store');
            Route::put('/categories/{acat}', 'saveCategory')->name('assets.categories.update');
            Route::get('/{asset}/edit', 'edit')->name('assets.edit');
            Route::put('/{asset}', 'update')->name('assets.update');
            Route::post('/{asset}/status', 'status')->name('assets.status');
            Route::post('/{asset}/dispose', 'dispose')->name('assets.dispose');
            Route::delete('/{asset}', 'destroy')->name('assets.destroy');
        });
        Route::controller(Controllers\AssetDepreciationController::class)->group(function () {
            Route::get('/depreciation', 'index')->middleware('perm:assets.view')->name('assets.depreciation');
            Route::post('/depreciation', 'run')->middleware('perm:assets.depreciation')->name('assets.depreciation.run');
            Route::get('/depreciation/{deprun}', 'show')->middleware('perm:assets.view')->name('assets.depreciation.show');
            Route::post('/depreciation/{deprun}/reverse', 'reverse')->middleware('perm:assets.depreciation')->name('assets.depreciation.reverse');
        });
        Route::get('/{asset}', [Controllers\AssetController::class, 'show'])->middleware('perm:assets.view')->name('assets.show');
    });
    Route::prefix('pm-schedules')->name('pm.')->middleware('perm:maintenance.manage')->controller(Controllers\PmScheduleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/generate', 'generate')->name('generate');
        Route::put('/{pmsched}', 'update')->name('update');
        Route::post('/{pmsched}/toggle', 'toggle')->name('toggle');
        Route::delete('/{pmsched}', 'destroy')->name('destroy');
    });

    Route::prefix('laundry')->name('laundry.')->controller(Controllers\LaundryController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:laundry.view')->name('index');
        Route::get('/create', 'create')->middleware('perm:laundry.orders')->name('create');
        Route::post('/', 'store')->middleware('perm:laundry.orders')->name('store');
        Route::get('/prices', 'prices')->middleware('perm:laundry.view')->name('prices');
        Route::post('/prices', 'savePrice')->middleware('perm:laundry.manage')->name('prices.save');
        Route::post('/prices/{litem}/toggle', 'togglePrice')->middleware('perm:laundry.manage')->whereNumber('litem')->name('prices.toggle');
        Route::post('/settings', 'saveSettings')->middleware('perm:laundry.manage')->name('settings.save');
        Route::get('/{lorder}', 'show')->middleware('perm:laundry.view')->whereNumber('lorder')->name('show');
        Route::get('/{lorder}/docket', 'docket')->middleware('perm:laundry.view')->whereNumber('lorder')->name('docket');
        Route::post('/{lorder}/items', 'items')->middleware('perm:laundry.orders')->whereNumber('lorder')->name('items');
        Route::post('/{lorder}/ready', 'ready')->middleware('perm:laundry.orders')->whereNumber('lorder')->name('ready');
        Route::post('/{lorder}/deliver', 'deliver')->middleware('perm:laundry.orders')->whereNumber('lorder')->name('deliver');
        Route::post('/{lorder}/cancel', 'cancel')->middleware('perm:laundry.orders')->whereNumber('lorder')->name('cancel');
    });
    Route::prefix('linen')->name('linen.')->controller(Controllers\LinenController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:laundry.view')->name('index');
        Route::post('/move', 'move')->middleware('perm:laundry.linen')->name('move');
        Route::post('/count', 'count')->middleware('perm:laundry.linen')->name('count');
        Route::post('/types', 'saveType')->middleware('perm:laundry.manage')->name('types.save');
        Route::post('/levels', 'saveLevels')->middleware('perm:laundry.manage')->name('levels.save');
        Route::get('/batches', 'batches')->middleware('perm:laundry.view')->name('batches');
        Route::post('/batches', 'send')->middleware('perm:laundry.linen')->name('send');
        Route::get('/batches/{lbatch}', 'batch')->middleware('perm:laundry.view')->whereNumber('lbatch')->name('batch');
        Route::post('/batches/{lbatch}/receive', 'receive')->middleware('perm:laundry.linen')->whereNumber('lbatch')->name('receive');
        Route::post('/batches/{lbatch}/close', 'close')->middleware('perm:laundry.linen')->whereNumber('lbatch')->name('close');
        Route::post('/batches/{lbatch}/bill', 'bill')->middleware('perm:ap.manage')->whereNumber('lbatch')->name('bill');
    });
    Route::get('/my-shift', [Controllers\MyShiftController::class, 'index'])->middleware('perm:logbook.view,folio.shift,pos.order,pos.shift_view_all')->name('my-shift');
    Route::prefix('service-shifts')->name('department-shifts.')->middleware('perm:pos.order,pos.shift_view_all')->controller(Controllers\DepartmentShiftController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'start')->middleware('perm:pos.order')->name('start');
        Route::get('/day/print', 'printDay')->name('day-print');
        Route::get('/{session}', 'show')->whereNumber('session')->name('show');
        Route::get('/{session}/print', 'print')->whereNumber('session')->name('print');
        Route::post('/{session}/close', 'close')->whereNumber('session')->middleware('perm:pos.order')->name('close');
    });
    Route::prefix('shifts')->name('shifts.')->middleware('perm:folio.shift')->controller(Controllers\ShiftController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'open')->name('open');
        Route::post('/cash-in', 'cashIn')->name('cash-in');
        Route::post('/cash-out', 'cashOut')->name('cash-out');
        Route::get('/{shift}', 'show')->name('show');
        Route::post('/{shift}/close', 'close')->name('close');
    });

    // ---- Loyalty
    Route::get('/loyalty', [Controllers\LoyaltyController::class, 'index'])->middleware('perm:guests.view')->name('loyalty.index');
    Route::post('/loyalty/adjust/{guest}', [Controllers\LoyaltyController::class, 'adjust'])->middleware('perm:guests.manage')->name('loyalty.adjust');
    Route::post('/folios/{folio}/redeem', [Controllers\LoyaltyController::class, 'redeem'])->middleware('perm:folio.loyalty')->name('folios.redeem');

    // ---- Front office
    Route::prefix('front-desk')->name('frontdesk.')->controller(Controllers\FrontDeskController::class)->group(function () {
        Route::middleware('perm:frontdesk.view')->group(function () {
            Route::get('/rooms', 'rooms')->name('rooms');
            Route::get('/tape-chart', 'tapeChart')->name('tapechart');
            Route::get('/arrivals', 'arrivals')->name('arrivals');
            Route::get('/in-house', 'inHouse')->name('inhouse');
            Route::get('/departures', 'departures')->name('departures');
            Route::get('/stay/{stay}/grc', 'grc')->name('grc');
        });
        Route::controller(Controllers\GrcController::class)->group(function () {
            Route::get('/stay/{stay}/grc/sign', 'sign')->middleware('perm:frontdesk.checkin')->name('grc.sign');
            Route::post('/stay/{stay}/grc/sign', 'saveSignature')->middleware('perm:frontdesk.checkin')->name('grc.signature.store');
            Route::get('/stay/{stay}/grc/signature.png', 'signature')->middleware('perm:frontdesk.view')->name('grc.signature');
        });
        Route::middleware('perm:frontdesk.checkin')->group(function () {
            Route::get('/walk-in', 'walkIn')->name('walkin');
            Route::post('/walk-in', 'storeWalkIn')->name('walkin.store');
            Route::post('/walk-in/company', 'storeWalkInCompany')->middleware('perm:credit.manage')->name('walkin.company.store');
            Route::get('/stay/{stay}/check-in', 'checkInForm')->name('checkin');
            Route::post('/stay/{stay}/check-in', 'checkIn')->name('checkin.store');
        });
        Route::middleware('perm:frontdesk.checkout')->group(function () {
            Route::get('/stay/{stay}/check-out', 'checkOutForm')->name('checkout');
            Route::post('/stay/{stay}/check-out', 'checkOut')->name('checkout.store');
            Route::post('/stay/{stay}/prepare-bill', 'prepareBill')->name('prepare');
        });
        Route::middleware('perm:frontdesk.roommove')->group(function () {
            Route::get('/stay/{stay}/move', 'moveForm')->name('move');
            Route::post('/stay/{stay}/move', 'move')->name('move.store');
            Route::post('/stay/{stay}/amend', 'amend')->name('amend');
        });
        // The desk also exposes safe placement to reservation editors; in-house
        // room changes still pass through StayService's audited move workflow.
        Route::post('/tape-chart/drop', 'tapeDrop')->middleware('perm:frontdesk.roommove,reservations.edit')->name('tapechart.drop');
    });

    // ---- Guest requests queue & guest portal links (v1.7)
    Route::prefix('guest-requests')->name('requests.')->controller(Controllers\GuestRequestController::class)->group(function () {
        Route::middleware('perm:requests.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/count', 'count')->name('count');
        });
        Route::middleware('perm:requests.manage')->group(function () {
            Route::post('/', 'store')->name('store');
            Route::get('/stay/{stay}/portal', 'portal')->name('portal');
            Route::post('/stay/{stay}/portal/send', 'sendPortal')->middleware('throttle:10,1')->name('portal.send');
            Route::post('/stay/{stay}/portal/regenerate', 'regenerate')->name('portal.regenerate');
            Route::post('/{greq}/assign', 'assign')->name('assign');
            Route::post('/{greq}/{action}', 'action')->where('action', 'acknowledge|start|done|decline')->name('action');
        });
    });
