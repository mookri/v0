<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Retention hygiene (single cron entry point — the deploy runbook must
// schedule `php artisan schedule:run` every minute; without it these
// never fire and tables grow unbounded):
// - daily: sweep expired OTP challenges, invites, sessions, step-up proofs.
// - weekly: purge revoked/expired OAuth tokens and auth codes (168 h
//   retention for expired rows, matching the forensics window).
Schedule::command('cleanup:expired --force')->daily();
Schedule::command('passport:purge --hours=168')->weekly();
