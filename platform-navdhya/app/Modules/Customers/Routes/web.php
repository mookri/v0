<?php

use App\Modules\Customers\Http\Controllers\StaffSupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customers Module Web Routes (module registry, doc 01 §1)
|--------------------------------------------------------------------------
|
| The support staff console's shared write actions (plan slice 4, doc 06
| §7.1-7.2): one action method serves both this web route and /api/v1 —
| session + CSRF here, bearer over there, one service and validation path
| underneath. The file is mounted bare by the registry, so it applies its
| own middleware. Queue/thread READS never come here: the console pages
| fetch them from the API on the session cookie (SessionContextForReads).
|
| Support replies carry no elevated privilege (no step-up): the
| customers:support:manage capability is the gate. Step-up-gated console
| ops stay API-only until their /oauth/stepup form flow lands (doc 06 §7.5).
|
*/

Route::group(['prefix' => 'customers/support', 'middleware' => 'auth'], function (): void {
    Route::post('tickets/{id}/messages', [StaffSupportController::class, 'reply'])
        ->middleware(['tenant', 'capability:customers:support:manage']);

    Route::post('tickets/{id}:close', [StaffSupportController::class, 'close'])
        ->middleware(['tenant', 'capability:customers:support:manage']);
});
