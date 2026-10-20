<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Settings (B4): defaults merge, strict per-key updates with type
 * validation, unknown keys rejected, guests denied.
 */
final class CustomerSettingTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'cust_'.strtolower((string) Str::ulid())])->save();
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

    public function test_defaults_update_and_strictness(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $defaults = $actor()->getJson('/api/v1/customers/settings', $headers)->assertOk();
        $this->assertSame('en', $defaults->json('data.settings.locale'));
        $this->assertTrue($defaults->json('data.settings.notifications_email'));
        $this->assertFalse($defaults->json('data.settings.marketing_opt_in'));

        $updated = $actor()->patchJson('/api/v1/customers/settings', [
            'locale' => 'hi', 'marketing_opt_in' => true,
        ], $headers)->assertOk();
        $this->assertSame('hi', $updated->json('data.settings.locale'));
        $this->assertTrue($updated->json('data.settings.marketing_opt_in'));
        $this->assertTrue($updated->json('data.settings.notifications_sms'));

        $actor()->patchJson('/api/v1/customers/settings', ['theme' => 'dark'], $headers)
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.fields.theme', ['unknown_setting']);

        $actor()->patchJson('/api/v1/customers/settings', ['marketing_opt_in' => 'yes-please'], $headers)
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $this->assertAuditRecorded($tenant, 'customers.settings.updated');
    }

    public function test_guests_are_denied(): void
    {
        $tenant = $this->makeTenant();

        $this->getJson('/api/v1/customers/settings', $this->tenantHeaders($tenant))->assertUnauthorized();
    }
}
