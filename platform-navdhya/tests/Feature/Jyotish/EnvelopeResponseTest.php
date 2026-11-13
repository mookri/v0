<?php

namespace Tests\Feature\Jyotish;

use App\Domain\Jyotish\Profiles\JyotishProfile;
use App\Modules\Jyotish\Http\JyotishProblemResponder;
use App\Modules\Jyotish\Http\Presenters\JyotishResultEnvelope;
use Tests\TestCase;

final class EnvelopeResponseTest extends TestCase
{
    public function test_result_envelope_matches_standard_specification_structure(): void
    {
        $profile = new JyotishProfile(
            profile_code: 'parashari-lahiri-sripati',
            ayanamsa_code: 'lahiri',
            house_system: 'sripati',
            node_mode: 'mean',
            rules_version: 2
        );

        $response = JyotishResultEnvelope::create(
            toolId: 'jyotish.chart.d1',
            data: ['lagna' => 125.4],
            profile: $profile,
            rulesetCode: 'parashari.d1',
            verificationStatus: 'golden_tested',
            warnings: ['birth_time_approximated'],
            unsupported: []
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertArrayHasKey('calculation_id', $payload);
        $this->assertSame('jyotish.chart.d1', $payload['tool_id']);
        $this->assertSame('completed', $payload['status']);
        $this->assertSame(125.4, $payload['data']['lagna']);
        $this->assertSame('parashari', $payload['methodology']['system']);
        $this->assertSame('lahiri', $payload['methodology']['ayanamsa']);
        $this->assertSame('sripati', $payload['methodology']['house_system']);
        $this->assertSame('golden_tested', $payload['quality']['verification_status']);
        $this->assertContains('birth_time_approximated', $payload['quality']['warnings']);
    }

    public function test_problem_responder_outputs_rfc_7807_structure(): void
    {
        $response = JyotishProblemResponder::create(
            status: 422,
            code: 'ambiguous_place',
            title: 'Ambiguous Birth Place',
            detail: 'Multiple geographic coordinates match the provided location name.',
            extra: ['candidates' => ['Varanasi, UP', 'Varanasi, Bihar']]
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('https://api.navdhya.com/problems/ambiguous_place', $payload['type']);
        $this->assertSame('ambiguous_place', $payload['code']);
        $this->assertCount(2, $payload['candidates']);
    }
}

