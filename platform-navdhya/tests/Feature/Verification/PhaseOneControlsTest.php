<?php

namespace Tests\Feature\Verification;

use App\Domain\Verification\Exceptions\DirectVerificationStatusWriteException;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Projector\VerificationProjector;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\TempleManagementService;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\VendorManagementService;
use App\Platform\Audit\AuditLog;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Tests T-TR01-05 (T-01), T-TR01-06 (T-14), and T-TR01-07 (T-15):
 * - Runtime write-block forbids direct verification_status updates across Pandit, Temple, Vendor
 * - VerificationProjector records structured claims (mode=manual) and derives column projection
 * - verification:backfill-legacy-claims tags legacy VERIFIED rows with legacy_asserted=true
 * - TempleManagementService::verify() no longer sets status=ACTIVE; publish() is explicit and audited
 * - VendorManagementService::verify() and suspend() record audited events inside transactions
 */
final class PhaseOneControlsTest extends TenantIntegrationTestCase
{
    public function test_runtime_write_block_forbids_direct_verification_status_mutations(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $pandit = PanditProfile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'verification_status' => PanditProfile::VERIFICATION_PENDING,
        ]);

        $temple = Temple::factory()->create([
            'tenant_id' => $tenant->id,
            'owner_id' => $user->id,
            'status' => Temple::STATUS_DRAFT,
            'verification_status' => Temple::VERIFICATION_PENDING,
        ]);

        $vendor = Vendor::factory()->create([
            'tenant_id' => $tenant->id,
            'owner_user_id' => $user->id,
            'application_id' => (string) Str::ulid(),
            'verification_status' => Vendor::VERIFICATION_PENDING,
        ]);

        foreach ([
            [$pandit, PanditProfile::VERIFICATION_VERIFIED],
            [$temple, Temple::VERIFICATION_VERIFIED],
            [$vendor, Vendor::VERIFICATION_VERIFIED],
        ] as [$model, $nextStatus]) {
            $threw = false;
            try {
                $model->forceFill(['verification_status' => $nextStatus])->save();
            } catch (DirectVerificationStatusWriteException) {
                $threw = true;
            }
            $this->assertTrue($threw, 'Expected DirectVerificationStatusWriteException for ' . $model::class);
            $this->assertSame('PENDING', $model->fresh()->verification_status);
        }

        $projector = app(VerificationProjector::class);
        $claim = $projector->grantManualClaimAndProject($pandit, 'pandit', 'admin_1');

        $this->assertSame('manual', $claim->mode);
        $this->assertSame('granted', $claim->status);
        $this->assertSame(PanditProfile::VERIFICATION_VERIFIED, $pandit->fresh()->verification_status);
    }

    public function test_temple_verify_preserves_draft_status_until_explicit_publish_and_audits_both(): void
    {
        $tenant = $this->makeTenant();
        [$admin, $membership] = $this->actingAsMembership($tenant, ['temple:profile:manage']);
        $this->resolveContext($tenant, $membership);

        $temple = Temple::factory()->create([
            'tenant_id' => $tenant->id,
            'owner_id' => $admin->id,
            'status' => Temple::STATUS_DRAFT,
            'verification_status' => Temple::VERIFICATION_PENDING,
        ]);

        $service = app(TempleManagementService::class);

        $beforePublishFailed = false;
        try {
            $service->publish((string) $temple->id);
        } catch (\InvalidArgumentException) {
            $beforePublishFailed = true;
        }
        $this->assertTrue($beforePublishFailed, 'Unverified temple cannot be published.');

        $verified = $service->verify((string) $temple->id);
        $this->assertSame(Temple::VERIFICATION_VERIFIED, $verified['verification_status']);
        $this->assertSame(Temple::STATUS_DRAFT, $verified['status'], 'verify() MUST NOT auto-publish a DRAFT temple.');

        $published = $service->publish((string) $temple->id);
        $this->assertSame(Temple::STATUS_ACTIVE, $published['status']);

        $this->assertSame(1, AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('action', 'temple.profile.verified')
            ->where('resource->resource_id', (string) $temple->id)
            ->count());

        $this->assertSame(1, AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('action', 'temple.profile.published')
            ->where('resource->resource_id', (string) $temple->id)
            ->count());
    }

    public function test_vendor_verify_and_suspend_emit_audit_logs(): void
    {
        $tenant = $this->makeTenant();
        [$admin, $membership] = $this->actingAsMembership($tenant, ['vendor:profile:manage']);
        $this->resolveContext($tenant, $membership);

        $vendor = Vendor::factory()->create([
            'tenant_id' => $tenant->id,
            'owner_user_id' => $admin->id,
            'application_id' => (string) Str::ulid(),
            'status' => Vendor::STATUS_ACTIVE,
            'verification_status' => Vendor::VERIFICATION_PENDING,
        ]);

        $service = app(VendorManagementService::class);
        $verified = $service->verify((string) $vendor->id);
        $this->assertSame(Vendor::VERIFICATION_VERIFIED, $verified['verification_status']);

        $suspended = $service->suspend((string) $vendor->id);
        $this->assertSame(Vendor::STATUS_SUSPENDED, $suspended['status']);

        $this->assertSame(1, AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('action', 'vendor.profile.verified')
            ->where('resource->resource_id', (string) $vendor->id)
            ->count());

        $this->assertSame(1, AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('action', 'vendor.profile.suspended')
            ->where('resource->resource_id', (string) $vendor->id)
            ->count());
    }

    public function test_backfill_legacy_claims_marks_legacy_asserted_and_is_idempotent(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $pandit = PanditProfile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
        ]);

        $this->artisan('verification:backfill-legacy-claims', ['--tenant' => $tenant->id, '--dry-run' => true])
            ->assertExitCode(0);
        $this->assertSame(0, VerificationClaim::query()->withoutGlobalScopes()->where('subject_id', $pandit->id)->count());

        $this->artisan('verification:backfill-legacy-claims', ['--tenant' => $tenant->id])
            ->assertExitCode(0);

        $claim = VerificationClaim::query()->withoutGlobalScopes()->where('subject_id', $pandit->id)->first();
        $this->assertNotNull($claim);
        $this->assertTrue($claim->legacy_asserted);
        $this->assertSame('manual', $claim->mode);
        $this->assertSame('unassessed', $claim->confidence);
        $this->assertSame('legacy', $claim->claim_kind);

        // Idempotent second run creates no duplicate.
        $this->artisan('verification:backfill-legacy-claims', ['--tenant' => $tenant->id])
            ->assertExitCode(0);
        $this->assertSame(1, VerificationClaim::query()->withoutGlobalScopes()->where('subject_id', $pandit->id)->count());
    }
}
