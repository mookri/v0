<?php

namespace Tests\Platform\Api;

use App\Platform\Api\ErrorEnvelope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\KernelTestCase;

/**
 * JSON error envelope (doc 06 §4) + request-id middleware (doc 05 §11.1).
 */
final class ErrorEnvelopeTest extends KernelTestCase
{
    public function test_api_404_uses_envelope_with_stable_code(): void
    {
        $response = $this->getJson('/api/v1/definitely-missing');

        $this->assertEnvelopeCode($response, 404, 'not_found');
        $response->assertHeader('Navdhya-Request-Id');
    }

    public function test_validation_errors_use_validation_failed_code_with_fields(): void
    {
        Route::post('/api/v1/platform/test-validation', function (Request $request) {
            $request->validate(['email' => ['required', 'email']]);

            return response()->json(['ok' => true]);
        });

        $response = $this->postJson('/api/v1/platform/test-validation', ['email' => 'not-an-email']);

        $this->assertEnvelopeCode($response, 422, 'validation_failed');
        $response->assertJsonStructure(['error' => ['details' => ['fields' => ['email']]]]);
    }

    public function test_every_response_carries_request_id_header(): void
    {
        $response = $this->get('/up');

        $response->assertHeader('Navdhya-Request-Id');
    }

    public function test_client_supplied_request_id_is_echoed(): void
    {
        $response = $this->get('/up', ['Navdhya-Request-Id' => 'req_test_123']);

        $this->assertSame('req_test_123', $response->headers->get('Navdhya-Request-Id'));
    }

    public function test_invalid_client_request_id_is_replaced(): void
    {
        $response = $this->get('/up', ['Navdhya-Request-Id' => "bad id\ninjection"]);

        $this->assertNotSame("bad id\ninjection", $response->headers->get('Navdhya-Request-Id'));
        $this->assertMatchesRegularExpression('/^req_[0-9a-z]+$/', (string) $response->headers->get('Navdhya-Request-Id'));
    }

    public function test_renderer_yields_null_for_html_so_framework_rendering_applies(): void
    {
        // The admin shell catch-all turns unknown GETs into login redirects
        // by design, so the envelope contract is asserted directly: the
        // renderer must not envelop non-JSON requests (doc 06 §4 — Blade
        // keeps framework error pages).
        $request = Request::create('/definitely-missing', 'GET');
        $request->headers->set('Accept', 'text/html');

        $this->assertNull(ErrorEnvelope::render($request, new ModelNotFoundException()));

        // JSON-expecting requests are enveloped.
        $jsonRequest = Request::create('/definitely-missing', 'GET');
        $jsonRequest->headers->set('Accept', 'application/json');
        $jsonRequest->attributes->set('request_id', 'req_test_1');

        $response = ErrorEnvelope::render($jsonRequest, new ModelNotFoundException());

        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('not_found', $response->getData(true)['error']['code']);
        $this->assertSame('req_test_1', $response->getData(true)['error']['request_id']);
    }
}
