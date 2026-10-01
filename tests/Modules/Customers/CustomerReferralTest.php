<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\ReferralAttribution;
use App\Modules\Customers\Models\ReferralCode;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Referrals, attribution only: codes issued at signup, valid codes
 * attribute, invalid/self codes are ignored (never signup failures),
 * and the summary exposes code + count + masked list.
 */
final class CustomerReferralTest extends TenantIntegrationTestCase
{
    private function otp(): SeededOtpCodes
    {
        $codes = new SeededOtpCodes();
        $this->app->singleton(CustomerOtpService::class, static fn (): CustomerOtpService => new CustomerOtpService($codes));
        config()->set('identity.hmac_secret', 'referral-test-secret');

        return $codes;
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(string $phone): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill([
            'email' => preg_replace('/[^0-9]/', '', $phone).'@phone.local',
            'external_id' => 'cust_'.strtolower((string) Str::ulid()),
        ])->save();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user];
    }

    private function signupWithReferral(Tenant $tenant, string $phone, string $name, ?string $referralCode, SeededOtpCodes $codes): string
    {
        $tid = (string) $tenant->getKey();

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', array_filter([
            'tenant_id' => $tid, 'phone' => $phone, 'name' => $name, 'referral_code' => $referralCode,
        ]))->assertStatus(202)->json('data.challenge_id');

        $verify = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ]);
        $verify->assertCreated();

        return (string) User::query()->where('phone', $phone)->firstOrFail()->getAuthIdentifier();
    }

    public function test_attribution_and_summary(): void
    {
        [$tenant, $referrer] = $this->customer('+911111111111');
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        // Referrer has a code (backfilled on first read).
        $summary = $this->actingAs($referrer)->getJson('/api/v1/customers/referrals', $headers)->assertOk();
        $code = $summary->json('data.code');
        $this->assertMatchesRegularExpression('/^REF-[A-HJ-NP-Z2-9]{8}$/', $code);
        $this->assertSame(0, $summary->json('data.referred_count'));

        // Referred signup attributes.
        $codes = $this->otp();
        $referredId = $this->signupWithReferral($tenant, '+912222222222', 'Referred', $code, $codes);

        $this->assertDatabaseHas('referral_attributions', [
            'tenant_id' => $tid,
            'referrer_user_id' => $referrer->getAuthIdentifier(),
            'referred_user_id' => $referredId,
            'code' => $code,
        ], 'tenant_shared');
        $this->assertAuditRecorded($tenant, 'customers.referral.attributed');

        $after = $this->actingAs($referrer)->getJson('/api/v1/customers/referrals', $headers)->assertOk();
        $this->assertSame(1, $after->json('data.referred_count'));
        $this->assertSame('+91******22', $after->json('data.referrals.0.phone_masked'));

        // The referred account got its own (different) code.
        $this->resolveContext($tenant);
        try {
            $own = ReferralCode::query()->where('user_id', $referredId)->firstOrFail();
        } finally {
            $this->clearContext();
        }
        $this->assertNotSame($code, $own->code);
    }

    public function test_invalid_and_self_codes_ignored(): void
    {
        [$tenant] = $this->customer('+911111111111');

        // Unknown code: signup still succeeds, nothing attributed.
        $codes = $this->otp();
        $referredId = $this->signupWithReferral($tenant, '+913333333333', 'NoRef', 'REF-NONSENSE', $codes);
        $this->assertSame(0, ReferralAttribution::query()
            ->withoutGlobalScopes()->where('referred_user_id', $referredId)->count());

        // Self-referral is impossible by construction (the code must
        // exist before the account does), so the remaining ignore case
        // is a foreign-tenant code.
        $other = $this->makeTenant();
        $this->resolveContext($other);
        try {
            $foreign = ReferralCode::factory()->create();
        } finally {
            $this->clearContext();
        }

        $referred2 = $this->signupWithReferral($tenant, '+914444444444', 'Foreign', $foreign->code, $codes);
        $this->assertSame(0, ReferralAttribution::query()
            ->withoutGlobalScopes()->where('referred_user_id', $referred2)->count());
    }
}
