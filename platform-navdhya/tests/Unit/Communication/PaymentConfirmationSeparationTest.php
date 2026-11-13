<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Support\EventTemplateRegistry;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM04-04:
 * Proves that payment receipt events strictly refrain from confirming bookings
 * prior to booking schedule & pandit validation.
 */
final class PaymentConfirmationSeparationTest extends TenantIntegrationTestCase
{
    public function test_payment_received_cannot_falsely_state_pandit_confirmed(): void
    {
        // 1. Payment receipt with financial details is valid
        $validReceipt = EventTemplateRegistry::validateSemantics(
            EventTemplateRegistry::EVENT_PAYMENT_RECEIVED,
            EventTemplateRegistry::TEMPLATE_PAYMENT_RECEIPT,
            ['amount' => 5000, 'currency' => 'INR', 'receipt_no' => 'REC-2026-001']
        );
        $this->assertTrue($validReceipt);

        // 2. Payment receipt falsely asserting pandit_confirmed fails validation
        $invalidReceipt = EventTemplateRegistry::validateSemantics(
            EventTemplateRegistry::EVENT_PAYMENT_RECEIVED,
            EventTemplateRegistry::TEMPLATE_PAYMENT_RECEIPT,
            ['amount' => 5000, 'pandit_confirmed' => true]
        );
        $this->assertFalse($invalidReceipt);

        // 3. Booking confirmed event properly carries pandit details
        $validConfirmation = EventTemplateRegistry::validateSemantics(
            EventTemplateRegistry::EVENT_BOOKING_CONFIRMED,
            EventTemplateRegistry::TEMPLATE_BOOKING_CONFIRMATION,
            ['booking_id' => 'bkg_123', 'pandit_name' => 'Pandit Sharma']
        );
        $this->assertTrue($validConfirmation);
    }
}

