<?php

namespace Tests\Platform\Identity;

use Tests\TenantIntegrationTestCase;

/**
 * OAuth discovery surfaces (SEC-002 §2.1, partial): RFC 8414 metadata,
 * JWKS keys, and the credential-bearing userinfo endpoint.
 */
final class DiscoveryTest extends TenantIntegrationTestCase
{
    public function test_metadata_advertises_supported_flows_only(): void
    {
        $metadata = $this->getJson('/.well-known/oauth-authorization-server')->assertOk()->json();

        $this->assertSame(['code'], $metadata['response_types_supported']);
        $this->assertEqualsCanonicalizing(
            ['authorization_code', 'refresh_token', 'client_credentials'],
            $metadata['grant_types_supported']
        );
        $this->assertStringEndsWith('/oauth/token', $metadata['token_endpoint']);
        $this->assertStringEndsWith('/oauth/userinfo', $metadata['userinfo_endpoint']);
        $this->assertStringEndsWith('/.well-known/jwks.json', $metadata['jwks_uri']);
    }

    public function test_jwks_publishes_rsa_key(): void
    {
        $response = $this->getJson('/.well-known/jwks.json')->assertOk();
        $keys = $response->json('keys');

        $this->assertCount(1, $keys);
        $this->assertSame('RSA', $keys[0]['kty']);
        $this->assertSame('sig', $keys[0]['use']);
        $this->assertSame('RS256', $keys[0]['alg']);
        $this->assertNotEmpty($keys[0]['n']);
        $this->assertNotEmpty($keys[0]['e']);
        $this->assertStringContainsString('max-age=3600', (string) $response->headers->get('Cache-Control'));
    }

    public function test_userinfo_serves_identity_claims(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->get('/.well-known/oauth-authorization-server')->assertOk();

        $claims = $this->actingAs($user)->getJson('/oauth/userinfo')->assertOk()->json();

        $this->assertSame((string) $user->external_id !== '' && $user->external_id !== null
            ? (string) $user->external_id
            : (string) $user->getAuthIdentifier(), $claims['sub']);
        $this->assertSame($user->email, $claims['email']);
        $this->assertArrayHasKey('email_verified', $claims);
        $this->assertArrayHasKey('phone_number_verified', $claims);
    }

    public function test_userinfo_denies_guests(): void
    {
        $this->getJson('/oauth/userinfo')->assertUnauthorized();
    }
}
