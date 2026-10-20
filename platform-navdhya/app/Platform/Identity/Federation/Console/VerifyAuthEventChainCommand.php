<?php

namespace App\Platform\Identity\Federation\Console;

use App\Platform\Identity\Federation\Models\AuthEvent;
use Illuminate\Console\Command;

/**
 * Chain integrity verification (ADR-0020 D5): re-walks auth_events in
 * ULID order and re-derives each seal. Any recomputed-hash mismatch or
 * prev_hash discontinuity is tamper evidence — exits non-zero and names
 * the first broken row. Runs as a scheduled job and on demand for
 * investigations ("given a token reference + trace id" reconstructs
 * without ever touching a bearer token).
 */
final class VerifyAuthEventChainCommand extends Command
{
    protected $signature = 'auth:verify-chain
        {--from= : Start ULID (inclusive) for a partial walk}
        {--limit=2000 : Max rows per walk chunk}';

    protected $description = 'Verify the auth_events hash chain (ADR-0020 D5 tamper evidence)';

    public function handle(): int
    {
        $query = AuthEvent::query()->withoutGlobalScopes()->orderBy('id');

        if (is_string($this->option('from')) && $this->option('from') !== '') {
            $query->where('id', '>=', (string) $this->option('from'));
        }

        $prevHash = null;
        $checked = 0;

        foreach ($query->lazyById((int) $this->option('limit'), 'id') as $event) {
            // Recompute the seal through the shared canonical function —
            // the same normalization the recorder used pre-persist.
            $recomputed = AuthEvent::seal($event->getAttributes());

            if (! hash_equals($recomputed, (string) $event->hash)) {
                $this->error("Chain broken at {$event->id} ({$event->event_type}): seal mismatch — row was modified after write.");

                return self::FAILURE;
            }

            if (($event->getAttributes()['prev_hash'] ?? null) !== $prevHash) {
                $this->error("Chain broken at {$event->id} ({$event->event_type}): prev_hash discontinuity — a row was deleted or reordered.");

                return self::FAILURE;
            }

            $prevHash = $recomputed;
            $checked++;
        }

        $this->info("Chain intact: {$checked} events verified.");

        return self::SUCCESS;
    }
}
