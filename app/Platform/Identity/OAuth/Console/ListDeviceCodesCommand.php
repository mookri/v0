<?php

namespace App\Platform\Identity\OAuth\Console;

use Illuminate\Console\Command;
use Laravel\Passport\Passport;

/**
 * List OAuth device codes: `platform:device:list [--client=] [--stale]`.
 * User codes print masked (first/last 2 chars — enough to correlate
 * with a terminal screen, never enough to hijack approval). `--stale`
 * shows only expired/revoked rows (purge candidates); otherwise live
 * and terminal rows both list with a state column.
 */
final class ListDeviceCodesCommand extends Command
{
    protected $signature = 'platform:device:list
        {--client= : Only codes for this OAuth client id}
        {--stale : Only expired or revoked codes}';

    protected $description = 'List OAuth device codes with state (user codes masked)';

    public function handle(): int
    {
        $model = Passport::deviceCodeModel();

        $query = $model::query()->orderByDesc('expires_at');

        $client = (string) ($this->option('client') ?? '');

        if ($client !== '') {
            $query->where('client_id', $client);
        }

        $rows = $query->get()->map(fn (mixed $code): array => [
            'user_code' => self::mask((string) $code->getAttribute('user_code')),
            'client_id' => (string) $code->getAttribute('client_id'),
            'state' => self::state($code),
            'expires_at' => (string) $code->getAttribute('expires_at'),
            'last_polled_at' => (string) ($code->getAttribute('last_polled_at') ?? '—'),
        ]);

        if ((bool) $this->option('stale')) {
            $rows = $rows->filter(static fn (array $row): bool => in_array($row['state'], ['expired', 'revoked'], true))->values();
        }

        if ($rows->isEmpty()) {
            $this->info('No device codes found.');

            return self::SUCCESS;
        }

        $this->table(['user_code', 'client_id', 'state', 'expires_at', 'last_polled_at'], $rows->all());

        return self::SUCCESS;
    }

    private static function mask(string $userCode): string
    {
        if (strlen($userCode) <= 4) {
            return '****';
        }

        return substr($userCode, 0, 2).'****'.substr($userCode, -2);
    }

    private static function state(mixed $code): string
    {
        if ((bool) $code->getAttribute('revoked')) {
            // League stores user denial as revocation — denied codes
            // read as revoked here (polls still answer access_denied
            // from grant state, unaffected by this label).
            return 'revoked';
        }

        if ($code->getAttribute('user_approved_at') !== null) {
            return 'approved';
        }

        $expiresAt = $code->getAttribute('expires_at');

        if ($expiresAt !== null && strtotime((string) $expiresAt) <= time()) {
            return 'expired';
        }

        return 'pending';
    }
}
