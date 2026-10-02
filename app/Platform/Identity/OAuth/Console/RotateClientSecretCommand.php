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

        // Passport memoizes client rows per process (once): flush so
        // in-process readers (Octane, queue workers) see the new secret.
        \Illuminate\Support\Once::flush();

        $this->info('Secret rotated for client: '.(string) $client->getKey());
        $this->line('New secret (shown once): '.$secret);
        $this->line($accessIds->count().' live token(s) killed.');

        return self::SUCCESS;
    }
}
