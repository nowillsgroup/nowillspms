<?php

use App\Http\Controllers;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
// Root serves the login page directly (200) for guests; signed-in users go to the dashboard.
Route::get('/', fn () => auth()->check() ? redirect()->to(LoginController::home(auth()->user())) : app(LoginController::class)->show())->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});
Route::middleware('guest')->controller(Controllers\Auth\TwoFactorController::class)->group(function () {
    Route::get('/login/two-factor', 'challenge')->name('login.2fa');
    Route::post('/login/two-factor', 'verify')->middleware('throttle:10,1')->name('login.2fa.verify');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Online booking engine & guest feedback (public, per hotel code)
Route::get('/book/{code}', [Controllers\BookingEngineController::class, 'show'])->name('booking.show');
Route::post('/book/{code}', [Controllers\BookingEngineController::class, 'store'])->middleware('throttle:10,1')->name('booking.store');
Route::get('/book/{code}/done/{no}', [Controllers\BookingEngineController::class, 'done'])->name('booking.done');
Route::get('/go/{dlcode}', [Controllers\DigitalMarketingController::class, 'go'])->where('dlcode', '[A-Za-z0-9-]{3,40}')->middleware('throttle:120,1')->name('dm.go'); // v6.2 tracked short link
Route::get('/feedback/{code}', [Controllers\FeedbackController::class, 'form'])->name('feedback.form');
Route::post('/feedback/{code}', [Controllers\FeedbackController::class, 'submit'])->middleware('throttle:10,1')->name('feedback.submit');

// v3.8 public gift voucher page: the voucher code is the bearer secret (40 random bits) – throttled against guessing
Route::get('/v/{code}', [Controllers\GiftVoucherController::class, 'publicPage'])->where('code', 'GV-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}')->middleware('throttle:30,1')->name('voucher.public');

// v2.6 marketing links: tracked booking link + unsubscribe / preferences (token = one marketing message)
Route::get('/m/{token}', [Controllers\MarketingLinkController::class, 'click'])->where('token', '[A-Za-z0-9]{32}')->middleware('throttle:60,1')->name('crm.click');
Route::get('/u/{token}', [Controllers\MarketingLinkController::class, 'preferences'])->where('token', '[A-Za-z0-9]{7,40}')->middleware('throttle:30,1')->name('crm.unsubscribe');
Route::post('/u/{token}', [Controllers\MarketingLinkController::class, 'save'])->where('token', '[A-Za-z0-9]{32}')->middleware('throttle:10,1')->name('crm.unsubscribe.save');
// RFC 8058 one-click unsubscribe: POSTed by the mail client itself – no session / CSRF
Route::post('/u/{token}/one-click', [Controllers\MarketingLinkController::class, 'oneClick'])->where('token', '[A-Za-z0-9]{32}')->middleware('throttle:30,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('crm.unsubscribe.oneclick');

// QR table menu – guest self-ordering (token printed on the table QR code)
// Polled by OTAs every few minutes: stateless – no session row, no cookies, no CSRF.
Route::get('/ical/{token}.ics', [Controllers\ChannelController::class, 'feed'])->where('token', '[A-Za-z0-9]{24,40}')->middleware('throttle:120,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('ical.feed');
// v2.8 channel manager API – called by OTAs / the channel hub: stateless, HMAC-signed, per-connection token
Route::prefix('api/channel/{token}')->where(['token' => '[A-Za-z0-9]{40}'])->controller(Controllers\ChannelApiController::class)->middleware('throttle:240,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('channel.api.')->group(function () {
        Route::post('/reservations', 'reservations')->name('reservations');
        Route::get('/ari', 'ari')->name('ari');
    });
// v3.7 PBX / call-logger push of call detail records: stateless, per-hotel 40-character token (Interfaces › PBX push)
Route::post('/api/pbx/{token}', [Controllers\InterfaceController::class, 'push'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:120,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('pbx.api.push');
// v7.61 fleet GPS ingest – a paired tracker pushes fixes to /api/fleet/{token}: stateless, token in the path, SHA-256 stored
Route::post('/api/fleet/{token}', [Controllers\GpsIngestController::class, 'push'])->where(['token' => '[A-Za-z0-9]{48}'])->middleware('throttle:fleet-gps')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('fleet.gps.push');
Route::get('/api/fleet/{token}/health', [Controllers\GpsIngestController::class, 'health'])->where(['token' => '[A-Za-z0-9]{48}'])->middleware('throttle:fleet-gps')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('fleet.gps.health');
// Robita normalized-event intake – stateless, bearer token is sent in Authorization (never in the URL).
Route::post('/api/robita/events', [Controllers\RobitaController::class, 'webhook'])->middleware('throttle:120,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('robita.api.webhook');
// v7.40 review-platform webhook – platforms push review rows; stateless, per-hotel 40-character token (Reputation > Reviews)
Route::post('/api/reviews/{token}', [Controllers\ReviewController::class, 'webhook'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:120,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('reviews.webhook');
// v4.0 REST API v1 – stateless JSON (bearer key per client; scopes, IP allow-list, per-key rate limit – see ApiAuthenticate)
Route::prefix('api/v1')->name('apiv1.')->controller(Controllers\Api\V1Controller::class)
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->group(function () {
        Route::get('/openapi.json', 'openapi')->middleware('throttle:60,1')->name('openapi');
        Route::middleware('api.client')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/hotel', 'hotel')->name('hotel');
            Route::get('/events', 'events')->name('events');
            Route::get('/availability', 'availability')->middleware('api.scope:availability:read')->name('availability');
            Route::get('/rates', 'rates')->middleware('api.scope:rates:read')->name('rates');
            Route::get('/reservations', 'reservations')->middleware('api.scope:reservations:read')->name('reservations.index');
            Route::post('/reservations', 'storeReservation')->middleware('api.scope:reservations:write')->name('reservations.store');
            Route::get('/reservations/{no}', 'reservation')->where('no', '[A-Za-z0-9_\-.]{1,30}')->middleware('api.scope:reservations:read')->name('reservations.show');
            Route::post('/reservations/{no}/cancel', 'cancelReservation')->where('no', '[A-Za-z0-9_\-.]{1,30}')->middleware('api.scope:reservations:write')->name('reservations.cancel');
            Route::get('/reservations/{no}/folio', 'folio')->where('no', '[A-Za-z0-9_\-.]{1,30}')->middleware('api.scope:folios:read')->name('reservations.folio');
            Route::get('/rooms', 'rooms')->middleware('api.scope:rooms:read')->name('rooms.index');
            Route::patch('/rooms/{roomNo}', 'updateRoom')->where('roomNo', '[A-Za-z0-9\-]{1,20}')->middleware('api.scope:housekeeping:write')->name('rooms.update');
            Route::get('/guests', 'guests')->middleware('api.scope:guests:read')->name('guests.index');
            Route::get('/guests/{id}', 'guest')->whereNumber('id')->middleware('api.scope:guests:read')->name('guests.show');
            Route::get('/reports/daily', 'daily')->middleware('api.scope:reports:read')->name('reports.daily');
        });
    });
// v3.1 post-stay guest survey (unguessable per-invitation link; only its SHA-256 is stored)
Route::get('/survey/{token}', [Controllers\SurveyPublicController::class, 'show'])->where('token', '[a-f0-9]{40}')->middleware('throttle:30,1')->name('survey.show');
Route::post('/survey/{token}', [Controllers\SurveyPublicController::class, 'submit'])->where('token', '[a-f0-9]{40}')->middleware('throttle:10,1')->name('survey.submit');
Route::get('/survey/{token}/review/{site}', [Controllers\SurveyPublicController::class, 'review'])->where(['token' => '[a-f0-9]{40}', 'site' => 'google|tripadvisor'])->middleware('throttle:10,1')->name('survey.review');

// v3.2 restaurant table booking (public page per hotel code) and the guest's manage / cancel link (only its SHA-256 is stored)
Route::get('/dine/r/{token}', [Controllers\DinePublicController::class, 'manage'])->where('token', '[a-f0-9]{40}')->middleware('throttle:30,1')->name('dine.manage');
Route::post('/dine/r/{token}/cancel', [Controllers\DinePublicController::class, 'cancel'])->where('token', '[a-f0-9]{40}')->middleware('throttle:10,1')->name('dine.cancel');
Route::get('/dine/{code}', [Controllers\DinePublicController::class, 'show'])->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:60,1,dine-view')->name('dine.show');
Route::post('/dine/{code}', [Controllers\DinePublicController::class, 'store'])->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:10,1,dine-book')->name('dine.store');

// v6.9 public careers page: open vacancies and the application form (CV upload). The throttle prefixes keep separate counters –
// unprefixed limiters share one key per IP, so browsing the jobs would use up the five applications a minute
Route::get('/careers/{code}', [Controllers\CareersPublicController::class, 'index'])->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:60,1,careers-view')->name('careers.index');
Route::get('/careers/{code}/{job}', [Controllers\CareersPublicController::class, 'show'])->where(['code' => '[A-Za-z0-9_-]{2,20}', 'job' => '[0-9]+'])->middleware('throttle:60,1,careers-view')->name('careers.show');
Route::post('/careers/{code}/{job}', [Controllers\CareersPublicController::class, 'apply'])->where(['code' => '[A-Za-z0-9_-]{2,20}', 'job' => '[0-9]+'])->middleware('throttle:5,1,careers-apply')->name('careers.apply');

// v2.9 privacy notice & data-principal request form (per hotel code); the request is confirmed through an e-mailed link
Route::get('/privacy-notice/{code}', [Controllers\PrivacyPublicController::class, 'show'])->where('code', '[A-Za-z0-9_-]{2,20}')->name('privacy.public');
Route::post('/privacy-notice/{code}', [Controllers\PrivacyPublicController::class, 'submit'])->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:5,1')->name('privacy.public.submit');
Route::get('/privacy-notice/{code}/verify/{token}', [Controllers\PrivacyPublicController::class, 'verify'])->where(['code' => '[A-Za-z0-9_-]{2,20}', 'token' => '[A-Za-z0-9]{40}'])
    ->middleware('throttle:20,1')->name('privacy.public.verify');
// Online (pre-arrival) check-in – tokenised link sent with the confirmation
Route::get('/checkin/{token}', [Controllers\PreCheckinController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('checkin.show');
Route::post('/checkin/{token}', [Controllers\PreCheckinController::class, 'submit'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:6,1')->name('checkin.submit');
// v4.1 – guest's private "enhance your stay" offers page (pre-arrival e-mail, online check-in, guest portal)
Route::get('/offers/{token}', [Controllers\UpsellPublicController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('offers.show');
Route::post('/offers/{token}', [Controllers\UpsellPublicController::class, 'order'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:10,1')->name('offers.order');
Route::post('/offers/{token}/cancel/{oid}', [Controllers\UpsellPublicController::class, 'cancel'])->where(['token' => '[A-Za-z0-9]{40}', 'oid' => '[0-9]+'])->middleware('throttle:10,1')->name('offers.cancel');
// v4.8 – supplier's private quotation page (link in the RFQ e-mail)
Route::get('/quote/{token}', [Controllers\RfqPublicController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('rfq.public');
Route::post('/quote/{token}', [Controllers\RfqPublicController::class, 'submit'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:10,1')->name('rfq.public.submit');
Route::post('/quote/{token}/decline', [Controllers\RfqPublicController::class, 'decline'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:5,1')->name('rfq.public.decline');
// v6.6 – valet ticket QR: the guest sees where the car is and calls for it
Route::get('/valet/{token}', [Controllers\ParkingController::class, 'publicPage'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('parking.public');
Route::post('/valet/{token}/request', [Controllers\ParkingController::class, 'publicRequest'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:6,1')->name('parking.public.request');
// v5.9 – guest's activity voucher (link in the confirmation SMS / e-mail) – no room number
Route::get('/activity-voucher/{token}', [Controllers\ConciergeController::class, 'publicVoucher'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('concierge.voucher.public');
// v4.2 – visitor pass a guest shares with their visitor (QR shown at the security desk)
Route::get('/visitor-pass/{token}', [Controllers\VisitorPassController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('visitor.pass');
Route::get('/menu/{token}', [Controllers\QrMenuController::class, 'show'])->where('token', '[A-Za-z0-9]{16,40}')->name('qrmenu.show');
Route::post('/menu/{token}', [Controllers\QrMenuController::class, 'order'])->where('token', '[A-Za-z0-9]{16,40}')->middleware('throttle:6,1')->name('qrmenu.order');
Route::get('/menu/{token}/status', [Controllers\QrMenuController::class, 'status'])->where('token', '[A-Za-z0-9]{16,40}')->middleware('throttle:60,1')->name('qrmenu.status');
// v7.2 Self-service lobby kiosk (stateless like the TVs: the kiosk's 40-character token identifies it, each guest visit has its own token)
Route::prefix('kiosk')->name('kiosk.')->controller(Controllers\KioskController::class)
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->group(function () {
        Route::get('/setup/{code}', 'setup')->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:30,1')->name('setup');
        Route::post('/setup/{code}', 'pairStart')->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:kiosk-pair')->name('pair.start');
        Route::get('/pair/{token}', 'pairStatus')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:60,1')->name('pair.status');
        Route::prefix('{token}')->where(['token' => '[A-Za-z0-9]{40}'])->group(function () {
            Route::middleware('throttle:kiosk')->group(function () {
                Route::get('/', 'app')->name('app');
                Route::get('/home', 'home')->name('home');
                Route::get('/v/{visit}', 'state')->where('visit', '[A-Za-z0-9]{40}')->name('state');
                Route::get('/v/{visit}/pay-status', 'payStatus')->where('visit', '[A-Za-z0-9]{40}')->name('pay.status');
                Route::get('/v/{visit}/walkin-offer', 'walkinOffer')->where('visit', '[A-Za-z0-9]{40}')->name('walkin.offer');
            });
            Route::middleware('throttle:kiosk-write')->group(function () {
                Route::post('/start', 'start')->name('start');
                Route::post('/v/{visit}/{action}', 'act')->where(['visit' => '[A-Za-z0-9]{40}', 'action' => 'find|details|document|sign|pay|room|keys|checkout|feedback|invoice|assist|cancel|walkin'])->name('act');
            });
        });
    });
// v7.1 In-room TV portal – hotel TVs / lobby screens (stateless: the 40-character device token in the start URL identifies the set)
Route::prefix('tv')->name('tv.')->controller(Controllers\TvController::class)
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->group(function () {
        Route::get('/setup/{code}', 'setup')->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:30,1')->name('setup');
        Route::post('/setup/{code}', 'pairStart')->where('code', '[A-Za-z0-9_-]{2,20}')->middleware('throttle:tv-pair')->name('pair.start');
        Route::get('/pair/{token}', 'pairStatus')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:60,1')->name('pair.status');
        Route::prefix('{token}')->where(['token' => '[A-Za-z0-9]{40}'])->group(function () {
            Route::middleware('throttle:tv')->group(function () {
                Route::get('/', 'app')->name('app');
                Route::get('/state', 'state')->name('state');
                Route::get('/portal-qr', 'portalQr')->name('portal-qr');
                Route::get('/data/{section}', 'data')->where('section', 'channels|movies|dining|services|pages|explore|messages')->name('data');
            });
            Route::middleware('throttle:tv-write')->group(function () {
                Route::post('/welcome', 'welcome')->name('welcome');
                Route::post('/language', 'language')->name('language');
                Route::post('/event', 'event')->name('event');
                Route::post('/read/{id}', 'read')->whereNumber('id')->name('read');
                Route::post('/ack/{id}', 'ack')->whereNumber('id')->name('ack');
                Route::post('/rent/{movie}', 'rent')->whereNumber('movie')->name('rent');
                Route::post('/order', 'order')->name('order');
                Route::post('/request', 'request')->name('request');
                Route::post('/wakeup', 'wakeup')->name('wakeup');
                Route::post('/bill', 'bill')->name('bill');
                Route::post('/checkout', 'checkout')->name('checkout');
                Route::post('/feedback', 'feedback')->name('feedback');
            });
        });
    });
// Guest mobile portal (v1.7) – private per-stay link sent at check-in / printed on the welcome card
Route::prefix('stay/{token}')->where(['token' => '[A-Za-z0-9]{40}'])->controller(Controllers\GuestPortalController::class)->name('portal.')->group(function () {
    Route::middleware('throttle:portal')->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('/requests', 'requests')->name('requests');
        Route::get('/dining', 'dining')->name('dining');
        Route::get('/spa', 'spa')->name('spa');
        Route::get('/transport', 'transport')->name('transport');
        Route::get('/bill', 'bill')->name('bill');
        Route::get('/exchange', 'exchange')->name('exchange');
        Route::get('/messages', 'messages')->name('messages');
        Route::get('/visitors', 'visitors')->name('visitors');   // v4.2
        Route::get('/explore', 'explore')->name('explore');      // v5.9 activities & local guide
        Route::get('/chat', 'chat')->name('chat');               // v7.3 chat with the hotel
    });
    Route::get('/chat/poll', 'chatPoll')->middleware('throttle:portal-chat')->name('chat.poll');
    Route::middleware('throttle:portal-write')->group(function () {
        Route::post('/requests', 'storeRequest')->name('requests.store');
        Route::post('/requests/{rid}/cancel', 'cancelRequest')->whereNumber('rid')->name('requests.cancel');
        Route::post('/requests/{rid}/rate', 'rateRequest')->whereNumber('rid')->name('requests.rate');
        Route::post('/dining', 'orderDining')->name('dining.order');
        Route::post('/spa', 'bookSpa')->name('spa.book');
        Route::post('/spa/{aid}/cancel', 'cancelSpa')->whereNumber('aid')->name('spa.cancel');
        Route::post('/transport', 'requestTransport')->name('transport.request');
        Route::post('/transport/{tid}/cancel', 'cancelTransport')->whereNumber('tid')->name('transport.cancel');
        Route::post('/checkout', 'expressCheckout')->name('checkout');
        Route::post('/pay', 'payOnline')->name('pay');
        Route::post('/feedback', 'feedback')->name('feedback');
        Route::post('/explore', 'bookActivity')->name('explore.book');                                        // v5.9
        Route::post('/explore/{bid}/cancel', 'cancelActivity')->whereNumber('bid')->name('explore.cancel');
        Route::post('/visitors', 'registerVisitor')->name('visitors.store');                                  // v4.2
        Route::post('/visitors/{vid}/cancel', 'cancelVisitor')->whereNumber('vid')->name('visitors.cancel');
        Route::post('/visitors/preference', 'visitorPolicy')->name('visitors.policy');
        Route::post('/green', 'green')->name('green');                                                        // v7.0 green-stay choice
        Route::post('/chat', 'chatSend')->name('chat.send');                                                  // v7.3
        Route::post('/chat/rate', 'chatRate')->name('chat.rate');
    });
});
// Online payments (v2.1) – public payment page per link; card details are entered on the gateway's hosted page
Route::prefix('pay/{token}')->where(['token' => '[A-Za-z0-9]{40}'])->controller(Controllers\PayController::class)->name('pay.')->group(function () {
    Route::middleware('throttle:portal')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::get('/return', 'back')->name('return');
        Route::get('/demo', 'demo')->name('demo');
    });
    Route::middleware('throttle:portal-write')->group(function () {
        Route::post('/start', 'start')->name('start');
        Route::post('/demo', 'demoSubmit')->name('demo.submit');
    });
});
// v7.3 inbound guest messages (SMS / WhatsApp gateway, inbound e-mail) – stateless, no CSRF; authenticated by the provider's signature
// v7.4 corporate & travel-agent portal (B2B extranet): its own session ('partner' middleware), never the staff guard.
// Token links (invitation / reset) are 48 random characters; only their SHA-256 is stored.
Route::prefix('partner')->name('partner.')->controller(Controllers\PartnerPortalController::class)->group(function () {
    Route::get('/login', 'loginForm')->middleware('throttle:60,1,partner-view')->name('login');
    Route::post('/login', 'login')->middleware('throttle:10,1,partner-login')->name('login.attempt');
    Route::get('/forgot', 'forgotForm')->middleware('throttle:60,1,partner-view')->name('forgot');
    Route::post('/forgot', 'forgot')->middleware('throttle:5,1,partner-forgot')->name('forgot.send');
    Route::get('/invite/{token}', 'inviteForm')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:30,1,partner-token')->name('invite');
    Route::post('/invite/{token}', 'inviteAccept')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:10,1,partner-token-save')->name('invite.accept');
    Route::get('/reset/{token}', 'resetForm')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:30,1,partner-token')->name('reset');
    Route::post('/reset/{token}', 'reset')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:10,1,partner-token-save')->name('reset.save');
    Route::middleware(['partner', 'throttle:240,1,partner-app'])->group(function () {
        Route::get('/', 'home')->name('home');
        Route::post('/logout', 'logout')->name('logout');
        Route::get('/book', 'book')->name('book');
        Route::post('/book', 'store')->middleware('throttle:20,1,partner-book')->name('book.store');
        Route::get('/bookings', 'bookings')->name('bookings');
        Route::get('/bookings/{id}', 'booking')->whereNumber('id')->name('booking');
        Route::post('/bookings/{id}/cancel', 'cancel')->whereNumber('id')->name('booking.cancel');
        Route::post('/bookings/{id}/amend', 'amend')->whereNumber('id')->name('booking.amend');
        Route::get('/requests', 'requests')->name('requests');
        Route::post('/requests/group', 'group')->name('requests.group');
        Route::post('/requests/{id}/withdraw', 'withdraw')->whereNumber('id')->name('requests.withdraw');
        Route::get('/statement', 'statement')->name('statement');
        Route::get('/invoices/{folio}', 'invoice')->whereNumber('folio')->name('invoice');
        Route::post('/pay', 'pay')->middleware('throttle:10,1,partner-pay')->name('pay');
        Route::get('/commissions', 'commissions')->name('commissions');
        Route::get('/users', 'users')->name('users');
        Route::post('/users', 'invite')->middleware('throttle:20,1,partner-invite')->name('users.invite');
        Route::post('/users/{id}', 'userAction')->whereNumber('id')->name('users.action');
        Route::get('/profile', 'profile')->name('profile');
        Route::post('/profile/password', 'password')->middleware('throttle:10,1,partner-pw')->name('password');
    });
});

// v7.5 event organiser portal (B2B extranet for MICE & banquet clients): its own session ('organiser' middleware), never the staff guard.
// Token links (invitation / reset) are 48 random characters; only their SHA-256 is stored.
Route::prefix('organiser')->name('organiser.')->controller(Controllers\OrganiserPortalController::class)->group(function () {
    Route::get('/login', 'loginForm')->middleware('throttle:60,1,organiser-view')->name('login');
    Route::post('/login', 'login')->middleware('throttle:10,1,organiser-login')->name('login.attempt');
    Route::get('/forgot', 'forgotForm')->middleware('throttle:60,1,organiser-view')->name('forgot');
    Route::post('/forgot', 'forgot')->middleware('throttle:5,1,organiser-forgot')->name('forgot.send');
    Route::get('/invite/{token}', 'inviteForm')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:30,1,organiser-token')->name('invite');
    Route::post('/invite/{token}', 'inviteAccept')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:10,1,organiser-token-save')->name('invite.accept');
    Route::get('/reset/{token}', 'resetForm')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:30,1,organiser-token')->name('reset');
    Route::post('/reset/{token}', 'reset')->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:10,1,organiser-token-save')->name('reset.save');
    Route::middleware(['organiser', 'throttle:240,1,organiser-app'])->group(function () {
        Route::get('/', 'home')->name('home');
        Route::post('/logout', 'logout')->name('logout');
        Route::get('/events', 'events')->name('events');
        Route::get('/events/{id}', 'event')->whereNumber('id')->name('event');
        Route::get('/events/{id}/beo', 'beo')->whereNumber('id')->name('beo');
        Route::post('/events/{id}/beo/sign', 'beoSign')->whereNumber('id')->middleware('throttle:20,1,organiser-beo')->name('beo.sign');
        Route::post('/events/{id}/guarantee', 'guarantee')->whereNumber('id')->middleware('throttle:20,1,organiser-guarantee')->name('guarantee');
        Route::post('/events/{id}/change', 'change')->whereNumber('id')->middleware('throttle:20,1,organiser-change')->name('change');
        Route::post('/events/{id}/cancel', 'cancelReq')->whereNumber('id')->middleware('throttle:10,1,organiser-cancel')->name('cancel');
        Route::post('/events/{id}/pay', 'pay')->whereNumber('id')->middleware('throttle:10,1,organiser-pay')->name('pay');
        Route::get('/events/{id}/guests', 'attendees')->whereNumber('id')->name('attendees');
        Route::post('/events/{id}/guests', 'attendeeStore')->whereNumber('id')->middleware('throttle:60,1,organiser-guests')->name('attendees.store');
        Route::post('/events/{id}/guests/{aid}/delete', 'attendeeDelete')->whereNumber(['id', 'aid'])->name('attendees.delete');
        Route::get('/requests', 'requests')->name('requests');
        Route::post('/requests/quote', 'quote')->middleware('throttle:20,1,organiser-quote')->name('requests.quote');
        Route::post('/requests/{id}/withdraw', 'withdraw')->whereNumber('id')->name('requests.withdraw');
        Route::get('/users', 'users')->name('users');
        Route::post('/users', 'invite')->middleware('throttle:20,1,organiser-invite')->name('users.invite');
        Route::post('/users/{id}', 'userAction')->whereNumber('id')->name('users.action');
        Route::get('/profile', 'profile')->name('profile');
        Route::post('/profile/password', 'password')->middleware('throttle:10,1,organiser-pw')->name('password');
    });
});

Route::post('/webhooks/inbox/{provider}/{code}', [Controllers\InboxController::class, 'webhook'])->where(['provider' => 'generic|twilio', 'code' => '[A-Za-z0-9_-]{2,20}'])->middleware('throttle:240,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('inbox.webhook');
// Gateway → server notifications: stateless, no CSRF; authenticated by the provider's HMAC signature
Route::post('/webhooks/pay/{provider}/{code}', [Controllers\PayController::class, 'webhook'])->where(['provider' => '[a-z]+', 'code' => '[A-Za-z0-9_-]{2,20}'])->middleware('throttle:240,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class, \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->name('pay.webhook');

/*
|--------------------------------------------------------------------------
| Application (authenticated + tenant-scoped)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant', 'twofactor'])->group(function () {
    require __DIR__.'/front-office.php';
    require __DIR__.'/guest-operations.php';
    require __DIR__.'/pos-finance.php';
    require __DIR__.'/management.php';
});
