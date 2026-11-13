<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Exceptions\DltNotRegisteredException;
use App\Domain\Delivery\Models\SmsDltTemplate;
use App\Domain\Delivery\Registry\SmsDltRegistry;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM05-02 & T-CM05-03 (Closes C-11):
 * Proves that SMS templates require approved TRAI DLT registrations,
 * unregistered templates throw DltNotRegisteredException,
 * and Hindi Unicode characters are accurately detected.
 */
final class DltTemplateRegistryTest extends TenantIntegrationTestCase
{
    public function test_approved_dlt_template_is_resolved(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        SmsDltTemplate::query()->create([
            'template_code' => 'booking_confirmed',
            'pe_id' => '1701159123456789012',
            'header' => 'NAVDHY',
            'dlt_template_id' => '1707161234567890123',
            'unicode_flag' => false,
            'content_template' => 'Namaste {#var#}, your puja {#var#} is confirmed for {#var#}. - Navdhya',
            'approval_status' => SmsDltTemplate::STATUS_APPROVED,
        ]);

        $registry = new SmsDltRegistry();
        $template = $registry->resolveTemplate('booking_confirmed');

        $this->assertSame('1701159123456789012', $template->pe_id);
        $this->assertSame('NAVDHY', $template->header);
        $this->assertSame('1707161234567890123', $template->dlt_template_id);
    }

    public function test_unregistered_template_throws_loud_exception_and_never_falls_through(): void
    {
        $registry = new SmsDltRegistry();

        $this->expectException(DltNotRegisteredException::class);
        $this->expectExceptionMessage('has no approved TRAI DLT registration (fail-closed)');

        $registry->resolveTemplate('unregistered_random_template_code');
    }

    public function test_hindi_unicode_detection(): void
    {
        $registry = new SmsDltRegistry();

        // English ASCII text -> not Unicode
        $asciiText = 'Your Satyanarayan Puja is scheduled.';
        $this->assertFalse($registry->isUnicode($asciiText));

        // Hindi Devanagari text -> detected as Unicode
        $hindiText = 'आपकी सत्यनारायण पूजा निर्धारित कर दी गई है।';
        $this->assertTrue($registry->isUnicode($hindiText));

        // Mixed text containing Devanagari -> detected as Unicode
        $mixedText = 'Namaste! आपकी पूजा confirmed. - Navdhya';
        $this->assertTrue($registry->isUnicode($mixedText));
    }
}

