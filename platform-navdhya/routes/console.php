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
Schedule::command('cleanup:expired --force')->daily()->withoutOverlapping(30);
Schedule::command('passport:purge --hours=168')->weekly();
// Transactional outbox (F2): dispatch pending events once a minute —
// producers only append; consumers exist only once handlers register
// (until then the drain reports unhandled and leaves rows pending).
Schedule::command('outbox:drain')->everyMinute()->withoutOverlapping(10);

// Booking checkout hold reaper (Phase 2, Task 2.3.3): sweep expired locks every minute.
Schedule::command('booking:reap-expired-holds')->everyMinute()->withoutOverlapping(10);

// Verification lifecycle expiry & DPDP retention purges (E-TR-06 / E-TR-07).
Schedule::command('verification:expire')->daily()->withoutOverlapping(30);
Schedule::command('privacy:purge-expired')->daily()->withoutOverlapping(30);

// Missing arrival scanner (TS-01 / TS-02 — alerts ops without auto NO_SHOW).
Schedule::command('booking:scan-missing-arrivals')->everyFiveMinutes()->withoutOverlapping(5);

// Statutory grievance acknowledgment & redressal SLA scanner (TS-11).
Schedule::command('support:scan-grievance-slas')->hourly()->withoutOverlapping(15);

// Devotee ritual reminder scan (T-JYAUT-01 / JY-AUT-01): daily advance slot notifications at 04:00 UTC.
Schedule::command('customers:process-reminders')->dailyAt('04:00')->withoutOverlapping(30);

// Ruleset staleness sweeper (T-JYAUT-05 / JY-AUT-05): monthly sweep for outdated rulesets.
Schedule::command('jyotish:ruleset-staleness-sweep')->monthly()->withoutOverlapping(15);

// Calendar autonomous generation & health watchdogs (T-JYAUT-02..04 / JY-AUT-02..04).
Schedule::command('calendar:horizon-watchdog')->hourly()->withoutOverlapping(10);
Schedule::command('calendar:tzdb-drift-detection')->monthly()->withoutOverlapping(15);
Schedule::command('calendar:autonomous-generate')->monthly()->withoutOverlapping(60);

// Uncertain payment reconciliation (T-AP02-02): poll gateway for PAYMENT_UNKNOWN intents.
Schedule::command('payments:reconcile-unknown')->everyTwoMinutes()->withoutOverlapping(5);
