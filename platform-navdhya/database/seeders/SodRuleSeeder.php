<?php

namespace Database\Seeders;

use App\Platform\Authz\Models\SodRule;
use Illuminate\Database\Seeder;

/**
 * Platform-default SoD conflict rules (SEC-011 §7.1). Idempotent by code:
 * safe to re-run; existing rows are left untouched so tenant overrides
 * and severity changes are never clobbered. Run explicitly:
 * `php artisan db:seed --class=SodRuleSeeder`.
 */
final class SodRuleSeeder extends Seeder
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            [
                'code' => 'sod.po_vendor_bill',
                'name' => 'Purchase approver must not approve vendor bills',
                'permission_set' => ['purchasing:order:approve', 'invoicing:bill:approve'],
            ],
            [
                'code' => 'sod.vendor_master_payments',
                'name' => 'Vendor master maintainer must not approve payouts',
                'permission_set' => ['suppliers:supplier:create', 'payments:payout:approve'],
            ],
            [
                'code' => 'sod.payroll_run_approve',
                'name' => 'Payroll creator must not approve the run',
                'permission_set' => ['payroll:run:create', 'payroll:run:approve'],
            ],
            [
                'code' => 'sod.journal_post_approve',
                'name' => 'Journal creator must not approve the journal',
                'permission_set' => ['gl:journal:create', 'gl:journal:approve'],
            ],
            [
                'code' => 'sod.role_grant_self',
                'name' => 'Members must not grant roles to themselves',
                'permission_set' => [],
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::defaults() as $default) {
            SodRule::query()->firstOrCreate(
                ['tenant_id' => null, 'code' => $default['code']],
                [
                    'name' => $default['name'],
                    'kind' => SodRule::KIND_CONFLICT,
                    'role_set' => null,
                    'permission_set' => $default['permission_set'],
                    'severity' => SodRule::SEVERITY_BLOCK,
                    'status' => SodRule::STATUS_ACTIVE,
                ]
            );
        }
    }
}
