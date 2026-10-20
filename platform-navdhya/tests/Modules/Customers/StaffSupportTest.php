<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Support staff console (plan slice 4, doc 06 §7): capability-gated
 * tenant-wide queue over the customers module's rows, thread authorship,
 * and the SHARED reply/close actions — bearer envelopes on /api/v1,
 * redirect + flash on web form posts. Customer-side routes stay
 * ownership-scoped (covered by CustomerSupportTest).
 */
final class StaffSupportTest extends TenantIntegrationTestCase
{
    /**
     * Open one booking ticket in the given tenant as a fresh customer
     * (customer-side flow, unchanged), and return its id.
     */
    private function openTicket(Tenant $tenant): string
    {
        $customer = User::factory()->create();
        $customer->forceFill(['external_id' => 'cust_'.strtolower((string) Str::ulid())])->save();
        $this->makeMembership($tenant, $customer);

        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $customer->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($customer);

        return $this->postJson('/api/v1/customers/support/tickets', [
            'subject' => 'Need help with a booking',
            'body' => 'Rescheduled slot vanished.',
            'category' => 'booking',
        ], $this->tenantHeaders($tenant))->assertCreated()->json('data.id');
    }

    public function test_staff_queue_thread_reply_and_close(): void
    {
        $tenant = $this->makeTenant();
        $ticketId = $this->openTicket($tenant);

        [$staff] = $this->actingAsMembership($tenant, [
            'customers:support:view',
            'customers:support:manage',
        ]);
        $headers = $this->tenantHeaders($tenant);

        // Queue: every ticket in the tenant, customer identity masked.
        $listed = $this->actingAs($staff)
            ->getJson('/api/v1/customers/support/staff/tickets', $headers)
            ->assertOk();
        $this->assertSame([$ticketId], collect($listed->json('data'))->pluck('id')->all());
        $this->assertNotEmpty($listed->json('data.0.customer.name'));
        $this->assertStringContainsString('***', (string) $listed->json('data.0.customer.email'));

        // Thread: full messages with authorship.
        $thread = $this->actingAs($staff)
            ->getJson("/api/v1/customers/support/staff/tickets/{$ticketId}", $headers)
            ->assertOk();
        $this->assertSame('Need help with a booking', $thread->json('data.subject'));
        $this->assertSame('customer', $thread->json('data.messages.0.by'));
        $this->assertCount(1, $thread->json('data.messages'));

        // Staff reply: 201, authored by the desk, audited as staff.
        $this->actingAs($staff)
            ->postJson("/api/v1/customers/support/staff/tickets/{$ticketId}/messages", [
                'body' => 'We are on it.',
            ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'OPEN')
            ->assertJsonPath('data.messages.0.by', 'staff')
            ->assertJsonPath('data.messages.0.author.name', $staff->name);

        $after = $this->actingAs($staff)
            ->getJson("/api/v1/customers/support/staff/tickets/{$ticketId}", $headers)
            ->assertOk();
        $this->assertCount(2, $after->json('data.messages'));

        // Close: 200; repeating is 409 state_forbidden — the SAME code
        // the customer side answers with (parity by construction).
        $this->actingAs($staff)
            ->postJson("/api/v1/customers/support/staff/tickets/{$ticketId}:close", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'CLOSED');
        $this->actingAs($staff)
            ->postJson("/api/v1/customers/support/staff/tickets/{$ticketId}:close", [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden');

        // Status filter on the queue.
        $this->assertSame(
            [$ticketId],
            collect($this->actingAs($staff)
                ->getJson('/api/v1/customers/support/staff/tickets?status=CLOSED', $headers)
                ->assertOk()->json('data'))->pluck('id')->all(),
        );
        $this->assertSame([], $this->actingAs($staff)
            ->getJson('/api/v1/customers/support/staff/tickets?status=OPEN', $headers)
            ->assertOk()->json('data'));
        $this->actingAs($staff)
            ->getJson('/api/v1/customers/support/staff/tickets?status=WEIRD', $headers)
            ->assertStatus(422);

        // A staff reply re-opens — identical semantics to the customer's
        // own reply (documented reopen-on-message).
        $this->actingAs($staff)
            ->postJson("/api/v1/customers/support/staff/tickets/{$ticketId}/messages", [
                'body' => 'Following up with the fix.',
            ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'OPEN');

        $this->assertAuditRecorded($tenant, 'customers.ticket.replied');
        $this->assertAuditRecorded($tenant, 'customers.ticket.closed');
    }

    public function test_staff_routes_are_capability_gated(): void
    {
        $tenant = $this->makeTenant();
        [$member] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $this->getJson('/api/v1/customers/support/staff/tickets', $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied');
        $this->getJson('/api/v1/customers/support/staff/tickets/missing', $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied');
        $this->postJson('/api/v1/customers/support/staff/tickets/missing/messages', ['body' => 'x'], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied');
        $this->postJson('/api/v1/customers/support/staff/tickets/missing:close', [], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied');

        auth()->logout();
        $this->getJson('/api/v1/customers/support/staff/tickets', $headers)->assertUnauthorized();
    }

    public function test_staff_queue_is_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        $foreignId = $this->openTicket($tenantB);

        [$staffA] = $this->actingAsMembership($tenantA, [
            'customers:support:view',
            'customers:support:manage',
        ]);
        $headers = $this->tenantHeaders($tenantA);

        $listed = $this->actingAs($staffA)
            ->getJson('/api/v1/customers/support/staff/tickets', $headers)
            ->assertOk();
        $this->assertSame([], $listed->json('data'));

        // Foreign ids collapse into the same 404 as unknown ids.
        $this->actingAs($staffA)->getJson("/api/v1/customers/support/staff/tickets/{$foreignId}", $headers)->assertNotFound();
        $this->actingAs($staffA)->postJson("/api/v1/customers/support/staff/tickets/{$foreignId}/messages", ['body' => 'Hi'], $headers)->assertNotFound();
        $this->actingAs($staffA)->postJson("/api/v1/customers/support/staff/tickets/{$foreignId}:close", [], $headers)->assertNotFound();
    }

    public function test_web_reply_and_close_share_the_api_actions(): void
    {
        $tenant = $this->makeTenant();
        $ticketId = $this->openTicket($tenant);

        [$staff] = $this->actingAsMembership($tenant, [
            'customers:support:view',
            'customers:support:manage',
        ]);

        // The console's reply form: session + CSRF + hidden tenant field.
        $this->withHeader('Referer', 'http://localhost/customers/support')
            ->post("/customers/support/tickets/{$ticketId}/messages", [
                '_token' => 'not-a-real-token',
                'tenant_id' => $tenant->getKey(),
                'body' => 'Checked your booking.',
            ])
            ->assertRedirect('http://localhost/customers/support')
            ->assertSessionHas('flash', 'Reply sent.');

        $this->assertDatabaseHas('support_messages', [
            'ticket_id' => $ticketId,
            'body' => 'Checked your booking.',
        ], 'tenant_shared');

        // The console's close button posts to the shared action too.
        $this->withHeader('Referer', 'http://localhost/customers/support')
            ->post("/customers/support/tickets/{$ticketId}:close", [
                '_token' => 'not-a-real-token',
                'tenant_id' => $tenant->getKey(),
            ])
            ->assertRedirect('http://localhost/customers/support')
            ->assertSessionHas('flash', 'Ticket closed.');

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticketId,
            'status' => 'CLOSED',
        ], 'tenant_shared');

        // Web denial keeps the real status (thrown capability denial).
        [$plain] = $this->actingAsMembership($tenant);
        $this->actingAs($plain)
            ->post("/customers/support/tickets/{$ticketId}/messages", [
                'tenant_id' => $tenant->getKey(),
                'body' => 'nope',
            ])
            ->assertForbidden();
    }

    public function test_support_page_renders_the_console_shell(): void
    {
        $this->withoutVite();

        // Guest: straight to login.
        $this->get('/customers/support')->assertRedirect('/login');

        $tenant = $this->makeTenant();
        [$staff] = $this->actingAsMembership($tenant, ['customers:support:view']);

        $response = $this->actingAs($staff)->get('/customers/support');
        $response->assertOk();
        $response->assertSee('id="support-rows"', false);
        $response->assertSee('id="support-thread-card"', false);
        $response->assertSee('id="support-reply-form"', false);
        $response->assertSee('id="support-close-form"', false);
        $response->assertSee('name="csrf-token"', false);
        $response->assertSee('data-workspace-field', false);
    }
}
