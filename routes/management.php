<?php

declare(strict_types=1);

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

    // v5.6 financial close – periods & checklist, schedules, recurring journals, year end
    Route::controller(Controllers\CloseController::class)->prefix('close')->name('close.')->group(function () {
        Route::middleware('perm:close.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/accruals', 'accruals')->name('accruals');
            Route::get('/accruals/{accrual}', 'accrual')->whereNumber('accrual')->name('accruals.show');
            Route::get('/recurring', 'recurring')->name('recurring');
            Route::get('/year-end', 'yearEnd')->name('yearend');
            Route::get('/{month}', 'show')->where('month', '\d{4}-\d{2}')->name('show');
        });
        Route::middleware('perm:close.prepare')->group(function () {
            Route::post('/{month}/check', 'check')->where('month', '\d{4}-\d{2}')->middleware('throttle:20,1')->name('check');
            Route::post('/{month}/post', 'post')->where('month', '\d{4}-\d{2}')->middleware('throttle:10,1')->name('post');
            Route::post('/{month}/tasks', 'addTask')->where('month', '\d{4}-\d{2}')->name('tasks.add');
            Route::post('/{month}/submit', 'submit')->where('month', '\d{4}-\d{2}')->name('submit');
            Route::post('/{month}/reopen-request', 'reopenRequest')->where('month', '\d{4}-\d{2}')->name('reopen.request');
            Route::post('/tasks/{closetask}/done', 'taskDone')->name('tasks.done');
            Route::post('/tasks/{closetask}/waive', 'taskWaive')->name('tasks.waive');
            Route::post('/tasks/{closetask}/reset', 'taskReset')->name('tasks.reset');
            Route::post('/accruals', 'storeAccrual')->middleware('throttle:30,1')->name('accruals.store');
            Route::post('/recurring', 'storeRecurring')->middleware('throttle:30,1')->name('recurring.store');
        });
        Route::post('/accruals/{accrual}/cancel', 'cancelAccrual')->middleware('perm:close.prepare,close.approve')->name('accruals.cancel');
        Route::middleware('perm:close.approve')->group(function () {
            Route::post('/{month}/approve', 'approve')->where('month', '\d{4}-\d{2}')->name('approve');
            Route::post('/{month}/reject', 'reject')->where('month', '\d{4}-\d{2}')->name('reject');
            Route::post('/{month}/reopen-approve', 'reopenApprove')->where('month', '\d{4}-\d{2}')->name('reopen.approve');
            Route::post('/{month}/reopen-deny', 'reopenDeny')->where('month', '\d{4}-\d{2}')->name('reopen.deny');
            Route::post('/accruals/{accrual}/decide', 'decideAccrual')->name('accruals.decide');
            Route::post('/recurring/{rjournal}/decide', 'decideRecurring')->name('recurring.decide');
            Route::post('/recurring/{rjournal}/status', 'recurringStatus')->name('recurring.status');
            Route::post('/settings', 'settings')->name('settings');
        });
    });

    // v5.7 treasury & cash management
    Route::controller(Controllers\TreasuryController::class)->prefix('treasury')->name('treasury.')->group(function () {
        Route::middleware('perm:treasury.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/transfers', 'transfers')->name('transfers');
            Route::get('/transfers/{transfer}', 'showTransfer')->whereNumber('transfer')->name('transfers.show');
            Route::get('/suggest', 'suggest')->name('suggest');
            Route::get('/cheques', 'cheques')->name('cheques');
            Route::get('/forecast', 'forecast')->name('forecast');
            Route::get('/cash-flow', 'cashFlow')->name('cashflow');
            Route::get('/accounts', 'accounts')->name('accounts');
        });
        Route::middleware('perm:treasury.operate')->group(function () {
            Route::post('/transfers', 'storeTransfer')->name('transfers.store');
            Route::post('/transfers/{transfer}/dispatch', 'dispatchTransfer')->name('transfers.dispatch');
            Route::post('/transfers/{transfer}/complete', 'complete')->name('transfers.complete');
            Route::post('/transfers/{transfer}/cancel', 'cancel')->name('transfers.cancel');
            Route::post('/cheques/received', 'receiveCheque')->name('cheques.receive');
            Route::post('/cheques/received/{cheque}/bank-pdc', 'bankPdc')->name('cheques.pdc');
            Route::post('/cheques/received/{cheque}/clear', 'clearCheque')->name('cheques.clear');
            Route::post('/cheques/received/{cheque}/bounce', 'bounce')->name('cheques.bounce');
            Route::post('/cheques/received/{cheque}/return', 'returnCheque')->name('cheques.return');
            Route::post('/cheques/issued', 'issue')->name('cheques.issue');
            Route::post('/cheques/issued/{issued}/void', 'voidIssued')->name('cheques.void');
            Route::post('/cheques/issued/{issued}/clear', 'clearIssued')->name('cheques.presented');
            Route::post('/cheques/books/{book}/spoil', 'spoil')->name('cheques.spoil');
            Route::post('/cheques/sync', 'sync')->name('cheques.sync');
            Route::post('/forecast/lines', 'storeLine')->name('forecast.store');
            Route::put('/forecast/lines/{fline}', 'updateLine')->name('forecast.update');
            Route::delete('/forecast/lines/{fline}', 'deleteLine')->name('forecast.delete');
        });
        Route::middleware('perm:treasury.approve')->group(function () {
            Route::post('/transfers/{transfer}/approve', 'approve')->name('transfers.approve');
            Route::post('/transfers/{transfer}/reject', 'reject')->name('transfers.reject');
        });
        Route::middleware('perm:treasury.manage')->group(function () {
            Route::post('/accounts', 'storeAccount')->name('accounts.store');
            Route::put('/accounts/{taccount}', 'updateAccount')->name('accounts.update');
            Route::post('/cheques/books', 'addBook')->name('cheques.books');
            Route::post('/settings', 'settings')->name('settings');
            Route::post('/cf-classes', 'cfClasses')->name('cfclasses');
        });
    });

    // v5.8 Owner reporting & USALI management accounts
    // v6.0 learning & development: training matrix, courses, sessions, records, SOP library
    Route::controller(Controllers\LearningController::class)->prefix('learning')->name('learning.')->group(function () {
        Route::middleware('perm:learning.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/matrix', 'matrix')->name('matrix');
            Route::get('/matrix/print', 'matrixPrint')->name('matrix.print');
            Route::get('/staff/{employee}', 'employee')->whereNumber('employee')->name('employee');
            Route::get('/courses', 'courses')->name('courses');
            Route::get('/courses/{tcourse}', 'course')->whereNumber('tcourse')->name('course');
            Route::get('/sessions', 'sessions')->name('sessions');
            Route::get('/sessions/{tsession}', 'session')->whereNumber('tsession')->name('session');
            Route::get('/sessions/{tsession}/sheet', 'sessionSheet')->whereNumber('tsession')->name('session.sheet');
            Route::get('/records', 'records')->name('records');
            Route::get('/records/{trecord}/certificate', 'certificate')->whereNumber('trecord')->name('certificate');
            Route::get('/sops', 'sops')->name('sops');
            Route::get('/sops/{sop}', 'sop')->whereNumber('sop')->name('sop');
            Route::get('/sops/{sop}/print', 'sopPrint')->whereNumber('sop')->name('sop.print');
            Route::get('/sops/{sop}/sheet', 'sopSheet')->whereNumber('sop')->name('sop.sheet');
        });
        Route::middleware('perm:learning.train')->group(function () {
            Route::post('/sessions', 'sessionStore')->name('sessions.store');
            Route::post('/sessions/{tsession}/enrol', 'enrol')->whereNumber('tsession')->name('session.enrol');
            Route::post('/sessions/{tsession}/complete', 'complete')->whereNumber('tsession')->name('session.complete');
            Route::post('/sessions/{tsession}/cancel', 'cancelSession')->whereNumber('tsession')->name('session.cancel');
            Route::post('/records/{trecord}/unenrol', 'unenrol')->whereNumber('trecord')->name('unenrol');
            Route::post('/records', 'recordStore')->name('records.store');
            Route::post('/sops/{sop}/sheet', 'sopRecordSheet')->whereNumber('sop')->name('sop.sheet.store');
        });
        Route::middleware('perm:learning.sop')->group(function () {
            Route::get('/sops/new', 'sopCreate')->name('sop.create');
            Route::get('/sops/{sop}/edit', 'sopEdit')->whereNumber('sop')->name('sop.edit');
            Route::post('/sops', 'sopStore')->name('sop.store');
            Route::put('/sops/{sop}', 'sopUpdate')->whereNumber('sop')->name('sop.update');
            Route::post('/sops/{sop}/publish', 'sopPublish')->whereNumber('sop')->name('sop.publish');
            Route::post('/sops/{sop}/review', 'sopReview')->whereNumber('sop')->name('sop.review');
            Route::post('/sops/{sop}/discard', 'sopDiscard')->whereNumber('sop')->name('sop.discard');
            Route::post('/sops/{sop}/retire', 'sopRetire')->whereNumber('sop')->name('sop.retire');
        });
        Route::middleware('perm:learning.manage')->group(function () {
            Route::post('/courses', 'courseStore')->name('courses.store');
            Route::put('/courses/{tcourse}', 'courseUpdate')->whereNumber('tcourse')->name('course.update');
            Route::post('/courses/{tcourse}/requirements', 'requirementStore')->whereNumber('tcourse')->name('requirement.store');
            Route::delete('/requirements/{treq}', 'requirementDestroy')->whereNumber('treq')->name('requirement.destroy');
            Route::post('/exempt', 'exempt')->name('exempt');
            Route::post('/records/{trecord}/delete', 'recordDelete')->whereNumber('trecord')->name('record.delete');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
    });
    Route::controller(Controllers\LearningController::class)->prefix('me')->name('me.')->middleware('perm:hr.self')->group(function () {
        Route::get('/learning', 'mine')->name('learning');
        Route::get('/sops/{sop}', 'mySop')->whereNumber('sop')->name('sop');
        Route::post('/sops/{sop}/sign', 'mySign')->whereNumber('sop')->middleware('throttle:30,1')->name('sop.sign');
        Route::get('/certificates/{trecord}', 'myCertificate')->whereNumber('trecord')->name('certificate');
    });

    // ---- v6.1 Marketing plan: calendar of marketing events, budget vs actual, collateral library
    Route::controller(Controllers\MarketingPlanController::class)->prefix('sales')->name('sales.')->group(function () {
        Route::middleware('perm:sales.view')->group(function () {
            Route::get('/marketing', 'index')->name('marketing');
            Route::get('/marketing/{mevent}', 'show')->whereNumber('mevent')->name('mevent');
            Route::get('/marketing-budget', 'budget')->name('budget');
            Route::get('/collateral', 'collateral')->name('collateral');
            Route::get('/collateral/{mcollateral}/download', 'download')->whereNumber('mcollateral')->name('collateral.download');
        });
        Route::middleware('perm:sales.marketing,sales.manage')->group(function () {
            Route::post('/marketing', 'store')->name('mevents.store');
            Route::put('/marketing/{mevent}', 'update')->whereNumber('mevent')->name('mevent.update');
            Route::post('/marketing/{mevent}/status', 'status')->whereNumber('mevent')->name('mevent.status');
            Route::post('/marketing-budget', 'saveBudget')->name('budget.save');
            Route::post('/collateral', 'collateralStore')->name('collateral.store');
            Route::post('/collateral/{mcollateral}', 'collateralUpdate')->whereNumber('mcollateral')->name('collateral.update');
            Route::post('/collateral/{mcollateral}/delete', 'collateralDelete')->whereNumber('mcollateral')->name('collateral.delete');
        });
    });

    // ---- v6.2 Digital marketing: dashboard, social content calendar & approval, paid campaigns, tracked links, channels & listings, influencers
    // v6.3 business development – competitive set, market mix, partnerships, initiatives
    // v6.4 central reservations & lost business: group availability & booking, enquiry log (denials / regrets), referrals, guest lookup, KPIs
    Route::controller(Controllers\CroController::class)->prefix('cro')->name('cro.')->group(function () {
        Route::middleware('perm:cro.view,cro.book,cro.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/enquiries', 'enquiries')->name('enquiries');
            Route::get('/enquiries/{croenquiry}', 'show')->whereNumber('croenquiry')->name('enquiry');
            Route::get('/guests', 'guests')->name('guests');
            Route::get('/lost', 'lost')->name('lost');
            Route::get('/performance', 'performance')->name('performance');
        });
        Route::middleware('perm:cro.book,cro.manage')->group(function () {
            Route::post('/book', 'book')->name('book');
            Route::post('/enquiries', 'store')->name('enquiries.store');
            Route::post('/enquiries/{croenquiry}/lost', 'markLost')->whereNumber('croenquiry')->name('enquiry.lost');
            Route::post('/enquiries/{croenquiry}/reopen', 'reopen')->whereNumber('croenquiry')->name('enquiry.reopen');
            Route::post('/enquiries/{croenquiry}/contact', 'contact')->whereNumber('croenquiry')->name('enquiry.contact');
        });
        Route::middleware('perm:cro.manage')->group(function () {
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'settingsSave')->name('settings.save');
        });
    });

    // v6.8 F&B cost control & menu engineering: recipe cards & plate cost, menu engineering, cost statement, usage variance, pricing, yields, staff meals
    Route::controller(Controllers\FbCostController::class)->prefix('fbc')->name('fbc.')->group(function () {
        Route::middleware('perm:fbc.view,fbc.cost,fbc.price')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/recipes', 'recipes')->name('recipes');
            Route::get('/recipes/{fbdish}', 'card')->whereNumber('fbdish')->name('card');
            Route::get('/recipes/{fbdish}/print', 'cardPrint')->whereNumber('fbdish')->name('card.print');
            Route::get('/menu-engineering', 'menu')->name('menu');
            Route::get('/side-dishes', 'sides')->name('sides'); // v9.34 what the side dishes earned and cost
            Route::get('/statement', 'statement')->name('statement');
            Route::get('/variance', 'variance')->name('variance');
            Route::get('/pricing', 'pricing')->name('pricing');
            Route::get('/yields', 'yields')->name('yields');
            Route::get('/staff-meals', 'meals')->name('meals');
        });
        Route::middleware('perm:fbc.cost,fbc.price')->group(function () {
            Route::get('/recipes/{fbdish}/edit', 'cardEdit')->whereNumber('fbdish')->name('card.edit');
            Route::post('/recipes/{fbdish}', 'cardSave')->whereNumber('fbdish')->name('card.save');
            Route::post('/yields', 'yieldStore')->name('yields.store');
            Route::post('/yields/{fbyield}/apply', 'yieldApply')->whereNumber('fbyield')->name('yields.apply');
            Route::post('/staff-meals', 'mealSave')->name('meals.store');
            Route::post('/staff-meals/{fbmeal}/delete', 'mealDelete')->whereNumber('fbmeal')->name('meals.delete');
        });
        Route::middleware('perm:fbc.price')->group(function () {
            Route::post('/recipes/{fbdish}/approve', 'cardApprove')->whereNumber('fbdish')->name('card.approve');
            Route::post('/pricing/{fbdish}', 'priceChange')->whereNumber('fbdish')->name('pricing.change');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'settingsSave')->name('settings.save');
        });
    });

    // v6.9 talent management: recruitment, new starters, appraisals, discipline & grievances, exits
    Route::controller(Controllers\TalentController::class)->prefix('talent')->name('talent.')->group(function () {
        Route::middleware('perm:talent.view,talent.recruit,talent.approve,talent.appraise,talent.discipline,talent.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/vacancies', 'vacancies')->name('vacancies');
            Route::get('/vacancies/{tvacancy}', 'vacancy')->whereNumber('tvacancy')->name('vacancy');
            Route::get('/establishment', 'establishment')->name('establishment');
            Route::get('/interviews/{tinterview}', 'interview')->whereNumber('tinterview')->name('interview');
            Route::post('/interviews/{tinterview}/score', 'score')->whereNumber('tinterview')->name('interview.score');
            Route::get('/applicants/{tapplicant}/cv', 'cv')->whereNumber('tapplicant')->name('applicant.cv');
        });
        Route::middleware('perm:talent.recruit,talent.approve')->group(function () {
            Route::get('/applicants', 'applicants')->name('applicants');
            Route::get('/applicants/{tapplicant}', 'applicant')->whereNumber('tapplicant')->name('applicant');
            Route::get('/offers/{toffer}/letter', 'offerLetter')->whereNumber('toffer')->name('offer.letter');
            Route::post('/vacancies', 'vacancyStore')->name('vacancies.store');
            Route::post('/vacancies/{tvacancy}', 'vacancyUpdate')->whereNumber('tvacancy')->name('vacancy.update');
            Route::post('/vacancies/{tvacancy}/submit', 'vacancySubmit')->whereNumber('tvacancy')->name('vacancy.submit');
            Route::post('/vacancies/{tvacancy}/status', 'vacancyStatus')->whereNumber('tvacancy')->name('vacancy.status');
            Route::post('/vacancies/{tvacancy}/applicants', 'applicantStore')->whereNumber('tvacancy')->name('applicants.store');
            Route::post('/applicants/{tapplicant}/move', 'applicantMove')->whereNumber('tapplicant')->name('applicant.move');
            Route::post('/applicants/{tapplicant}/interviews', 'interviewStore')->whereNumber('tapplicant')->name('interviews.store');
            Route::post('/interviews/{tinterview}/status', 'interviewStatus')->whereNumber('tinterview')->name('interview.status');
            Route::post('/applicants/{tapplicant}/offer', 'offerStore')->whereNumber('tapplicant')->name('offer.store');
            Route::post('/offers/{toffer}/respond', 'offerRespond')->whereNumber('toffer')->name('offer.respond');
        });
        Route::middleware('perm:talent.approve')->group(function () {
            Route::post('/vacancies/{tvacancy}/approve', 'vacancyApprove')->whereNumber('tvacancy')->name('vacancy.approve');
            Route::post('/vacancies/{tvacancy}/return', 'vacancyReturn')->whereNumber('tvacancy')->name('vacancy.return');
            Route::post('/offers/{toffer}/approve', 'offerApprove')->whereNumber('toffer')->name('offer.approve');
        });
        Route::middleware('perm:talent.recruit,talent.appraise')->group(function () {
            Route::get('/starters', 'starters')->name('starters');
            Route::get('/starters/{temployee}', 'starter')->whereNumber('temployee')->name('starter');
            Route::get('/starters/{temployee}/statement', 'statement')->whereNumber('temployee')->name('statement');
            Route::post('/starters/{temployee}/statement', 'statementIssue')->whereNumber('temployee')->name('statement.issue');
            Route::post('/tasks/{ttask}', 'taskToggle')->whereNumber('ttask')->name('task.toggle');
        });
        Route::middleware('perm:talent.appraise')->group(function () {
            Route::post('/starters/{temployee}/probation', 'probation')->whereNumber('temployee')->name('probation');
            Route::get('/appraisals', 'appraisals')->name('appraisals');
            Route::get('/appraisals/cycles/{tcycle}', 'cycle')->whereNumber('tcycle')->name('cycle');
            Route::post('/appraisals/cycles', 'cycleStore')->name('cycles.store');
            Route::post('/appraisals/cycles/{tcycle}/close', 'cycleClose')->whereNumber('tcycle')->name('cycle.close');
            Route::post('/appraisals/{tappraisal}/reviewer', 'reviewer')->whereNumber('tappraisal')->name('appraisal.reviewer');
            Route::post('/appraisals/{tappraisal}/signoff', 'signoff')->whereNumber('tappraisal')->name('appraisal.signoff');
        });
        Route::middleware('perm:hr.self,talent.view,talent.appraise')->group(function () {
            Route::get('/me', 'me')->name('me');
            Route::get('/appraisals/{tappraisal}', 'appraisal')->whereNumber('tappraisal')->name('appraisal');
            Route::post('/appraisals/{tappraisal}/self', 'selfSave')->whereNumber('tappraisal')->name('appraisal.self');
            Route::post('/appraisals/{tappraisal}/review', 'reviewSave')->whereNumber('tappraisal')->name('appraisal.review');
            Route::post('/appraisals/{tappraisal}/acknowledge', 'acknowledge')->whereNumber('tappraisal')->name('appraisal.acknowledge');
        });
        Route::middleware('perm:talent.discipline')->group(function () {
            Route::get('/cases', 'cases')->name('cases');
            Route::get('/cases/{tcase}', 'caseShow')->whereNumber('tcase')->name('case');
            Route::get('/cases/{tcase}/letter/{kind}', 'caseLetter')->whereNumber('tcase')->where('kind', 'query|outcome')->name('case.letter');
            Route::post('/cases', 'caseStore')->name('cases.store');
            Route::post('/cases/{tcase}/{step}', 'caseAction')->whereNumber('tcase')->where('step', 'query|reply|hearing|held|decide|investigating|resolved|withdrawn|close')->name('case.action');
            Route::get('/exits', 'exits')->name('exits');
            Route::get('/exits/{texit}', 'exitShow')->whereNumber('texit')->name('exit');
            Route::get('/exits/{texit}/letter', 'exitLetter')->whereNumber('texit')->name('exit.letter');
            Route::post('/exits', 'exitStore')->name('exits.store');
            Route::post('/exits/{texit}/{step}', 'exitAction')->whereNumber('texit')->where('step', 'clearance|interview|complete|withdraw')->name('exit.action');
        });
        Route::middleware('perm:talent.manage')->group(function () {
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'settingsSave')->name('settings.save');
            Route::post('/establishment', 'establishmentSave')->name('establishment.save');
            Route::post('/managers', 'managersSave')->name('managers.save');
        });
    });

    // v6.7 health, safety & fire: equipment & inspection round, drills & wardens, risk assessments, permits to work, accidents
    Route::controller(Controllers\HseController::class)->prefix('hse')->name('hse.')->group(function () {
        Route::middleware('perm:hse.view,hse.check,hse.permit,hse.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/equipment', 'equipment')->name('equipment');
            Route::get('/equipment/{hseequipment}', 'equipmentShow')->whereNumber('hseequipment')->name('equipment.show');
            Route::get('/permits', 'permits')->name('permits');
            Route::get('/permits/{hsepermit}', 'permit')->whereNumber('hsepermit')->name('permit');
            Route::get('/permits/{hsepermit}/print', 'permitPrint')->whereNumber('hsepermit')->name('permit.print');
            Route::get('/accidents', 'accidents')->name('accidents');
            Route::get('/accidents/{hseaccident}', 'accident')->whereNumber('hseaccident')->name('accident');
            Route::get('/risks', 'risks')->name('risks');
            Route::get('/risks/{hserisk}', 'risk')->whereNumber('hserisk')->name('risk');
            Route::get('/drills', 'drills')->name('drills');
            Route::get('/drills/{hsedrill}', 'drill')->whereNumber('hsedrill')->name('drill');
        });
        Route::middleware('perm:hse.check,hse.permit,hse.manage')->group(function () {
            Route::get('/round', 'round')->name('round');
            Route::post('/round', 'roundSave')->name('round.save');
            Route::post('/equipment/{hseequipment}/check', 'check')->whereNumber('hseequipment')->name('equipment.check');
            Route::post('/permits', 'permitStore')->name('permits.store');
            Route::post('/permits/{hsepermit}/cancel', 'permitCancel')->whereNumber('hsepermit')->name('permits.cancel');
            Route::post('/accidents', 'accidentStore')->name('accidents.store');
            Route::post('/drills/{hsedrill}/record', 'drillRecord')->whereNumber('hsedrill')->name('drills.record');
            Route::post('/risks/{hserisk}/review', 'riskReview')->whereNumber('hserisk')->name('risks.review');
        });
        Route::middleware('perm:hse.permit,hse.manage')->group(function () {
            Route::post('/permits/{hsepermit}/issue', 'permitIssue')->whereNumber('hsepermit')->name('permits.issue');
            Route::post('/permits/{hsepermit}/close', 'permitClose')->whereNumber('hsepermit')->name('permits.close');
        });
        Route::middleware('perm:hse.manage')->group(function () {
            Route::get('/accidents/{hseaccident}/notice', 'notice')->whereNumber('hseaccident')->name('accident.notice');
            Route::post('/equipment', 'equipmentSave')->name('equipment.store');
            Route::post('/equipment/{hseequipment}', 'equipmentSave')->whereNumber('hseequipment')->name('equipment.update');
            Route::post('/accidents/{hseaccident}', 'accidentUpdate')->whereNumber('hseaccident')->name('accidents.update');
            Route::post('/accidents/{hseaccident}/notify', 'accidentNotify')->whereNumber('hseaccident')->name('accidents.notify');
            Route::post('/accidents/{hseaccident}/investigate', 'accidentInvestigate')->whereNumber('hseaccident')->name('accidents.investigate');
            Route::post('/accidents/{hseaccident}/close', 'accidentClose')->whereNumber('hseaccident')->name('accidents.close');
            Route::post('/risks', 'riskSave')->name('risks.store');
            Route::post('/risks/{hserisk}', 'riskSave')->whereNumber('hserisk')->name('risks.update');
            Route::post('/risks/{hserisk}/accept', 'riskAccept')->whereNumber('hserisk')->name('risks.accept');
            Route::post('/risks/{hserisk}/archive', 'riskArchive')->whereNumber('hserisk')->name('risks.archive');
            Route::post('/drills', 'drillStore')->name('drills.store');
            Route::post('/drills/{hsedrill}/cancel', 'drillCancel')->whereNumber('hsedrill')->name('drills.cancel');
            Route::post('/wardens', 'wardenSave')->name('wardens.store');
            Route::post('/wardens/{hsewarden}', 'wardenSave')->whereNumber('hsewarden')->name('wardens.update');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
    });

    // v7.0 sustainability & ESG: carbon (HCMI / HWMI), waste, F-gas, green stay, targets, initiatives, people & community, certification, policies, statements, annual report
    Route::controller(Controllers\EsgController::class)->prefix('esg')->name('esg.')->group(function () {
        Route::middleware('perm:esg.view,esg.record,esg.green,esg.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/carbon', 'carbon')->name('carbon');
            Route::get('/waste', 'waste')->name('waste');
            Route::get('/refrigerants', 'refrigerants')->name('refrigerants');
            Route::get('/green', 'green')->name('green');
            Route::get('/targets', 'targets')->name('targets');
            Route::get('/initiatives', 'initiatives')->name('initiatives');
            Route::get('/initiatives/{esginitiative}', 'initiative')->whereNumber('esginitiative')->name('initiative');
            Route::get('/social', 'social')->name('social');
            Route::get('/certification', 'certification')->name('certification');
            Route::get('/policies', 'policies')->name('policies');
            Route::get('/policies/{esgpolicy}/file', 'policyFile')->whereNumber('esgpolicy')->name('policy.file');
            Route::get('/community/{esgcommunity}/evidence', 'communityFile')->whereNumber('esgcommunity')->name('community.file');
            Route::get('/statements', 'statements')->name('statements');
            Route::get('/statements/print', 'statementPrint')->name('statement.print');
            Route::get('/report', 'report')->name('report');
            Route::get('/report/print', 'reportPrint')->name('report.print');
        });
        Route::middleware('perm:esg.green,esg.manage')->group(function () {
            Route::post('/green', 'greenIn')->name('green.in');
            Route::post('/green/{esggreen}/end', 'greenOut')->whereNumber('esggreen')->name('green.out');
        });
        Route::middleware('perm:esg.record,esg.manage')->group(function () {
            Route::post('/waste', 'wasteStore')->name('waste.store');
            Route::post('/waste/{esgwaste}/cancel', 'wasteCancel')->whereNumber('esgwaste')->name('waste.cancel');
            Route::post('/refrigerants', 'refrigerantStore')->name('refrigerants.store');
            Route::post('/community', 'communityStore')->name('community.store');
            Route::post('/initiatives', 'initiativeStore')->name('initiatives.store');
            Route::post('/initiatives/{esginitiative}', 'initiativeUpdate')->whereNumber('esginitiative')->name('initiatives.update');
            Route::post('/initiatives/{esginitiative}/move', 'initiativeMove')->whereNumber('esginitiative')->name('initiatives.move');
        });
        Route::middleware('perm:esg.manage')->group(function () {
            Route::post('/laundry', 'laundryStore')->name('laundry.store');
            Route::post('/targets', 'targetStore')->name('targets.store');
            Route::post('/targets/{esgtarget}', 'targetUpdate')->whereNumber('esgtarget')->name('targets.update');
            Route::post('/certification/{esgitem}', 'assess')->whereNumber('esgitem')->name('certification.assess');
            Route::post('/policies', 'policyStore')->name('policies.store');
            Route::post('/policies/{esgpolicy}', 'policyUpdate')->whereNumber('esgpolicy')->name('policies.update');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
    });
    // v7.1 IPTV & guest welcome: TVs & pairing, remote commands, channels / EPG, movie rentals, welcome screens, pages, TV messages & emergency alert
    Route::controller(Controllers\IptvController::class)->prefix('iptv')->name('iptv.')->group(function () {
        Route::middleware('perm:iptv.view,iptv.message,iptv.emergency,iptv.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/devices', 'devices')->name('devices');
            Route::get('/devices/{tvdevice}', 'device')->whereNumber('tvdevice')->name('device');
            Route::get('/channels', 'channels')->name('channels');
            Route::get('/channels/export', 'm3uExport')->name('channels.export');
            Route::get('/movies', 'movies')->name('movies');
            Route::get('/welcome', 'welcome')->name('welcome');
            Route::get('/pages', 'pages')->name('pages');
            Route::get('/messages', 'messages')->name('messages');
            Route::get('/analytics', 'analytics')->name('analytics');
        });
        Route::middleware('perm:iptv.message,iptv.manage')->group(function () {
            Route::post('/messages', 'messageStore')->name('messages.store');
            Route::post('/messages/{tvmessage}/cancel', 'messageCancel')->whereNumber('tvmessage')->name('messages.cancel');
            Route::post('/stay/{stay}/reset', 'stayReset')->whereNumber('stay')->name('stay.reset');
        });
        Route::middleware('perm:iptv.emergency,iptv.manage')->group(function () {
            Route::post('/emergency', 'emergency')->name('emergency');
            Route::post('/emergency/{tvmessage}/clear', 'emergencyClear')->whereNumber('tvmessage')->name('emergency.clear');
        });
        Route::middleware('perm:iptv.manage')->group(function () {
            Route::post('/devices', 'deviceStore')->name('devices.store');
            Route::post('/devices/pair', 'pair')->name('devices.pair');
            Route::put('/devices/{tvdevice}', 'deviceUpdate')->whereNumber('tvdevice')->name('devices.update');
            Route::post('/devices/{tvdevice}/rotate', 'rotate')->whereNumber('tvdevice')->name('devices.rotate');
            Route::post('/devices/{tvdevice}/status', 'status')->whereNumber('tvdevice')->name('devices.status');
            Route::post('/devices/{tvdevice}/command', 'command')->whereNumber('tvdevice')->name('devices.command');
            Route::delete('/devices/{tvdevice}', 'deviceDelete')->whereNumber('tvdevice')->name('devices.delete');
            Route::post('/channels', 'channelStore')->name('channels.store');
            Route::put('/channels/{tvchannel}', 'channelUpdate')->whereNumber('tvchannel')->name('channels.update');
            Route::delete('/channels/{tvchannel}', 'channelDelete')->whereNumber('tvchannel')->name('channels.delete');
            Route::post('/channels/import', 'm3uImport')->name('channels.import');
            Route::post('/channels/epg', 'epgImport')->name('channels.epg');
            Route::post('/movies', 'movieStore')->name('movies.store');
            Route::put('/movies/{tvmovie}', 'movieUpdate')->whereNumber('tvmovie')->name('movies.update');
            Route::post('/rentals/{tvpurchase}/refund', 'refund')->whereNumber('tvpurchase')->name('rentals.refund');
            Route::post('/welcome', 'templateStore')->name('welcome.store');
            Route::put('/welcome/{tvtemplate}', 'templateUpdate')->whereNumber('tvtemplate')->name('welcome.update');
            Route::post('/pages', 'pageStore')->name('pages.store');
            Route::put('/pages/{tvpage}', 'pageUpdate')->whereNumber('tvpage')->name('pages.update');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
    });

    // v7.3 guest messaging: shared inbox (portal chat, WhatsApp, SMS, e-mail), dashboard, broadcasts, quick replies, routing rules, settings
    // ---- v7.4 Partner portal (hotel side)
    Route::controller(Controllers\PartnerAdminController::class)->prefix('partner-admin')->name('partner-admin.')->group(function () {
        Route::get('/', 'dashboard')->middleware('perm:partner.view')->name('dashboard');
        Route::get('/accounts', 'accounts')->middleware('perm:partner.view')->name('accounts');
        Route::get('/accounts/{company}', 'company')->middleware('perm:partner.view')->name('company');
        Route::post('/accounts/{company}/terms', 'terms')->middleware('perm:partner.manage')->name('terms');
        Route::post('/accounts/{company}/invite', 'invite')->middleware('perm:partner.manage')->name('invite');
        Route::post('/users/{puser}', 'user')->middleware('perm:partner.manage')->name('user');
        Route::get('/requests', 'requests')->middleware('perm:partner.view')->name('requests');
        Route::post('/requests/{partnerreq}', 'decide')->middleware('perm:partner.approve')->name('decide');
        Route::get('/activity', 'activity')->middleware('perm:partner.view')->name('activity');
        Route::get('/settings', 'settings')->middleware('perm:partner.manage')->name('settings');
        Route::post('/settings', 'saveSettings')->middleware('perm:partner.manage')->name('settings.save');
    });

    // v7.5 event organiser portal (staff side): dashboard, portal events + BEO publishing, organiser logins, request queue, activity, settings
    Route::controller(Controllers\OrganiserAdminController::class)->prefix('organiser-admin')->name('organiser-admin.')->group(function () {
        Route::get('/', 'dashboard')->middleware('perm:organiser.view')->name('dashboard');
        Route::get('/events', 'events')->middleware('perm:organiser.view')->name('events');
        Route::get('/events/{bevent}', 'event')->middleware('perm:organiser.view')->name('event');
        Route::post('/events/{bevent}/portal', 'portal')->middleware('perm:organiser.manage')->name('portal');
        Route::post('/events/{bevent}/beo', 'publish')->middleware('perm:organiser.manage')->name('beo.publish');
        Route::get('/organisers', 'organisers')->middleware('perm:organiser.view')->name('organisers');
        Route::post('/organisers/invite', 'invite')->middleware('perm:organiser.manage')->name('invite');
        Route::post('/organisers/{orguser}', 'user')->middleware('perm:organiser.manage')->name('user');
        Route::get('/requests', 'requests')->middleware('perm:organiser.view')->name('requests');
        Route::post('/requests/{orgreq}', 'decide')->middleware('perm:organiser.approve')->name('decide');
        Route::get('/activity', 'activity')->middleware('perm:organiser.view')->name('activity');
        Route::get('/settings', 'settings')->middleware('perm:organiser.manage')->name('settings');
        Route::post('/settings', 'saveSettings')->middleware('perm:organiser.manage')->name('settings.save');
    });

    Route::controller(Controllers\InboxController::class)->prefix('inbox')->name('inbox.')->group(function () {
        Route::middleware('perm:inbox.view,inbox.reply,inbox.broadcast,inbox.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/poll', 'poll')->name('poll');
            Route::get('/dashboard', 'dashboard')->name('dashboard');
            Route::get('/c/{conversation}', 'show')->whereNumber('conversation')->name('show');
            Route::get('/broadcasts', 'broadcasts')->name('broadcasts');
            Route::get('/broadcasts/{broadcast}', 'broadcast')->whereNumber('broadcast')->name('broadcast');
        });
        Route::middleware('perm:inbox.reply,inbox.manage')->group(function () {
            Route::post('/start', 'start')->name('start');
            Route::get('/guests', 'guestSearch')->name('guests');
            Route::post('/c/{conversation}/reply', 'reply')->whereNumber('conversation')->middleware('throttle:60,1')->name('reply');
            Route::post('/c/{conversation}/assign', 'assign')->whereNumber('conversation')->name('assign');
            Route::post('/c/{conversation}/department', 'department')->whereNumber('conversation')->name('department');
            Route::post('/c/{conversation}/priority', 'priority')->whereNumber('conversation')->name('priority');
            Route::post('/c/{conversation}/snooze', 'snooze')->whereNumber('conversation')->name('snooze');
            Route::post('/c/{conversation}/close', 'close')->whereNumber('conversation')->name('close');
            Route::post('/c/{conversation}/reopen', 'reopen')->whereNumber('conversation')->name('reopen');
            Route::post('/c/{conversation}/request', 'raise')->whereNumber('conversation')->name('request');
            Route::post('/c/{conversation}/link', 'link')->whereNumber('conversation')->name('link');
        });
        Route::middleware('perm:inbox.broadcast,inbox.manage')->group(function () {
            Route::get('/broadcasts/preview', 'broadcastPreview')->name('broadcasts.preview');
            Route::post('/broadcasts', 'broadcastStore')->middleware('throttle:10,1')->name('broadcasts.store');
        });
        Route::middleware('perm:inbox.manage')->group(function () {
            Route::get('/quick-replies', 'replies')->name('replies');
            Route::post('/quick-replies', 'replyStore')->name('replies.store');
            Route::put('/quick-replies/{quickreply}', 'replyUpdate')->whereNumber('quickreply')->name('replies.update');
            Route::delete('/quick-replies/{quickreply}', 'replyDestroy')->whereNumber('quickreply')->name('replies.destroy');
            Route::get('/rules', 'rules')->name('rules');
            Route::post('/rules', 'ruleStore')->name('rules.store');
            Route::post('/rules/test', 'ruleTest')->name('rules.test');
            Route::put('/rules/{inboxrule}', 'ruleUpdate')->whereNumber('inboxrule')->name('rules.update');
            Route::delete('/rules/{inboxrule}', 'ruleDestroy')->whereNumber('inboxrule')->name('rules.destroy');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
            Route::post('/settings/secret', 'secret')->name('settings.secret');
        });
    });
    // v7.2 self-service kiosk: dashboard & assistance queue, kiosks & pairing, guest visits, settings
    Route::controller(Controllers\KioskAdminController::class)->prefix('self-service')->name('kiosks.')->group(function () {
        Route::middleware('perm:kiosk.view,kiosk.assist,kiosk.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/kiosks', 'kiosks')->name('devices');
            Route::get('/visits', 'sessions')->name('sessions');
            Route::get('/visits/{ksession}', 'session')->whereNumber('ksession')->name('session');
        });
        Route::middleware('perm:kiosk.assist,kiosk.manage')->group(function () {
            Route::post('/visits/{ksession}/resolve', 'resolve')->whereNumber('ksession')->name('resolve');
        });
        Route::middleware('perm:kiosk.manage')->group(function () {
            Route::post('/kiosks', 'store')->name('store');
            Route::post('/kiosks/pair', 'pair')->name('pair');
            Route::put('/kiosks/{kiosk}', 'update')->whereNumber('kiosk')->name('update');
            Route::post('/kiosks/{kiosk}/rotate', 'rotate')->whereNumber('kiosk')->name('rotate');
            Route::post('/kiosks/{kiosk}/status', 'status')->whereNumber('kiosk')->name('status');
            Route::delete('/kiosks/{kiosk}', 'destroy')->whereNumber('kiosk')->name('destroy');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
    });

    // v6.6 parking & valet: desk (entry / exit / plate lookup), valet board, tickets, vehicles, permits, zones & tariff
    Route::controller(Controllers\ParkingController::class)->prefix('parking')->name('parking.')->group(function () {
        Route::middleware('perm:parking.view,parking.operate,parking.validate,parking.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/valet', 'valet')->name('valet');
            Route::get('/tickets', 'sessions')->name('sessions');
            Route::get('/tickets/{parkingsession}', 'session')->whereNumber('parkingsession')->name('session');
            Route::get('/tickets/{parkingsession}/print', 'ticket')->whereNumber('parkingsession')->name('ticket');
            Route::get('/vehicles', 'vehicles')->name('vehicles');
            Route::get('/permits', 'permits')->name('permits');
            Route::get('/zones', 'zones')->name('zones');
        });
        Route::post('/tickets/{parkingsession}/validate', 'validateTicket')->whereNumber('parkingsession')->middleware('perm:parking.validate,parking.operate,parking.manage')->name('validate');
        Route::middleware('perm:parking.operate,parking.manage')->group(function () {
            Route::post('/check-in', 'checkIn')->name('checkin');
            Route::post('/tickets/{parkingsession}/request', 'request')->whereNumber('parkingsession')->name('request');
            Route::post('/tickets/{parkingsession}/ready', 'ready')->whereNumber('parkingsession')->name('ready');
            Route::post('/tickets/{parkingsession}/repark', 'repark')->whereNumber('parkingsession')->name('repark');
            Route::post('/tickets/{parkingsession}/move', 'move')->whereNumber('parkingsession')->name('move');
            Route::post('/tickets/{parkingsession}/exit', 'checkOut')->whereNumber('parkingsession')->name('exit');
            Route::post('/vehicles/{parkingvehicle}', 'vehicleUpdate')->whereNumber('parkingvehicle')->name('vehicle.update');
        });
        Route::middleware('perm:parking.manage')->group(function () {
            Route::post('/tickets/{parkingsession}/cancel', 'cancel')->whereNumber('parkingsession')->name('cancel');
            Route::post('/post-nightly', 'postNightly')->name('nightly');
            Route::post('/vehicles/{parkingvehicle}/ban', 'vehicleBan')->whereNumber('parkingvehicle')->name('vehicle.ban');
            Route::post('/permits', 'permitSave')->name('permits.store');
            Route::post('/permits/{parkingpermit}', 'permitSave')->whereNumber('parkingpermit')->name('permits.update');
            Route::post('/permits/{parkingpermit}/revoke', 'permitRevoke')->whereNumber('parkingpermit')->name('permits.revoke');
            Route::post('/zones', 'zoneSave')->name('zones.store');
            Route::post('/zones/{parkingzone}', 'zoneSave')->whereNumber('parkingzone')->name('zones.update');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
    });

    // v6.5 quality assurance & brand standards: standards library, checklists, audits / mystery guest, scoring, corrective actions
    Route::controller(Controllers\QaController::class)->prefix('qa')->name('qa.')->group(function () {
        Route::middleware('perm:qa.view,qa.audit,qa.manage')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/audits', 'audits')->name('audits');
            Route::get('/audits/{qaaudit}', 'audit')->whereNumber('qaaudit')->name('audit');
            Route::get('/audits/{qaaudit}/print', 'auditPrint')->whereNumber('qaaudit')->name('audit.print');
            Route::post('/audits/{qaaudit}/acknowledge', 'acknowledge')->whereNumber('qaaudit')->name('audit.acknowledge');
            Route::get('/actions', 'actions')->name('actions');
            Route::post('/actions/{qaaction}', 'actionUpdate')->whereNumber('qaaction')->name('action.update');
            Route::post('/actions/{qaaction}/done', 'actionDone')->whereNumber('qaaction')->name('action.done');
            Route::post('/actions/{qaaction}/ticket', 'actionTicket')->whereNumber('qaaction')->name('action.ticket');
            Route::get('/standards', 'standards')->name('standards');
            Route::get('/checklists', 'templates')->name('templates');
        });
        Route::middleware('perm:qa.audit,qa.manage')->group(function () {
            Route::post('/audits', 'schedule')->name('audits.schedule');
            Route::post('/audits/{qaaudit}/record', 'record')->whereNumber('qaaudit')->name('audit.record');
            Route::post('/actions', 'actionStore')->name('actions.store');
        });
        Route::middleware('perm:qa.manage')->group(function () {
            Route::post('/audits/{qaaudit}/cancel', 'cancel')->whereNumber('qaaudit')->name('audit.cancel');
            Route::post('/actions/{qaaction}/verify', 'actionVerify')->whereNumber('qaaction')->name('action.verify');
            Route::post('/actions/{qaaction}/cancel', 'actionCancel')->whereNumber('qaaction')->name('action.cancel');
            Route::post('/standards', 'standardSave')->name('standards.save');
            Route::post('/checklists', 'templateSave')->name('templates.save');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'settingsSave')->name('settings.save');
        });
    });

    Route::controller(Controllers\BusinessDevController::class)->prefix('bd')->name('bd.')->group(function () {
        Route::middleware('perm:bd.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/compset', 'compset')->name('compset');
            Route::get('/competitors/{bdcompetitor}', 'competitor')->whereNumber('bdcompetitor')->name('competitor');
            Route::get('/market', 'market')->name('market');
            Route::get('/partners', 'partners')->name('partners');
            Route::get('/partners/{bdpartner}', 'partner')->whereNumber('bdpartner')->name('partner');
            Route::get('/initiatives', 'initiatives')->name('initiatives');
            Route::get('/initiatives/{bdinitiative}', 'initiative')->whereNumber('bdinitiative')->name('initiative');
            Route::get('/initiatives/{bdinitiative}/print', 'initiativePrint')->whereNumber('bdinitiative')->name('initiative.print');
        });
        Route::middleware('perm:bd.edit,bd.approve,bd.manage')->group(function () {
            Route::post('/shops', 'shopStore')->name('shops.store');
            Route::post('/shops/{bdshop}/delete', 'shopDelete')->whereNumber('bdshop')->name('shop.delete');
            Route::post('/benchmarks', 'benchmarkSave')->name('benchmarks.save');
            Route::post('/partners', 'partnerStore')->name('partners.store');
            Route::put('/partners/{bdpartner}', 'partnerUpdate')->whereNumber('bdpartner')->name('partner.update');
            Route::post('/partners/{bdpartner}/status', 'partnerStatus')->whereNumber('bdpartner')->name('partner.status');
            Route::post('/initiatives', 'initiativeStore')->name('initiatives.store');
            Route::put('/initiatives/{bdinitiative}', 'initiativeUpdate')->whereNumber('bdinitiative')->name('initiative.update');
            Route::post('/initiatives/{bdinitiative}/stage', 'initiativeStage')->whereNumber('bdinitiative')->name('initiative.stage');
            Route::post('/initiatives/{bdinitiative}/submit', 'initiativeSubmit')->whereNumber('bdinitiative')->name('initiative.submit');
            Route::post('/initiatives/{bdinitiative}/decide', 'initiativeDecide')->whereNumber('bdinitiative')->name('initiative.decide');
            Route::post('/initiatives/{bdinitiative}/milestones', 'milestoneStore')->whereNumber('bdinitiative')->name('milestones.store');
            Route::post('/milestones/{bdmilestone}/toggle', 'milestoneToggle')->whereNumber('bdmilestone')->name('milestone.toggle');
            Route::post('/milestones/{bdmilestone}/delete', 'milestoneDelete')->whereNumber('bdmilestone')->name('milestone.delete');
            Route::post('/initiatives/{bdinitiative}/results', 'resultSave')->whereNumber('bdinitiative')->name('initiative.results');
        });
        Route::middleware('perm:bd.manage')->group(function () {
            Route::post('/competitors', 'competitorStore')->name('competitors.store');
            Route::put('/competitors/{bdcompetitor}', 'competitorUpdate')->whereNumber('bdcompetitor')->name('competitor.update');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'settingsSave')->name('settings.save');
        });
    });

    Route::controller(Controllers\DigitalMarketingController::class)->prefix('digital')->name('digital.')->group(function () {
        Route::middleware('perm:digital.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/calendar', 'calendar')->name('calendar');
            Route::get('/posts/{dmpost}', 'post')->whereNumber('dmpost')->name('post');
            Route::get('/campaigns', 'campaigns')->name('campaigns');
            Route::get('/campaigns/{dmcampaign}', 'campaign')->whereNumber('dmcampaign')->name('campaign');
            Route::get('/links', 'links')->name('links');
            Route::get('/links/{dmlink}', 'link')->whereNumber('dmlink')->name('link');
            Route::get('/links/{dmlink}/qr', 'qr')->whereNumber('dmlink')->name('link.qr');
            Route::get('/channels', 'channels')->name('channels');
            Route::get('/channels/{dmchannel}', 'channel')->whereNumber('dmchannel')->name('channel');
            Route::get('/influencers', 'influencers')->name('influencers');
            Route::get('/influencers/{dminfluencer}', 'influencer')->whereNumber('dminfluencer')->name('influencer');
        });
        Route::middleware('perm:digital.content,digital.approve,digital.manage')->group(function () {
            Route::post('/posts', 'postStore')->name('posts.store');
            Route::put('/posts/{dmpost}', 'postUpdate')->whereNumber('dmpost')->name('post.update');
            Route::post('/posts/{dmpost}/submit', 'postSubmit')->whereNumber('dmpost')->name('post.submit');
            Route::post('/posts/{dmpost}/review', 'postReview')->whereNumber('dmpost')->name('post.review');
            Route::post('/posts/{dmpost}/publish', 'postPublish')->whereNumber('dmpost')->name('post.publish');
            Route::post('/posts/{dmpost}/stats', 'postStats')->whereNumber('dmpost')->name('post.stats');
            Route::post('/posts/{dmpost}/cancel', 'postCancel')->whereNumber('dmpost')->name('post.cancel');
            Route::post('/links', 'linkStore')->name('links.store');
            Route::put('/links/{dmlink}', 'linkUpdate')->whereNumber('dmlink')->name('link.update');
            Route::post('/links/{dmlink}/toggle', 'linkToggle')->whereNumber('dmlink')->name('link.toggle');
            Route::post('/channels/{dmchannel}/stats', 'channelStats')->whereNumber('dmchannel')->name('channel.stats');
            Route::post('/channels/{dmchannel}/audit', 'channelAudit')->whereNumber('dmchannel')->name('channel.audit');
            Route::post('/influencers/{dminfluencer}/results', 'influencerResults')->whereNumber('dminfluencer')->name('influencer.results');
        });
        Route::middleware('perm:digital.manage')->group(function () {
            Route::post('/channels', 'channelStore')->name('channels.store');
            Route::put('/channels/{dmchannel}', 'channelUpdate')->whereNumber('dmchannel')->name('channel.update');
            Route::post('/campaigns', 'campaignStore')->name('campaigns.store');
            Route::put('/campaigns/{dmcampaign}', 'campaignUpdate')->whereNumber('dmcampaign')->name('campaign.update');
            Route::post('/campaigns/{dmcampaign}/status', 'campaignStatus')->whereNumber('dmcampaign')->name('campaign.status');
            Route::post('/campaigns/{dmcampaign}/results', 'adStat')->whereNumber('dmcampaign')->name('campaign.adstat');
            Route::post('/campaigns/{dmcampaign}/import', 'import')->whereNumber('dmcampaign')->name('campaign.import');
            Route::post('/ad-results/{dmadstat}/delete', 'adStatDelete')->whereNumber('dmadstat')->name('adstat.delete');
            Route::post('/influencers', 'influencerStore')->name('influencers.store');
            Route::put('/influencers/{dminfluencer}', 'influencerUpdate')->whereNumber('dminfluencer')->name('influencer.update');
            Route::post('/influencers/{dminfluencer}/status', 'influencerStatus')->whereNumber('dminfluencer')->name('influencer.status');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'settingsSave')->name('settings.save');
        });
    });

    // ---- v6.1 Sales & account management: accounts, contacts, opportunity pipeline, sales diary, targets
    Route::controller(Controllers\SalesController::class)->prefix('sales')->name('sales.')->group(function () {
        Route::middleware('perm:sales.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/accounts', 'accounts')->name('accounts');
            Route::get('/accounts/{saccount}', 'account')->whereNumber('saccount')->name('account');
            Route::get('/opportunities', 'opportunities')->name('opportunities');
            Route::get('/opportunities/{opp}', 'opportunity')->whereNumber('opp')->name('opportunity');
            Route::get('/opportunities/{opp}/proposal', 'proposal')->whereNumber('opp')->name('proposal');
            Route::get('/activities', 'activities')->name('activities');
            Route::get('/targets', 'targets')->name('targets');
        });
        Route::middleware('perm:sales.edit,sales.manage')->group(function () {
            Route::post('/accounts', 'accountStore')->name('accounts.store');
            Route::put('/accounts/{saccount}', 'accountUpdate')->whereNumber('saccount')->name('account.update');
            Route::post('/accounts/{saccount}/contacts', 'contactStore')->whereNumber('saccount')->name('contacts.store');
            Route::put('/contacts/{scontact}', 'contactUpdate')->whereNumber('scontact')->name('contact.update');
            Route::post('/contacts/{scontact}/delete', 'contactDelete')->whereNumber('scontact')->name('contact.delete');
            Route::post('/opportunities', 'opportunityStore')->name('opportunities.store');
            Route::put('/opportunities/{opp}', 'opportunityUpdate')->whereNumber('opp')->name('opportunity.update');
            Route::post('/opportunities/{opp}/stage', 'stage')->whereNumber('opp')->name('opportunity.stage');
            Route::post('/opportunities/{opp}/reopen', 'reopen')->whereNumber('opp')->name('opportunity.reopen');
            Route::post('/opportunities/{opp}/link', 'link')->whereNumber('opp')->name('opportunity.link');
            Route::post('/opportunities/{opp}/contract', 'draftContract')->whereNumber('opp')->name('opportunity.contract');
            Route::post('/activities', 'activityStore')->name('activities.store');
            Route::put('/activities/{sact}', 'activityUpdate')->whereNumber('sact')->name('activity.update');
            Route::post('/activities/{sact}/complete', 'complete')->whereNumber('sact')->name('activity.complete');
            Route::post('/activities/{sact}/cancel', 'cancel')->whereNumber('sact')->name('activity.cancel');
        });
        Route::middleware('perm:sales.manage')->group(function () {
            Route::post('/targets', 'saveTargets')->name('targets.save');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
        });
    });

    // ---- v5.9 Concierge: activities & excursions sold as agent, departures, vendors, local guide, settlements
    Route::controller(Controllers\ConciergeController::class)->prefix('concierge')->name('concierge.')->group(function () {
        Route::middleware('perm:concierge.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/bookings', 'bookings')->name('bookings');
            Route::get('/bookings/{cbooking}', 'show')->whereNumber('cbooking')->name('booking');
            Route::get('/bookings/{cbooking}/voucher', 'voucher')->whereNumber('cbooking')->name('voucher');
            Route::get('/departures/{cdeparture}/manifest', 'manifest')->whereNumber('cdeparture')->name('manifest');
            Route::get('/slots', 'slots')->name('slots');
            Route::get('/catalogue', 'catalogue')->name('catalogue');
            Route::get('/guide', 'guide')->name('guide');
            Route::get('/guide/print', 'guidePrint')->name('guide.print');
            Route::get('/settlements', 'settlements')->name('settlements');
            Route::get('/settlements/{csettle}', 'settlement')->whereNumber('csettle')->name('settlement');
        });
        Route::middleware('perm:concierge.book')->group(function () {
            Route::get('/bookings/new', 'create')->name('create');
            Route::post('/bookings', 'store')->name('store');
            Route::put('/bookings/{cbooking}', 'update')->whereNumber('cbooking')->name('update');
            Route::post('/bookings/{cbooking}/confirm', 'confirm')->whereNumber('cbooking')->name('confirm');
            Route::post('/bookings/{cbooking}/complete', 'complete')->whereNumber('cbooking')->name('complete');
            Route::post('/bookings/{cbooking}/no-show', 'noShow')->whereNumber('cbooking')->name('noshow');
            Route::post('/bookings/{cbooking}/cancel', 'cancel')->whereNumber('cbooking')->name('cancel');
            Route::post('/bookings/{cbooking}/send', 'sendVoucher')->whereNumber('cbooking')->middleware('throttle:10,1')->name('send');
            Route::put('/departures/{cdeparture}', 'departureUpdate')->whereNumber('cdeparture')->name('departure.update');
            Route::post('/departures/{cdeparture}/cancel', 'departureCancel')->whereNumber('cdeparture')->name('departure.cancel');
        });
        Route::middleware('perm:concierge.settle')->group(function () {
            Route::post('/settlements', 'settle')->name('settle');
            Route::post('/settlements/{csettle}/void', 'settlementVoid')->whereNumber('csettle')->name('settlement.void');
        });
        Route::middleware('perm:concierge.manage')->group(function () {
            Route::post('/activities', 'activityStore')->name('activity.store');
            Route::put('/activities/{cactivity}', 'activityUpdate')->whereNumber('cactivity')->name('activity.update');
            Route::delete('/activities/{cactivity}', 'activityDelete')->whereNumber('cactivity')->name('activity.delete');
            Route::post('/vendors', 'vendorStore')->name('vendor.store');
            Route::put('/vendors/{cvendor}', 'vendorUpdate')->whereNumber('cvendor')->name('vendor.update');
            Route::delete('/vendors/{cvendor}', 'vendorDelete')->whereNumber('cvendor')->name('vendor.delete');
            Route::post('/guide', 'placeStore')->name('place.store');
            Route::put('/guide/{cplace}', 'placeUpdate')->whereNumber('cplace')->name('place.update');
            Route::delete('/guide/{cplace}', 'placeDelete')->whereNumber('cplace')->name('place.delete');
            Route::get('/settings', 'settings')->name('settings');
            Route::put('/settings', 'saveSettings')->name('settings.save');
        });
    });

    Route::controller(Controllers\OwnerController::class)->prefix('owner')->name('owner.')->group(function () {
        Route::middleware('perm:owner.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/usali', 'usali')->name('usali');
            Route::get('/usali/{dept}', 'dept')->where('dept', '[a-z_]+')->name('usali.dept');
            Route::get('/fees', 'fees')->name('fees');
            Route::get('/reserve', 'reserve')->name('reserve');
            Route::get('/capex/{capex}', 'capexShow')->whereNumber('capex')->name('capex.show');
            Route::get('/statements', 'statements')->name('statements');
            Route::get('/statements/{period}', 'statementShow')->where('period', '\d{4}-\d{2}')->name('statements.show');
        });
        Route::middleware('perm:owner.operate')->group(function () {
            Route::post('/fees/accrue', 'accrue')->name('fees.accrue');
            Route::post('/fees/{ofee}/reverse', 'reverseFee')->name('fees.reverse');
            Route::post('/fees/pay', 'pay')->name('fees.pay');
            Route::post('/reserve/entries', 'entry')->name('reserve.entry');
            Route::delete('/reserve/entries/{ffe}', 'deleteEntry')->name('reserve.entry.delete');
            Route::post('/capex', 'capexStore')->name('capex.store');
            Route::put('/capex/{capex}', 'capexUpdate')->name('capex.update');
            Route::post('/capex/{capex}/submit', 'capexSubmit')->name('capex.submit');
            Route::post('/capex/{capex}/close/{to}', 'capexClose')->where('to', 'completed|cancelled')->name('capex.close');
            Route::post('/capex/{capex}/assets', 'capexLink')->name('capex.link');
            Route::delete('/capex/{capex}/assets/{asset}', 'capexUnlink')->name('capex.unlink');
            Route::post('/statements', 'statementPrepare')->name('statements.prepare');
            Route::put('/statements/{ostmt}/commentary', 'statementUpdate')->name('statements.update');
            Route::post('/statements/{ostmt}/issue', 'statementIssue')->name('statements.issue');
            Route::post('/queries/{oquery}/reply', 'queryReply')->name('queries.reply');
        });
        Route::middleware('perm:owner.approve')->group(function () {
            Route::post('/capex/{capex}/approve', 'capexApprove')->name('capex.approve');
            Route::post('/capex/{capex}/reject', 'capexReject')->name('capex.reject');
            Route::post('/statements/{ostmt}/acknowledge', 'statementAck')->name('statements.ack');
            Route::post('/statements/{ostmt}/queries', 'queryStore')->name('queries.store');
        });
        Route::middleware('perm:owner.approve,owner.operate')->post('/queries/{oquery}/close', 'queryClose')->name('queries.close');
        Route::middleware('perm:owner.manage')->group(function () {
            Route::get('/setup', 'setup')->name('setup');
            Route::put('/setup/agreement', 'agreementSave')->name('setup.agreement');
            Route::put('/setup/mapping', 'mappingSave')->name('setup.mapping');
            Route::put('/setup/departments', 'deptMappingSave')->name('setup.departments');
        });
    });

    // v5.4 report automation – saved views, schedules, KPI alerts (defined before /reports/{report})
    Route::controller(Controllers\ReportAutomationController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::middleware('perm:reports.view')->group(function () {
            Route::get('/library', 'library')->name('library');
            Route::post('/views', 'storeView')->name('views.store');
            Route::put('/views/{rview}', 'updateView')->whereNumber('rview')->name('views.update');
            Route::post('/views/{rview}/pin', 'pinView')->whereNumber('rview')->name('views.pin');
            Route::delete('/views/{rview}', 'destroyView')->whereNumber('rview')->name('views.destroy');
        });
        Route::middleware('perm:reports.schedule')->group(function () {
            Route::get('/schedules', 'schedules')->name('schedules');
            Route::post('/schedules', 'storeSchedule')->name('schedules.store');
            Route::put('/schedules/{rschedule}', 'updateSchedule')->whereNumber('rschedule')->name('schedules.update');
            Route::post('/schedules/{rschedule}/run', 'runSchedule')->whereNumber('rschedule')->name('schedules.run');
            Route::post('/schedules/{rschedule}/toggle', 'toggleSchedule')->whereNumber('rschedule')->name('schedules.toggle');
            Route::delete('/schedules/{rschedule}', 'destroySchedule')->whereNumber('rschedule')->name('schedules.destroy');
            Route::get('/deliveries/{rdelivery}/csv', 'deliveryCsv')->whereNumber('rdelivery')->name('deliveries.csv');
            Route::post('/schedules/settings', 'settings')->name('schedules.settings');
        });
        Route::middleware('perm:reports.alerts')->group(function () {
            Route::get('/alerts', 'alerts')->name('alerts');
            Route::post('/alerts', 'storeAlert')->name('alerts.store');
            Route::put('/alerts/{kpialert}', 'updateAlert')->whereNumber('kpialert')->name('alerts.update');
            Route::post('/alerts/{kpialert}/toggle', 'toggleAlert')->whereNumber('kpialert')->name('alerts.toggle');
            Route::delete('/alerts/{kpialert}', 'destroyAlert')->whereNumber('kpialert')->name('alerts.destroy');
            Route::post('/alerts/evaluate', 'evaluate')->name('alerts.evaluate');
            Route::post('/alerts/events/{kpievent}/ack', 'acknowledge')->whereNumber('kpievent')->name('alerts.ack');
        });
    });
    // v5.5 rate packages, meal plans & inclusions
    Route::controller(Controllers\PackageController::class)->prefix('packages')->name('packages.')->group(function () {
        Route::middleware('perm:packages.view')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/register', 'register')->name('register');
            Route::get('/catalogue', 'catalogue')->name('catalogue');
        });
        Route::middleware('perm:packages.redeem')->group(function () {
            Route::get('/desk', 'desk')->name('desk');
            Route::post('/desk', 'redeem')->name('redeem');
        });
        Route::post('/folios/{folio}/add', 'addOn')->middleware('perm:packages.sell')->name('addon');
        Route::middleware('perm:packages.manage')->group(function () {
            Route::post('/redemptions/{pkgredeem}/void', 'void')->whereNumber('pkgredeem')->name('void');
            Route::post('/catalogue', 'store')->name('store');
            Route::put('/catalogue/{pkgitem}', 'update')->whereNumber('pkgitem')->name('update');
            Route::post('/catalogue/{pkgitem}/toggle', 'toggle')->whereNumber('pkgitem')->name('toggle');
            Route::post('/inclusions', 'inclusions')->name('inclusions');
            Route::post('/settings', 'settings')->name('settings');
        });
    });

    // Daily briefings — a pack per head of department and the consolidated GM/CEO brief.
    // Each screen checks the permission of the pack it renders, so a head sees only their own.
    Route::controller(Controllers\BriefingController::class)->prefix('briefings')->name('briefings.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/executive', 'exec')->name('exec');
        Route::get('/executive/print', 'execPrint')->name('exec.print');
        Route::get('/executive/csv', 'execCsv')->name('exec.csv');
        Route::get('/book', 'book')->name('book');
        Route::post('/{pack}/sign', 'sign')->where('pack', '[a-z]+')->name('sign');
        Route::get('/{pack}/print', 'print')->where('pack', '[a-z]+')->name('print');
        Route::get('/{pack}/csv', 'packCsv')->where('pack', '[a-z]+')->name('csv');
        Route::get('/{pack}', 'show')->where('pack', '[a-z]+')->name('show');
    });

    Route::get('/reports', [Controllers\ReportController::class, 'index'])->middleware('perm:reports.view')->name('reports.index');
    Route::get('/reports/{report}', [Controllers\ReportController::class, 'show'])->middleware('perm:reports.view,folio.shift_all')->name('reports.show');

    // ---- Generic masters (config-driven CRUD)
    Route::prefix('masters/{master}')->name('masters.')->controller(Controllers\MasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::patch('/{id}/availability', 'toggleAvailability')->name('availability');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        // photos on the record itself (rooms): add, choose the primary, take one off
        Route::post('/{id}/photos', 'storePhoto')->whereNumber('id')->name('photos.store');
        Route::post('/{id}/photos/{image}/primary', 'photoPrimary')->whereNumber('id')->whereNumber('image')->name('photos.primary');
        Route::post('/{id}/photos/{image}/hide', 'photoHide')->whereNumber('id')->whereNumber('image')->name('photos.hide');
    });

    // ---- Administration
    Route::middleware('perm:users.manage')->group(function () {
        Route::resource('users', Controllers\UserController::class)->except(['show', 'destroy']);
        Route::post('/users/{id}/two-factor/reset', [Controllers\Auth\TwoFactorController::class, 'reset'])->whereNumber('id')->name('users.2fa.reset');
        Route::resource('roles', Controllers\RoleController::class)->except(['show']);
    });
    // ---- Settings › Configuration: the register of every module's settings surface.
    // Read-only by design — each value is changed on the screen that owns it, so the
    // module keeps its own validation, permission and audit trail.
    Route::middleware('perm:settings.view')->prefix('configuration')->name('configuration.')->controller(Controllers\ConfigurationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/print', 'print')->name('print');
        Route::get('/export', 'csv')->name('csv');
        // before /{module}: "changed" is a page, not a module key
        Route::get('/changed', 'changed')->name('changed');
        Route::get('/on-null', 'onNull')->name('on-null');
        Route::get('/history', 'history')->name('history');
        Route::get('/handover', 'handover')->name('handover');
        // the one write in this area: put a setting back to a value the property recorded
        Route::middleware('perm:settings.manage')->group(function () {
            Route::post('/history/{change}/restore', 'restore')->whereNumber('change')->name('restore');
        });
        Route::get('/{module}', 'show')->where('module', '[a-z0-9_]+')->name('show');
    });
    Route::middleware('perm:settings.manage')->group(function () {
        Route::get('/settings', [Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [Controllers\SettingController::class, 'update'])->name('settings.update');
        Route::get('/settings/backup', [Controllers\SettingController::class, 'backup'])->name('settings.backup');
        Route::post('/settings/backup', [Controllers\SettingController::class, 'download'])->name('settings.backup.download');
        // ---- v7.35 Data migration & cutover
        Route::prefix('settings/migration')->name('migration.')->controller(Controllers\DataMigrationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/template/{dataset}', 'template')->name('template');
            Route::get('/export/{dataset}', 'export')->name('export');
            Route::get('/{batch}', 'show')->whereNumber('batch')->name('show');
            Route::post('/{batch}/validate', 'validate')->whereNumber('batch')->name('validate');
            Route::post('/{batch}/apply', 'apply')->whereNumber('batch')->name('apply');
        });
        Route::post('/settings/properties', [Controllers\SettingController::class, 'storeHotel'])->name('settings.hotels.store');
        Route::get('/settings/messaging', [Controllers\MessageController::class, 'settings'])->name('messages.settings');
        Route::post('/settings/messaging', [Controllers\MessageController::class, 'saveSettings'])->name('messages.settings.save');
        Route::post('/settings/messaging/test', [Controllers\MessageController::class, 'test'])->middleware('throttle:10,1')->name('messages.test');
    });
    // ---- v5.0 Food Safety & HACCP
    Route::prefix('food-safety')->name('fs.')->controller(Controllers\FoodSafetyController::class)->group(function () {
        Route::get('/', 'index')->middleware('perm:foodsafety.view')->name('index');
        Route::get('/temperatures', 'temps')->middleware('perm:foodsafety.view')->name('temps');
        Route::post('/temperatures', 'record')->middleware('perm:foodsafety.record')->name('temps.store');
        Route::post('/temperatures/{fslog}/cooling', 'cooling')->middleware('perm:foodsafety.record')->name('temps.cooling');
        Route::post('/temperatures/{fslog}/review', 'review')->middleware('perm:foodsafety.manage')->name('temps.review');
        Route::post('/equipment', 'saveEquipment')->middleware('perm:foodsafety.manage')->name('equipment.store');
        Route::put('/equipment/{fsequip}', 'saveEquipment')->middleware('perm:foodsafety.manage')->name('equipment.update');
        Route::get('/checklists', 'checklists')->middleware('perm:foodsafety.view')->name('checklists');
        Route::post('/checklists', 'saveChecklist')->middleware('perm:foodsafety.manage')->name('checklists.store');
        Route::put('/checklists/{fslist}', 'saveChecklist')->middleware('perm:foodsafety.manage')->name('checklists.update');
        Route::get('/checklists/{fslist}/run', 'runForm')->middleware('perm:foodsafety.record')->name('checklists.run');
        Route::post('/checklists/{fslist}/run', 'complete')->middleware('perm:foodsafety.record')->name('checklists.complete');
        Route::get('/checklist-runs/{fsrun}', 'showRun')->middleware('perm:foodsafety.view')->name('checklists.show');
        Route::post('/checklist-runs/{fsrun}/sign', 'sign')->middleware('perm:foodsafety.manage')->name('checklists.sign');
        Route::get('/allergens', 'allergens')->middleware('perm:foodsafety.view')->name('allergens');
        Route::post('/allergens/menu', 'saveMenuAllergens')->middleware('perm:foodsafety.manage')->name('allergens.menu');
        Route::post('/allergens/ingredients', 'saveIngredientAllergens')->middleware('perm:foodsafety.manage')->name('allergens.ingredients');
        Route::get('/allergens/print', 'allergenPrint')->middleware('perm:foodsafety.view')->name('allergens.print');
        Route::get('/waste', 'waste')->middleware('perm:foodsafety.view')->name('waste');
        Route::post('/waste', 'storeWaste')->middleware('perm:foodsafety.record')->name('waste.store');
        Route::post('/waste/{fswaste}/cancel', 'cancelWaste')->middleware('perm:foodsafety.manage')->name('waste.cancel');
        Route::get('/certificates', 'certs')->middleware('perm:foodsafety.view')->name('certs');
        Route::post('/certificates', 'storeCert')->middleware('perm:foodsafety.certs')->name('certs.store');
        Route::get('/labels', 'labels')->middleware('perm:foodsafety.record')->name('labels');
        Route::post('/labels', 'storeLabels')->middleware('perm:foodsafety.record')->name('labels.store');
        Route::get('/labels/{fslabel}/print', 'printLabels')->middleware('perm:foodsafety.record')->name('labels.print');
        Route::post('/settings', 'settings')->middleware('perm:foodsafety.manage')->name('settings');
        Route::get('/inspection-pack', 'pack')->middleware('perm:foodsafety.view')->name('pack');
    });

    // ---- v5.1 Health Club & Memberships
    Route::prefix('club')->name('club.')->group(function () {
        Route::controller(Controllers\ClubController::class)->group(function () {
            Route::get('/', 'index')->middleware('perm:club.view')->name('index');
            Route::get('/desk', 'desk')->middleware('perm:club.desk')->name('desk');
            Route::post('/desk/check-in', 'checkIn')->middleware('perm:club.desk')->name('checkin');
            Route::post('/desk/guest', 'guest')->middleware('perm:club.desk')->name('guest');
            Route::post('/desk/resident', 'resident')->middleware('perm:club.desk')->name('resident');
            Route::post('/desk/ticket', 'ticket')->middleware('perm:club.desk')->name('ticket');
            Route::post('/visits/{clubvisit}/check-out', 'checkOut')->middleware('perm:club.desk')->name('checkout');
            Route::get('/visits', 'visits')->middleware('perm:club.view')->name('visits');
            Route::get('/members', 'members')->middleware('perm:club.view')->name('members');
            Route::get('/members/enrol', 'create')->middleware('perm:club.members')->name('members.create');
            Route::post('/members', 'store')->middleware('perm:club.members')->name('members.store');
            Route::get('/members/{membership}', 'show')->middleware('perm:club.view')->name('members.show');
            Route::put('/members/{membership}', 'update')->middleware('perm:club.members')->name('members.update');
            Route::post('/members/{membership}/persons', 'addPerson')->middleware('perm:club.members')->name('persons.store');
            Route::put('/persons/{clubmember}', 'updatePerson')->middleware('perm:club.members')->name('persons.update');
            Route::post('/persons/{clubmember}/photo', 'photo')->middleware('perm:club.members')->name('persons.photo');
            Route::get('/persons/{clubmember}/photo', 'photoShow')->middleware('perm:club.view')->name('persons.photo.show');
            Route::post('/persons/{clubmember}/new-card', 'newCard')->middleware('perm:club.members')->name('persons.card');
            Route::get('/members/{membership}/cards', 'cards')->middleware('perm:club.view')->name('members.cards');
            Route::post('/members/{membership}/freeze', 'freeze')->middleware('perm:club.members')->name('members.freeze');
            Route::post('/members/{membership}/unfreeze', 'unfreeze')->middleware('perm:club.members')->name('members.unfreeze');
            Route::post('/members/{membership}/cancel', 'cancel')->middleware('perm:club.manage')->name('members.cancel');
            Route::post('/members/{membership}/withdraw-notice', 'withdrawNotice')->middleware('perm:club.members')->name('members.withdraw');
            Route::get('/passes', 'passes')->middleware('perm:club.view')->name('passes');
            Route::post('/passes', 'sellPass')->middleware('perm:club.desk')->name('passes.store');
            Route::post('/passes/{daypass}/cancel', 'cancelPass')->middleware('perm:club.desk')->name('passes.cancel');
            Route::get('/plans', 'plans')->middleware('perm:club.view')->name('plans');
            Route::post('/plans', 'savePlan')->middleware('perm:club.manage')->name('plans.store');
            Route::put('/plans/{clubplan}', 'savePlan')->middleware('perm:club.manage')->name('plans.update');
            Route::post('/settings', 'settings')->middleware('perm:club.manage')->name('settings');
            Route::post('/nightly', 'nightly')->middleware('perm:club.manage')->name('nightly');
        });
        Route::controller(Controllers\ClubBillingController::class)->group(function () {
            Route::get('/billing', 'index')->middleware('perm:club.view')->name('billing');
            Route::post('/billing/renewal-run', 'renewalRun')->middleware('perm:club.members')->name('billing.run');
            Route::post('/members/{membership}/renew', 'renew')->middleware('perm:club.members')->name('members.renew');
            Route::post('/members/{membership}/pay', 'pay')->middleware('perm:club.members')->name('members.pay');
            Route::post('/members/{membership}/refund', 'refund')->middleware('perm:club.manage')->name('members.refund');
            Route::post('/members/{membership}/charge', 'charge')->middleware('perm:club.members')->name('members.charge');
            Route::get('/members/{membership}/statement', 'statement')->middleware('perm:club.view')->name('members.statement');
            Route::post('/charges/{clubcharge}/cancel', 'cancelCharge')->middleware('perm:club.manage')->name('charges.cancel');
            Route::post('/charges/{clubcharge}/credit', 'credit')->middleware('perm:club.manage')->name('charges.credit');
            Route::get('/charges/{clubcharge}/invoice', 'invoice')->middleware('perm:club.view')->name('charges.invoice');
            Route::get('/receipts/{clubpay}', 'receipt')->middleware('perm:club.view')->name('receipts.show');
            Route::post('/receipts/{clubpay}/void', 'voidReceipt')->middleware('perm:club.manage')->name('receipts.void');
        });
        Route::controller(Controllers\ClubClassController::class)->group(function () {
            Route::get('/classes', 'index')->middleware('perm:club.view')->name('classes');
            Route::post('/classes', 'save')->middleware('perm:club.manage')->name('classes.store');
            Route::put('/classes/{clubclass}', 'save')->middleware('perm:club.manage')->name('classes.update');
            Route::get('/classes/{clubclass}/{date}', 'session')->middleware('perm:club.view')->where('date', '\d{4}-\d{2}-\d{2}')->name('classes.session');
            Route::post('/classes/{clubclass}/{date}/book', 'book')->middleware('perm:club.desk')->where('date', '\d{4}-\d{2}-\d{2}')->name('classes.book');
            Route::post('/classes/{clubclass}/{date}/attendance', 'attendance')->middleware('perm:club.desk')->where('date', '\d{4}-\d{2}-\d{2}')->name('classes.attendance');
            Route::post('/bookings/{clubbooking}/cancel', 'cancel')->middleware('perm:club.desk')->name('bookings.cancel');
        });
    });

    // ---- v7.38 Recreation ticketing (POS-style outlet, Wellness & Recreation)
    Route::prefix('recreation')->name('recreation.')->group(function () {
        Route::controller(Controllers\RecreationController::class)->group(function () {
            Route::get('/outlet', 'outlet')->middleware('perm:club.desk')->name('outlet');
            Route::post('/outlet/sell', 'sell')->middleware('perm:club.desk')->name('sell');
            // outlet cash desk: the shift that holds the takings, and the day (Z) report
            Route::get('/shift', 'shift')->middleware('perm:club.desk')->name('shift');
            Route::post('/shift/open', 'openShift')->middleware('perm:club.desk')->name('shift.open');
            Route::post('/shift/close', 'closeShift')->middleware('perm:club.desk')->name('shift.close');
            Route::post('/settings', 'saveSettings')->middleware('perm:club.manage')->name('settings');
            // the gate: admit the ticket the counter sold
            Route::get('/gate', 'gate')->middleware('perm:club.desk')->name('gate');
            Route::post('/gate/redeem', 'redeem')->middleware('perm:club.desk')->name('gate.redeem');
            Route::post('/gate/{daypass}/reverse', 'reverseAdmission')->middleware('perm:club.manage')->name('gate.reverse');
            Route::get('/day', 'day')->middleware('perm:club.view')->name('day');
            Route::get('/day/print', 'dayPrint')->middleware('perm:club.view')->name('day.print');
            Route::get('/sales', 'sales')->middleware('perm:club.view')->name('sales');
            Route::get('/sales/{sale}/receipt', 'receipt')->middleware('perm:club.view')->name('receipt');
            Route::post('/sales/{sale}/void', 'void')->middleware('perm:club.manage')->name('void');
            Route::get('/types', 'types')->middleware('perm:club.manage')->name('types');
            Route::post('/types', 'saveType')->middleware('perm:club.manage')->name('types.store');
            Route::put('/types/{type}', 'saveType')->middleware('perm:club.manage')->name('types.update');
        });
    });

    // ---- v5.3 Income Audit & Revenue Integrity
    Route::prefix('income-audit')->name('audit.')->group(function () {
        Route::controller(Controllers\IncomeAuditController::class)->group(function () {
            Route::get('/', 'index')->middleware('perm:income_audit.view')->name('index');
            Route::post('/start', 'start')->middleware('perm:income_audit.audit')->name('start');
            Route::post('/settings', 'settings')->middleware('perm:income_audit.close')->name('settings');
            Route::get('/days/{auditday}', 'show')->middleware('perm:income_audit.view')->name('day');
            Route::get('/days/{auditday}/print', 'print')->middleware('perm:income_audit.view')->name('print');
            Route::post('/days/{auditday}/run', 'run')->middleware('perm:income_audit.audit')->name('run');
            Route::post('/days/{auditday}/sign', 'sign')->middleware('perm:income_audit.audit')->name('sign');
            Route::post('/days/{auditday}/close', 'close')->middleware('perm:income_audit.close')->name('close');
            Route::post('/days/{auditday}/reopen', 'reopen')->middleware('perm:income_audit.close')->name('reopen');
            Route::post('/findings/{auditfinding}', 'resolve')->middleware('perm:income_audit.audit,income_audit.close')->name('resolve');
            Route::get('/room-count', 'count')->middleware('perm:income_audit.count')->name('count');
            Route::post('/room-count', 'saveCount')->middleware('perm:income_audit.count')->name('count.save');
            Route::get('/discrepancies', 'discrepancies')->middleware('perm:income_audit.view,income_audit.authorise')->name('discrepancies');
            Route::post('/room-count/{roomcount}/resolve', 'resolveCount')->middleware('perm:income_audit.authorise,income_audit.audit')->name('count.resolve');
        });
        Route::controller(Controllers\AuthorisationController::class)->group(function () {
            Route::get('/authorisations', 'index')->middleware('perm:income_audit.request,income_audit.view')->name('auth');
            Route::post('/authorisations', 'store')->middleware('perm:income_audit.request')->name('auth.store');
            Route::post('/authorisations/{revauth}/approve', 'approve')->middleware('perm:income_audit.authorise,income_audit.close')->name('auth.approve');
            Route::post('/authorisations/{revauth}/decline', 'decline')->middleware('perm:income_audit.authorise,income_audit.close')->name('auth.decline');
            Route::post('/authorisations/{revauth}/cancel', 'cancel')->middleware('perm:income_audit.request')->name('auth.cancel');
        });
    });

    // ---- v5.2 Serviced Apartments & Long-Stay Leases
    Route::prefix('leases')->name('leases.')->group(function () {
        Route::controller(Controllers\LeaseBillingController::class)->group(function () {
            Route::get('/billing', 'index')->middleware('perm:leases.view')->name('billing');
            Route::post('/billing/run', 'run')->middleware('perm:leases.billing')->name('billing.run');
            Route::post('/charges/{leasecharge}/cancel', 'cancelCharge')->middleware('perm:leases.approve')->name('charges.cancel');
            Route::post('/charges/{leasecharge}/credit', 'credit')->middleware('perm:leases.approve')->name('charges.credit');
            Route::get('/charges/{leasecharge}/invoice', 'invoice')->middleware('perm:leases.view')->name('charges.invoice');
            Route::get('/receipts/{leasepay}', 'receipt')->middleware('perm:leases.view')->name('receipts.show');
            Route::post('/receipts/{leasepay}/void', 'voidReceipt')->middleware('perm:leases.approve')->name('receipts.void');
            Route::post('/{lease}/pay', 'pay')->middleware('perm:leases.billing')->name('pay');
            Route::post('/{lease}/refund', 'refund')->middleware('perm:leases.approve')->name('refund');
            Route::post('/{lease}/apply-deposit', 'applyDeposit')->middleware('perm:leases.approve')->name('apply');
            Route::post('/{lease}/charge', 'charge')->middleware('perm:leases.billing')->name('charge');
            Route::get('/{lease}/statement', 'statement')->middleware('perm:leases.view')->name('statement');
            Route::get('/{lease}/settlement', 'settlement')->middleware('perm:leases.view')->name('settlement');
        });
        Route::controller(Controllers\LeaseController::class)->group(function () {
            Route::get('/', 'index')->middleware('perm:leases.view')->name('index');
            Route::get('/all', 'list')->middleware('perm:leases.view')->name('list');
            Route::get('/units', 'units')->middleware('perm:leases.view')->name('units');
            Route::post('/units', 'saveUnit')->middleware('perm:leases.manage')->name('units.store');
            Route::put('/units/{leaseunit}', 'saveUnit')->middleware('perm:leases.manage')->name('units.update');
            Route::post('/settings', 'settings')->middleware('perm:leases.approve')->name('settings');
            Route::get('/meters', 'meters')->middleware('perm:leases.view')->name('meters');
            Route::post('/meters', 'saveReadings')->middleware('perm:leases.manage')->name('meters.store');
            Route::get('/inspections/{leaseinspection}', 'inspection')->middleware('perm:leases.view')->name('inspections.show');
            Route::get('/new', 'create')->middleware('perm:leases.manage')->name('create');
            Route::post('/', 'store')->middleware('perm:leases.manage')->name('store');
            Route::get('/{lease}', 'show')->middleware('perm:leases.view')->name('show');
            Route::get('/{lease}/edit', 'edit')->middleware('perm:leases.manage')->name('edit');
            Route::put('/{lease}', 'update')->middleware('perm:leases.manage')->name('update');
            Route::get('/{lease}/agreement', 'agreement')->middleware('perm:leases.view')->name('agreement');
            Route::post('/{lease}/sign', 'sign')->middleware('perm:leases.manage')->name('sign');
            Route::post('/{lease}/move-in', 'activate')->middleware('perm:leases.manage')->name('activate');
            Route::post('/{lease}/cancel', 'cancel')->middleware('perm:leases.manage')->name('cancel');
            Route::post('/{lease}/notice', 'notice')->middleware('perm:leases.manage')->name('notice');
            Route::post('/{lease}/withdraw-notice', 'withdrawNotice')->middleware('perm:leases.manage')->name('withdraw');
            Route::post('/{lease}/renew', 'renew')->middleware('perm:leases.manage')->name('renew');
            Route::post('/{lease}/move-out', 'moveOut')->middleware('perm:leases.manage')->name('moveout');
            Route::post('/{lease}/settle', 'settle')->middleware('perm:leases.approve')->name('settle');
            Route::post('/{lease}/inspections', 'inspect')->middleware('perm:leases.manage')->name('inspect');
        });
    });

    Route::get('/email-log', [Controllers\EmailController::class, 'index'])->middleware('perm:logs.view')->name('emails.index');
    Route::get('/email-log/preview/{template}', [Controllers\EmailController::class, 'preview'])->middleware('perm:logs.view')->name('emails.preview');
    Route::get('/message-log', [Controllers\MessageController::class, 'index'])->middleware('perm:logs.view')->name('messages.index');
    Route::get('/activity-log', [Controllers\ActivityLogController::class, 'index'])->middleware('perm:logs.view')->name('logs.index');
