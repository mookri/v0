<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\OAuth\ClientAuthKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Revoke a client authentication key:
 * `platform:client:revoke-key {id} {kid}` (ADR-0020 D3). The
 * compromise response for one BFF: its registered key stops
 * authenticating immediately (revoked_at is part of every verification
 * lookup), while the row itself survives for forensics and the audit
 * chain records who revoked which key, when. Idempotent — re-revoking
 * is a warning, not an error. Row + audit share one transaction (D5).
 */
final class RevokeClientKeyCommand extends Command
{
    protected $signature = 'platform:client:revoke-key
        {id : OAuth client id}
        {kid : Key id to revoke}';

    protected $description = 'Revoke a registered client authentication key (immediate, row kept for forensics)';

    public function handle(): int
    {
        $clientId = (string) $this->argument('id');
        $kid = (string) $this->argument('kid');

        $key = ClientAuthKey::query()
            ->where('client_id', $clientId)
            ->where('kid', $kid)
            ->first();

        if ($key === null) {
            $this->error('No such key registered for this client.');

            return self::FAILURE;
        }

        if ($key->revoked_at !== null) {
            $this->warn('Key already revoked at '.$key->revoked_at->toIso8601String().'.');

            return self::SUCCESS;
        }

        try {
            DB::connection('central')->transaction(function () use ($key, $clientId, $kid): void {
                $key->forceFill(['revoked_at' => now()])->save();

                app(AuthEventRecorder::class)->record([
                    'event_type' => 'client_key.revoked',
                    'decision' => 'allow',
                    'actor_client_id' => $clientId,
                    'signing_key_id' => $kid,
                    'reason_code' => 'operator_revoke',
                ]);
            });
        } catch (\Throwable $e) {
            $this->error('Revocation failed (rolled back): '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Revoked client auth key: client '.$clientId.' kid '.$kid);

        return self::SUCCESS;
    }
}
