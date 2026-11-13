<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Models\NotificationIntent;
use App\Domain\Delivery\Planning\IntentPlanner;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM03-02:
 * Proves that IntentPlanner maps business domain events into unique NotificationIntents
 * with deterministic deduplication.
 */
final class IntentPlannerTest extends TenantIntegrationTestCase
{
    public function test_intent_planner_produces_unique_intent_per_moment_per_recipient(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $planner = new IntentPlanner();

        $intent1 = $planner->plan(
            tenantId: $tenantId,
            eventType: 'booking.confirmed',
            recipientRef: '+919876543210',
            subjectType: 'booking',
            subjectId: 'bkg_444',
            category: 'transactional'
        );

        $this->assertInstanceOf(NotificationIntent::class, $intent1);
        $this->assertSame('+919876543210', $intent1->recipient_ref);
        $this->assertSame('bkg_444', $intent1->subject_id);

        // Plan again with identical inputs — must return the existing intent without creating a second row
        $intent2 = $planner->plan(
            tenantId: $tenantId,
            eventType: 'booking.confirmed',
            recipientRef: '+919876543210',
            subjectType: 'booking',
            subjectId: 'bkg_444',
            category: 'transactional'
        );

        $this->assertSame($intent1->id, $intent2->id);
        $this->assertSame(1, NotificationIntent::query()->where('subject_id', 'bkg_444')->count());
    }
}

