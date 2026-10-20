<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Support tickets, customer side (B7): open/list/thread/reply with
 * reopen-on-message, close gates, open-ticket cap, foreign 404s.
 */
final class CustomerSupportTest extends TenantIntegrationTestCase
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

    public function test_open_thread_reply_reopen_close(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $opened = $actor()->postJson('/api/v1/customers/support/tickets', [
            'subject' => 'Reschedule help', 'body' => 'Need a new slot.', 'category' => 'booking',
        ], $headers)->assertCreated()->assertJsonPath('data.status', 'OPEN');
        $id = $opened->json('data.id');
        $this->assertCount(1, $opened->json('data.messages'));

        // Bad category is a validation error, not a ticket.
        $actor()->postJson('/api/v1/customers/support/tickets', [
            'subject' => 'X', 'body' => 'Y', 'category' => 'astrology',
        ], $headers)->assertStatus(422);

        $listed = $actor()->getJson('/api/v1/customers/support/tickets', $headers)->assertOk();
        $this->assertSame([$id], collect($listed->json('data'))->pluck('id')->all());
        $this->assertSame([], $listed->json('data.0.messages'));

        $actor()->postJson("/api/v1/customers/support/tickets/{$id}/messages", [
            'body' => 'Any update?',
        ], $headers)->assertCreated();

        $thread = $actor()->getJson("/api/v1/customers/support/tickets/{$id}", $headers)->assertOk();
        $this->assertCount(2, $thread->json('data.messages'));

        $actor()->postJson("/api/v1/customers/support/tickets/{$id}:close", [], $headers)
            ->assertOk()->assertJsonPath('data.status', 'CLOSED');
        $actor()->postJson("/api/v1/customers/support/tickets/{$id}:close", [], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');

        // A reply re-opens the closed thread (documented behavior).
        $reopened = $actor()->postJson("/api/v1/customers/support/tickets/{$id}/messages", [
            'body' => 'Actually, one more thing.',
        ], $headers)->assertCreated()->assertJsonPath('data.status', 'OPEN');
        $this->assertCount(1, $reopened->json('data.messages'));

        $this->assertAuditRecorded($tenant, 'customers.ticket.created');
        $this->assertAuditRecorded($tenant, 'customers.ticket.closed');
    }

    public function test_cap_foreign_and_guests(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        auth()->logout();
        $this->postJson('/api/v1/customers/support/tickets', [
            'subject' => 'S', 'body' => 'B', 'category' => 'other',
        ], $headers)->assertUnauthorized();

        $actor = fn () => $this->actingAs($user);
        for ($i = 0; $i < \App\Modules\Customers\Models\SupportTicket::MAX_OPEN_PER_CUSTOMER; $i++) {
            $actor()->postJson('/api/v1/customers/support/tickets', [
                'subject' => "Issue {$i}", 'body' => 'Details.', 'category' => 'other',
            ], $headers)->assertCreated();
        }
        $actor()->postJson('/api/v1/customers/support/tickets', [
            'subject' => 'Overflow', 'body' => 'Details.', 'category' => 'other',
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'conflict');

        $other = $this->makeTenant();
        [$otherUser] = $this->actingAsMembership($other);
        $foreignId = $this->actingAs($otherUser)->postJson('/api/v1/customers/support/tickets', [
            'subject' => 'Foreign', 'body' => 'Details.', 'category' => 'other',
        ], $this->tenantHeaders($other))->assertCreated()->json('data.id');

        $actor()->getJson("/api/v1/customers/support/tickets/{$foreignId}", $headers)->assertNotFound();
        $actor()->postJson("/api/v1/customers/support/tickets/{$foreignId}/messages", ['body' => 'Hi'], $headers)->assertNotFound();
        $actor()->postJson("/api/v1/customers/support/tickets/{$foreignId}:close", [], $headers)->assertNotFound();
    }
}
