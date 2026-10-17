<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\OAuth\IssuerIdentifier;
use Tests\TenantIntegrationTestCase;

/**
 * 9a (ADR-0021): discovery surfaces the new protocol facts — the PAR
 * endpoint advertisement (D1: an endpoint, never a server-wide
 * `require_pushed_request_parameters` mandate), the RFC 9207 `iss`
 * support flag (D2), and the OIDC-only fields (RS256, openid, nonce —
 * D3). Every document derives its issuer from the single shared
 * IssuerIdentifier, byte-equal to the `iss` response parameter and ID
 * token `iss`.
 */
final class DiscoveryMetadataTest extends TenantIntegrationTestCase
{
    public function test_both_documents_advertise_par_and_iss_support_with_the_shared_issuer(): void
    {
        config(['federation.issuer' => 'https://auth.example.test/']);

        $oauth = $this->getJson('/.well-known/oauth-authorization-server')->assertOk()->json();
        $oidc = $this->getJson('/.well-known/openid-configuration')->assertOk()->json();

        $issuer = IssuerIdentifier::value();
        $this->assertSame('https://auth.example.test', $issuer);

        foreach ([$oauth, $oidc] as $document) {
            $this->assertSame($issuer, $document['issuer']);
            $this->assertSame($issuer.'/oauth/authorize', $document['authorization_endpoint']);
            $this->assertSame($issuer.'/oauth/token', $document['token_endpoint']);
            $this->assertSame($issuer.'/api/oauth/par', $document['pushed_authorization_request_endpoint']);
            $this->assertTrue($document['request_uri_parameter_supported']);
            $this->assertTrue($document['authorization_response_iss_parameter_supported']);

            // D1: PAR is a per-client mandate (N2 only) — a server-wide
            // flag would misdescribe N1 clients that never push.
            $this->assertArrayNotHasKey('require_pushed_request_parameters', $document);
        }

        // One derivation, both documents byte-identical at the issuer.
        $this->assertSame($oauth['issuer'], $oidc['issuer']);
    }

    public function test_openid_configuration_carries_the_oidc_fields(): void
    {
        $oidc = $this->getJson('/.well-known/openid-configuration')->assertOk()->json();

        $this->assertSame(['RS256'], $oidc['id_token_signing_alg_values_supported']);
        $this->assertSame(['openid'], $oidc['scopes_supported']);
        $this->assertTrue($oidc['nonce_supported']);
        $this->assertSame(['public'], $oidc['subject_types_supported']);
    }

    public function test_documents_fall_back_to_the_app_url_when_no_issuer_is_configured(): void
    {
        // Direct-mode deployment: no federation.issuer ⇒ the historical
        // app-url base, still one shared derivation across both docs,
        // the PAR endpoint, the RFC 9207 `iss` and ID-token minting.
        config(['federation.issuer' => '', 'app.url' => 'https://navdhya.test']);

        $oauth = $this->getJson('/.well-known/oauth-authorization-server')->assertOk()->json();
        $oidc = $this->getJson('/.well-known/openid-configuration')->assertOk()->json();

        $this->assertSame('https://navdhya.test', IssuerIdentifier::value());
        $this->assertSame('https://navdhya.test', $oauth['issuer']);
        $this->assertSame('https://navdhya.test', $oidc['issuer']);
        $this->assertSame(
            'https://navdhya.test/api/oauth/par',
            $oidc['pushed_authorization_request_endpoint']
        );
    }

    public function test_documents_serve_on_any_host_without_federation_gating(): void
    {
        // Discovery is protocol surface, not auth surface: no host
        // gate, no session, no auth — a plain GET gets the same JSON.
        $this->get('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('issuer', IssuerIdentifier::value());

        $this->get('/.well-known/openid-configuration')
            ->assertOk()
            ->assertJsonPath('issuer', IssuerIdentifier::value());
    }
}
