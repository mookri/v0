<?php

use App\Modules\Customers\Models\CustomerRitualReminder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // For all pre-existing ritual reminders without a declared rules_version, stamp rules_version = 1 (ADR-0035)
        CustomerRitualReminder::query()
            ->each(function (CustomerRitualReminder $reminder): void {
                $rule = $reminder->anchor_rule ?? [];
                if (! isset($rule['rules_version'])) {
                    $rule['rules_version'] = 1;
                    $reminder->updateQuietly(['anchor_rule' => $rule]);
                }
            });
    }

    public function down(): void
    {
        // No-op rollback: metadata preservation
    }
};

