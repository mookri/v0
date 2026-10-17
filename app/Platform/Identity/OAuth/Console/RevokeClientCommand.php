<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use Illuminate\Console\Command;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * Revoke an OAuth client: `platform:client:revoke {id}`. Revocation
 * kills the client row, disables its profile, revokes every live
 * token for the client, and kills its outstanding device pairings
 * (bulk trigger, SEC-015 §8.1) — a compromised terminal dies
 * completely, not just at issuance.
 */
final class RevokeClientCommand extends Command
{
    protected $signature = 'platform:client:revoke
        {id : OAuth client id}';

    protected $description = 'Revoke an OAuth client, its profile, and all of its live tokens';

    public function handle(): int
    {
        $client = Client::query()->whereKey((string) $this->argument('id'))->first();

        if ($client === null) {
            $this->error('Client not found.');

            return self::FAILURE;
        }

        $client->forceFill(['revoked' => true])->save();

        OAuthClientProfile::query()
            ->where('client_id', (string) $client->getKey())
            ->update(['status' => 'disabled']);

        $accessIds = Passport::token()->newQuery()
            ->where('client_id', (string) $client->getKey())
            ->where('revoked', false)
            ->pluck('id');

        Passport::token()->newQuery()->whereIn('id', $accessIds)->update(['revoked' => true]);
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $accessIds)->update(['revoked' => true]);

        // Outstanding terminal pairings die too (pending codes can
        // otherwise still be approved and polled into fresh tokens).
        $deviceModel = Passport::deviceCodeModel();
        $devicesKilled = $deviceModel::query()
            ->where('client_id', (string) $client->getKey())
            ->where('revoked', false)
            ->update(['revoked' => true]);

        // D2 (9c): a console revocation is a first-party revocation
        // site exactly like the HTTP mirror — the shared status store
        // makes cross-process invalidation immediate, so the bound
        // degrades to 0 s here too, not the 30 s TTL.
        $revocationCache = app(\App\Platform\Identity\Federation\RevocationStatusCache::class);
        $revocationCache->invalidateClient((string) $client->getKey());
        $revocationCache->invalidateTokens($accessIds->all());

        // Passport memoizes client rows per process (once): flush so
        // in-process readers (Octane, queue workers) see the revocation.
        \Illuminate\Support\Once::flush();

        // D5: the kill lands on the federation chain per credential.
        $recorder = app(\App\Platform\Identity\Federation\AuthEventRecorder::class);
        foreach ($accessIds as $jti) {
            $recorder->record([
                'event_type' => 'token.revoked',
                'decision' => 'allow',
                'actor_client_id' => (string) $client->getKey(),
                'token_jti' => (string) $jti,
                'reason_code' => 'client_revoked',
            ]);
        }

        $this->info('Client revoked: '.(string) $client->getKey().' ('.$accessIds->count().' live tokens, '.$devicesKilled.' device codes killed).');

        return self::SUCCESS;
    }
}
