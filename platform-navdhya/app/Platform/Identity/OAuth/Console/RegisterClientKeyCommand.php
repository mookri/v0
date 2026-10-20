<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\OAuth\ClientAuthKey;
use App\Platform\Identity\OAuth\Jwk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;

/**
 * Register a client authentication public key:
 * `platform:client:register-key {id} --public-key= [--kid=]` (ADR-0020
 * D3). Accepts ONLY the public half of an RSA key pair — the private
 * half never touches this server. The row is scoped to that client
 * alone (lookup predicate = blast-radius boundary), and registration /
 * duplicate-kid denial are recorded on the hash-chained auth_events
 * chain: key administration is attributable and audited.
 *
 * Both writes (row + audit) share one transaction: an unauditable key
 * registration is not an acceptable outcome (D5 fail-closed).
 */
final class RegisterClientKeyCommand extends Command
{
    protected $signature = 'platform:client:register-key
        {id : OAuth client id}
        {--public-key= : Path to the public key PEM (SPKI, RSA)}
        {--kid= : Key id (defaults to the RFC 7638 thumbprint of the key)}';

    protected $description = 'Register a client authentication public key for private_key_jwt (public half only)';

    public function handle(): int
    {
        $clientId = (string) $this->argument('id');
        $client = Client::query()->whereKey($clientId)->first();

        if ($client === null || (bool) $client->getAttribute('revoked')) {
            $this->error('Client not found or revoked.');

            return self::FAILURE;
        }

        $path = (string) ($this->option('public-key') ?: '');

        if ($path === '' || ! is_file($path)) {
            $this->error('A readable --public-key PEM file is required.');

            return self::FAILURE;
        }

        $pem = file_get_contents($path);

        if (! is_string($pem) || trim($pem) === '') {
            $this->error('Public key file is empty or unreadable.');

            return self::FAILURE;
        }

        $jwk = Jwk::fromPem($pem);

        if ($jwk === null) {
            $this->error('Not a usable RSA public key (SPKI PEM required).');

            return self::FAILURE;
        }

        $kid = (string) ($this->option('kid') ?: $jwk['kid']);

        if (ClientAuthKey::query()->where('client_id', $clientId)->where('kid', $kid)->exists()) {
            // A duplicate kid is an administrative conflict — audit the
            // denial so replacement attempts are visible (D5).
            try {
                app(AuthEventRecorder::class)->record([
                    'event_type' => 'client_key.registered',
                    'decision' => 'deny',
                    'actor_client_id' => $clientId,
                    'signing_key_id' => $kid,
                    'reason_code' => 'kid_already_registered',
                ]);
            } catch (\Throwable $e) {
                $this->error('Audit write failed: '.$e->getMessage());

                return self::FAILURE;
            }

            $this->error('Key id already registered for this client (revoke first).');

            return self::FAILURE;
        }

        try {
            DB::connection('central')->transaction(function () use ($clientId, $kid, $pem): void {
                ClientAuthKey::query()->create([
                    'client_id' => $clientId,
                    'kid' => $kid,
                    'public_key' => $pem,
                ]);

                app(AuthEventRecorder::class)->record([
                    'event_type' => 'client_key.registered',
                    'decision' => 'allow',
                    'actor_client_id' => $clientId,
                    // D3 key administration rows carry the affected
                    // key's id here (signing or client-auth key).
                    'signing_key_id' => $kid,
                ]);
            });
        } catch (\Throwable $e) {
            $this->error('Registration failed (rolled back): '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Registered client auth key: client '.$clientId.' kid '.$kid);

        return self::SUCCESS;
    }
}
