<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\ActorTokenIssuer;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * Regression cover for the backend hardening pass (BACKEND-RCA 2026-10-21).
 *
 * Each test here pins a defect that was live in this repository and
 * would have silently come back.
 */
final class AuthHardeningTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['identity.hmac_secret' => 'auth-hardening-hmac-secret-32!!']);
    }

    /**
     * The per-client profile gate must apply to a client that
     * authenticates with HTTP Basic, not only to one that repeats
     * client_id in the body (RFC 6749 §2.3.1 forbids the repeat).
     */
    public function test_basic_auth_client_is_still_bound_to_the_profile_gate(): void
    {
        $tenant = $this->makeTenant();

        $client = new Client();
        $client->forceFill([
            'name' => 'Machine client',
            'secret' => 'machine-secret-12345',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_PARTNER,
            'tenant_id' => (string) $tenant->getKey(),
            'allowed_grants' => ['client_credentials'],
            // A DPoP-mandated client may not use a machine grant: the
            // machine grant has no DPoP binding seam.
            'require_dpop' => true,
        ]);

        $basic = base64_encode(((string) $client->getKey()).':machine-secret-12345');

        // Body-only client_id is gone: the client is identified by the
        // Basic header, exactly as league identifies it.
        $this->withHeader('Authorization', 'Basic '.$basic)
            ->postJson('/oauth/token', [
                'grant_type' => 'client_credentials',
                'scope' => '',
            ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'unsupported_grant');
    }

    /**
     * The same client, sending client_id in the body, is denied the
     * same way: the gate is not bypassable by the credential style.
     */
    public function test_body_client_id_is_denied_the_same_way(): void
    {
        $tenant = $this->makeTenant();

        $client = new Client();
        $client->forceFill([
            'name' => 'Machine client (body)',
            'secret' => 'machine-secret-12345',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_PARTNER,
            'tenant_id' => (string) $tenant->getKey(),
            'allowed_grants' => ['client_credentials'],
            'require_dpop' => true,
        ]);

        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'machine-secret-12345',
            'scope' => '',
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'unsupported_grant');
    }

    /**
     * The session cookie must be Secure in production. The flag used to
     * be read from an environment variable that was set nowhere, so it
     * was false everywhere - including production.
     */
    public function test_session_cookie_is_secure_in_production(): void
    {
        $restore = $this->setEnv('APP_ENV', 'production');

        try {
            $config = require base_path('config/session.php');
            $this->assertTrue($config['secure'], 'A production session cookie must be Secure.');
        } finally {
            $restore();
        }

        $restore = $this->setEnv('APP_ENV', 'local');

        try {
            $config = require base_path('config/session.php');
            $this->assertFalse($config['secure'], 'A local session cookie must not be Secure-only.');
        } finally {
            $restore();
        }
    }

    /**
     * An explicit override still wins, and an EMPTY value must not
     * silently disable the production default.
     */
    public function test_session_secure_cookie_override_is_honoured(): void
    {
        $restoreApp = $this->setEnv('APP_ENV', 'production');
        $restoreFlag = $this->setEnv('SESSION_SECURE_COOKIE', 'false');

        try {
            $config = require base_path('config/session.php');
            $this->assertFalse($config['secure']);
        } finally {
            $restoreFlag();
            $restoreApp();
        }

        $restoreApp = $this->setEnv('APP_ENV', 'production');
        $restoreFlag = $this->setEnv('SESSION_SECURE_COOKIE', '');

        try {
            $config = require base_path('config/session.php');
            $this->assertTrue($config['secure'], 'An empty value must fall back to the environment default.');
        } finally {
            $restoreFlag();
            $restoreApp();
        }
    }

    /**
     * An actor token must never be minted without a signing secret.
     */
    public function test_actor_token_issuer_refuses_an_empty_secret(): void
    {
        $this->expectException(\RuntimeException::class);

        ActorTokenIssuer::mint('', 'user-1', 60, gmdate('Y-m-d\TH:i:s.v\Z'));
    }

    /**
     * The auth-event chain seal is keyed, so an editor without the key
     * cannot produce a seal that verifies.
     */
    public function test_auth_event_seal_is_keyed(): void
    {
        config(['identity.chain_signing_key' => 'chain-key-a']);

        $event = app(AuthEventRecorder::class)->record([
            'event_type' => 'token.issued',
            'decision' => 'deny',
        ]);

        $row = $event->getAttributes();
        $this->assertContains((string) $event->hash, AuthEvent::seals($row));

        // A different key cannot re-seal the same row.
        config(['identity.chain_signing_key' => 'chain-key-b']);
        $this->assertNotContains((string) $event->hash, AuthEvent::seals($row));

        // A modified row never verifies.
        config(['identity.chain_signing_key' => 'chain-key-a']);
        $row['decision'] = 'allow';
        $this->assertNotContains((string) $event->hash, AuthEvent::seals($row));
    }

    /**
     * A tampered row breaks the chain verifier.
     */
    public function test_chain_verifier_detects_a_tampered_row(): void
    {
        config(['identity.chain_signing_key' => 'chain-key-c']);

        $event = app(AuthEventRecorder::class)->record([
            'event_type' => 'token.issued',
            'decision' => 'deny',
        ]);

        $this->assertSame(0, \Artisan::call('auth:verify-chain'));

        DB::connection('central')->table('auth_events')
            ->where('id', (string) $event->getKey())
            ->update(['decision' => 'allow']);

        $this->assertSame(1, \Artisan::call('auth:verify-chain'));
    }

    /**
     * A raw session id must never leave the process, whichever surface
     * issued it. The platform API's group-wide StartSession wrote the
     * plain id; the global middleware encrypts it on the way out.
     */
    public function test_raw_session_cookie_is_encrypted_on_the_way_out(): void
    {
        $encrypter = app('encrypter');
        $middleware = new \App\Platform\Http\Middleware\EncryptsSessionCookie($encrypter);
        $name = (string) config('session.cookie');
        $raw = str_repeat('a', 40);

        $response = new \Illuminate\Http\Response();
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie($name, $raw));

        $result = $middleware->handle(
            \Illuminate\Http\Request::create('/api/v1/platform/tenants'),
            fn () => $response,
        );

        $value = (string) $result->headers->getCookies()[0]->getValue();

        $this->assertNotSame($raw, $value, 'The raw session id must not leave the process.');
        $this->assertSame(
            $raw,
            \Illuminate\Cookie\CookieValuePrefix::validate(
                $name,
                $encrypter->decrypt($value, false),
                $encrypter->getAllKeys(),
            ),
            'The encrypted cookie must decrypt back to the session id.'
        );
    }

    /**
     * An already-encrypted cookie is left alone: no double encryption.
     */
    public function test_encrypted_session_cookie_is_not_encrypted_twice(): void
    {
        $encrypter = app('encrypter');
        $middleware = new \App\Platform\Http\Middleware\EncryptsSessionCookie($encrypter);
        $name = (string) config('session.cookie');
        $raw = str_repeat('b', 40);

        $encrypted = $encrypter->encrypt(
            \Illuminate\Cookie\CookieValuePrefix::create($name, $encrypter->getKey()).$raw,
            false,
        );

        $response = new \Illuminate\Http\Response();
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie($name, $encrypted));

        $result = $middleware->handle(
            \Illuminate\Http\Request::create('/api/v1/platform/tenants'),
            fn () => $response,
        );

        $this->assertSame(
            $encrypted,
            (string) $result->headers->getCookies()[0]->getValue(),
            'An already-encrypted cookie must not be encrypted again.'
        );
    }

    /**
     * Set an environment variable for the duration of a callback.
     */
    private function setEnv(string $key, string $value): callable
    {
        $previous = $_ENV[$key] ?? null;
        $previousServer = $_SERVER[$key] ?? null;
        $previousGetenv = getenv($key);

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);

        return function () use ($key, $previous, $previousServer, $previousGetenv): void {
            if ($previous === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $previous;
            }

            if ($previousServer === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $previousServer;
            }

            if ($previousGetenv === false) {
                putenv($key);
            } else {
                putenv($key.'='.$previousGetenv);
            }
        };
    }
}
