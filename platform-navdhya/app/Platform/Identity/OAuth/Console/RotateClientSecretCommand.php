<?php

namespace App\Platform\Identity\OAuth\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * Rotate an OAuth client secret: `platform:client:rotate-secret {id}`.
 * The new secret prints ONCE (hashed at rest); every live token for the
 * client dies with the old secret (bulk trigger, SEC-015 §8.1), so
 * rotation is a clean break, not an overlap.
 */
final class RotateClientSecretCommand extends Command
{
    protected $signature = 'platform:client:rotate-secret
        {id : OAuth client id}';

    protected $description = 'Rotate an OAuth client secret, killing all live tokens (secret shown once)';

    public function handle(): int
    {
        $client = Client::query()->whereKey((string) $this->argument('id'))->first();

        if ($client === null || (bool) $client->getAttribute('revoked')) {
            $this->error('Client not found or revoked.');

            return self::FAILURE;
        }

        $secret = Str::random(48);
        $client->forceFill(['secret' => $secret])->save();

        $accessIds = Passport::token()->newQuery()
            ->where('client_id', (string) $client->getKey())
            ->where('revoked', false)
            ->pluck('id');

        Passport::token()->newQuery()->whereIn('id', $accessIds)->update(['revoked' => true]);
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $accessIds)->update(['revoked' => true]);

        // D2 (9c): the console rotation is a first-party revocation
        // site — invalidated immediately (0 s, not the 30 s TTL). The
        // client row stays live (only its secret changed), so no
        // invalidateClient here: liveness is unaffected.
        app(\App\Platform\Identity\Federation\RevocationStatusCache::class)
            ->invalidateTokens($accessIds->all());

        // Passport memoizes client rows per process (once): flush so
        // in-process readers (Octane, queue workers) see the new secret.
        \Illuminate\Support\Once::flush();

        // D5: the rotation sweep lands on the federation chain.
        $recorder = app(\App\Platform\Identity\Federation\AuthEventRecorder::class);
        foreach ($accessIds as $jti) {
            $recorder->record([
                'event_type' => 'token.revoked',
                'decision' => 'allow',
                'actor_client_id' => (string) $client->getKey(),
                'token_jti' => (string) $jti,
                'reason_code' => 'secret_rotated',
            ]);
        }

        $this->info('Secret rotated for client: '.(string) $client->getKey());
        $this->line('New secret (shown once): '.$secret);
        $this->line($accessIds->count().' live token(s) killed.');

        return self::SUCCESS;
    }
}
