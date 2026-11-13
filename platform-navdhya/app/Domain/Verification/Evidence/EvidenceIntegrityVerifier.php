<?php

namespace App\Domain\Verification\Evidence;

use App\Platform\Audit\AuditWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Re-computes SHA-256 on document bytes prior to reviewer streaming or decisioning (Closes T-04).
 */
final class EvidenceIntegrityVerifier
{
    public function __construct(private readonly AuditWriter $audit)
    {
    }

    /**
     * @return array{ok: bool, state: string, computed_sha256: string|null, reason: string|null}
     */
    public function verifyRaw(string $key, string $expected): array
    {
        $disk = Storage::disk((string) config('filesystems.default', 'local'));

        if ($key === '' || ! $disk->exists($key)) {
            return ['ok' => false, 'state' => 'missing', 'computed_sha256' => null, 'reason' => 'object_missing'];
        }

        $stream = $disk->readStream($key);
        if (! is_resource($stream)) {
            return ['ok' => false, 'state' => 'missing', 'computed_sha256' => null, 'reason' => 'stream_unreadable'];
        }

        $ctx = hash_init('sha256');
        while (! feof($stream)) {
            $chunk = fread($stream, 65536);
            if ($chunk !== false && $chunk !== '') {
                hash_update($ctx, $chunk);
            }
        }
        fclose($stream);
        $computed = hash_final($ctx);

        if ($expected === '' || ! hash_equals(strtolower($expected), strtolower($computed))) {
            return ['ok' => false, 'state' => 'corrupted', 'computed_sha256' => $computed, 'reason' => 'sha256_mismatch'];
        }

        return ['ok' => true, 'state' => 'verified', 'computed_sha256' => $computed, 'reason' => null];
    }

    /**
     * @return array{ok: bool, state: string, computed_sha256: string|null, reason: string|null}
     */
    public function verifyDocument(Model $document, bool $auditFailures = true): array
    {
        $key = (string) $document->getAttribute('object_key');
        $expected = (string) ($document->getAttribute('sha256') ?? '');
        $result = $this->verifyRaw($key, $expected);
        $now = now();

        $document->forceFill([
            'integrity_state' => $result['state'],
            'integrity_last_checked_at' => $now,
        ])->save();

        if (! $result['ok'] && $auditFailures && in_array($result['state'], ['missing', 'corrupted'], true)) {
            $this->recordFailure($document, $result['state'], $result['computed_sha256']);
        }

        return $result;
    }

    private function recordFailure(Model $document, string $state, ?string $computedSha): void
    {
        $this->audit->record(
            'onboarding.document.integrity_failed',
            [
                'module' => 'onboarding',
                'resource_type' => 'application_document',
                'resource_id' => (string) $document->getKey(),
            ],
            ['expected_sha256' => $document->getAttribute('sha256')],
            ['integrity_state' => $state, 'computed_sha256' => $computedSha, 'application_id' => $document->getAttribute('application_id')]
        );
    }
}
