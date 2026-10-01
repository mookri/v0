<?php

use App\Platform\Observability\Health\ReadinessCheck;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Web Surfaces (doc 06 §11)
|--------------------------------------------------------------------------
|
| Kernel-owned, public discovery & health endpoints. This file is
| required from routes/web.php BEFORE the Velzon admin shell catch-all
| (inside the `web` middleware group). `/up` is registered by the
| framework via the `health` key in bootstrap/app.php.
|
*/

Route::get('/ready', ReadinessCheck::class)->name('platform.ready');
