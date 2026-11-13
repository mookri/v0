<?php

namespace Tests\Architecture;

use App\Domain\Verification\Concerns\BlocksDirectVerificationStatusWrite;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Temple\Models\Temple;
use App\Modules\Vendor\Models\Vendor;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Automated architecture report for T-TR01-05 (T-01) & T-TR01-07 (T-15):
 * - All projected partner models must apply BlocksDirectVerificationStatusWrite.
 * - No non-Verification production class may mutate `verification_status` directly.
 * - Admin state transitions in Pandit, Temple, Vendor, and Onboarding must record AuditWriter logs.
 */
final class AuditCoverageReportTest extends TestCase
{
    public function test_partner_models_use_verification_status_write_block_trait(): void
    {
        foreach ([PanditProfile::class, Temple::class, Vendor::class] as $modelClass) {
            $traits = class_uses_recursive($modelClass);
            $this->assertArrayHasKey(
                BlocksDirectVerificationStatusWrite::class,
                $traits,
                $modelClass . ' must use BlocksDirectVerificationStatusWrite trait.',
            );
        }
    }

    public function test_no_non_verification_production_code_directly_writes_verification_status(): void
    {
        $modulesDir = base_path('app/Modules');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesDir));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            // Initial creation on Temple/Vendor sets VERIFICATION_PENDING only; no VERIFIED/REJECTED writes allowed.
            $contents = file_get_contents($path) ?: '';
            $this->assertDoesNotMatchRegularExpression(
                "/['\"]verification_status['\"]\s*=>\s*[^,\]]*VERIFICATION_(VERIFIED|REJECTED)/",
                $contents,
                "Forbidden direct verification_status promotion write found in {$path}",
            );
        }
    }

    public function test_admin_lifecycle_services_record_audit_events(): void
    {
        $required = [
            app_path('Modules/Pandit/Http/Controllers/PanditProfileController.php') => ['pandit.profile.verified', 'pandit.profile.suspended', 'pandit.profile.reactivated'],
            app_path('Modules/Temple/TempleManagementService.php') => ['temple.profile.verified', 'temple.profile.published', 'temple.profile.suspended', 'temple.profile.reactivated'],
            app_path('Modules/Vendor/VendorManagementService.php') => ['vendor.profile.verified', 'vendor.profile.suspended'],
            app_path('Modules/Onboarding/Http/Controllers/ReviewController.php') => ['onboarding.review.decided'],
            app_path('Modules/Jyotish/Audit/RulesetAuditWriter.php') => [
                'jyotish.ruleset.created',
                'jyotish.ruleset.updated',
                'jyotish.ruleset.promoted',
                'jyotish.generation.promoted',
                'jyotish.generation.retired',
            ],
        ];

        foreach ($required as $path => $events) {
            $contents = file_get_contents($path) ?: '';
            foreach ($events as $event) {
                $this->assertStringContainsString(
                    $event,
                    $contents,
                    "Expected AuditWriter event '{$event}' in {$path}",
                );
            }
        }
    }
}
