<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use Tests\TenantIntegrationTestCase;

/**
 * The OTP login endpoint must not reveal whether an identity is
 * registered (BACKEND-RCA 2026-10-21, finding 2.6).
 *
 * Before the fix, an unknown identity always answered 202 while a known
 * one answered 429 on its second request - two POSTs per candidate
 * turned the endpoint into an account-enumeration oracle.
 */
final class CustomerOtpEnumerationTest extends TenantIntegrationTestCase
{
    /**
     * @return list<int>
     */
    private function statusSequence(string $tenantId, string $phone): array
    {
        $statuses = [];

        for ($i = 0; $i < 3; $i++) {
            $statuses[] = $this->postJson('/api/v1/customers/auth/otp', [
                'tenant_id' => $tenantId,
                'phone' => $phone,
            ])->getStatusCode();
        }

        return $statuses;
    }

    public function test_known_and_unknown_identities_answer_identically(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();

        $knownPhone = '+919876543210';
        User::factory()->create(['phone' => $knownPhone]);

        $known = $this->statusSequence($tenantId, $knownPhone);
        $unknown = $this->statusSequence($tenantId, '+919876543211');

        $this->assertSame(
            $known,
            $unknown,
            'The OTP issue endpoint must answer the same way for known and unknown identities.'
        );
    }

    public function test_the_first_request_is_accepted_for_both(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();

        $knownPhone = '+919876543212';
        User::factory()->create(['phone' => $knownPhone]);

        $known = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tenantId, 'phone' => $knownPhone,
        ]);

        $unknown = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tenantId, 'phone' => '+919876543213',
        ]);

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame(
            $known->json('data.object'),
            $unknown->json('data.object')
        );
        $this->assertNotEmpty($unknown->json('data.challenge_id'));
    }
}
