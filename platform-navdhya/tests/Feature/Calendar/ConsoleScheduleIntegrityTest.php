<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * Validates console schedule registration and mutex locks (T-JYAUT-01 / AC-T-JYAUT-01).
 */
final class ConsoleScheduleIntegrityTest extends TestCase
{
    public function test_customers_process_reminders_is_scheduled_with_mutex_lock(): void
    {
        $consolePath = realpath(__DIR__ . '/../../../routes/console.php');
        $this->assertNotFalse($consolePath);

        $content = file_get_contents($consolePath);
        $this->assertNotFalse($content);

        // Assert registration in routes/console.php
        $this->assertStringContainsString("Schedule::command('customers:process-reminders')", $content);
        $this->assertStringContainsString('withoutOverlapping', $content);
    }
}

