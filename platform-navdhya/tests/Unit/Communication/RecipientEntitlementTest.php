<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Policies\RecipientEntitlementGuard;
use App\Modules\Booking\Models\Booking;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM08-02 (Gate G-PRIV-1):
 * Proves recipient entitlement checking and sensitive spiritual attribute scrubbing.
 */
final class RecipientEntitlementTest extends TenantIntegrationTestCase
{
    public function test_entitlement_guard_authorizes_booking_owner_and_blocks_unauthorized_users(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $booking = Booking::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 9301,
            'package_id' => 'pkg_rudrabhishek',
            'city_id' => 'blr',
            'status' => 'CONFIRMED',
            'total_price' => 7000,
            'advance_amount' => 1500,
            'scheduled_date' => now()->addDays(3),
        ]);

        $guard = new RecipientEntitlementGuard();

        // Authorized: the booking owner
        $this->assertTrue($guard->isAuthorized($tenantId, 9301, 'booking', (string) $booking->id));

        // Unauthorized: stranger user
        $this->assertFalse($guard->isAuthorized($tenantId, 9999, 'booking', (string) $booking->id));
    }

    public function test_data_minimization_scrubs_sensitive_spiritual_attributes(): void
    {
        $guard = new RecipientEntitlementGuard();

        $rawVariables = [
            'devotee_name' => 'Rahul Sharma',
            'ritual_name' => 'Navagraha Shanti',
            'scheduled_date' => '20 Nov 2026',
            // Sensitive spiritual attributes that must be scrubbed:
            'gotra' => 'Kashyapa',
            'nakshatra' => 'Rohini',
            'date_of_birth' => '1988-05-12',
            'rashi' => 'Vrishabha',
        ];

        $sanitized = $guard->sanitizeVariables($rawVariables);

        $this->assertArrayHasKey('devotee_name', $sanitized);
        $this->assertArrayHasKey('ritual_name', $sanitized);
        $this->assertArrayHasKey('scheduled_date', $sanitized);

        $this->assertArrayNotHasKey('gotra', $sanitized);
        $this->assertArrayNotHasKey('nakshatra', $sanitized);
        $this->assertArrayNotHasKey('date_of_birth', $sanitized);
        $this->assertArrayNotHasKey('rashi', $sanitized);
    }
}

