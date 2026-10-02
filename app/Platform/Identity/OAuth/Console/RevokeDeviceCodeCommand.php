<?php

namespace App\Platform\Identity\OAuth\Console;

use Illuminate\Console\Command;
use Laravel\Passport\Passport;

/**
 * Revoke an OAuth device code: `platform:device:revoke {user-code}`.
 * Kills a stuck or compromised terminal pairing before approval (or
 * before the next poll consumes it). Idempotent: already-revoked
 * codes succeed with a note, unknown codes fail (typo guard for
 * operators reading codes off screens).
 */
final class RevokeDeviceCodeCommand extends Command
{
    protected $signature = 'platform:device:revoke
        {user-code : Device user_code as shown on the terminal screen}';

    protected $description = 'Revoke an OAuth device code (kills the pairing)';

    public function handle(): int
    {
        $userCode = strtoupper(trim((string) $this->argument('user-code')));

        $model = Passport::deviceCodeModel();
        $code = $model::query()->where('user_code', $userCode)->first();

        if ($code === null) {
            $this->error('Device code not found.');

            return self::FAILURE;
        }

        if ((bool) $code->getAttribute('revoked')) {
            $this->info('Device code already revoked: '.$userCode);

            return self::SUCCESS;
        }

        $code->forceFill(['revoked' => true])->save();

        \Illuminate\Support\Once::flush();

        $this->info('Device code revoked: '.$userCode);

        return self::SUCCESS;
    }
}
