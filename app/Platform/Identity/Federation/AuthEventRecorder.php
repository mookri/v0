<?php

namespace App\Platform\Identity\Federation;

use App\Platform\Identity\Federation\Models\AuthEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Federation audit writer (ADR-0020 D5): append-only, hash-chained,
 * correlated, non-secret. Every authentication, token issuance/exchange,
 * authorization denial, refresh, revocation and privileged policy
 * change records through here.
 *
 * Pseudonymization is mandatory and structural: subject, session and
 * token-jti references are HMAC-truncated with the identity secret, so
 * raw user ids, session ids and tokens cannot enter the table even by
 * caller mistake — the recorder accepts live ids and stores refs. The
 * request seal covers every column, so silent edits break the chain.
 *
 * Fail-closed posture: writing throws on failure — an unauditable
 * authorization decision is not an acceptable outcome (D5).
 */
final class AuthEventRecorder
{
    /**
     * @param  array{
     *     event_type: string,
     *     decision: 'allow'|'deny'|'error',
     *     subject_id?: string|null,
     *     session_id?: string|null,
     *     token_jti?: string|null,
     *     actor_client_id?: string|null,
     *     tenant_ref?: string|null,
     *     grant_type?: string|null,
     *     source_audience?: string|null,
     *     target_audience?: string|null,
     *     requested_scopes?: list<string>|null,
     *     granted_scopes?: list<string>|null,
     *     authentication_methods?: list<string>|null,
     *     authentication_assurance?: string|null,
     *     risk_tier?: string|null,
     *     policy_version?: string|null,
     *     reason_code?: string|null,
     *     signing_key_id?: string|null,
     *     trace_id?: string|null,
     * } $event
     */
    public function record(array $event): AuthEvent
    {
        $occurredAt = now();
        $id = (string) Str::ulid();

        // Chain seal over the previous row (bypass scopes deliberately —
        // the chain is global by ULID order, matching verify-chain).
        $prevHash = AuthEvent::query()
            ->withoutGlobalScopes()
            ->orderByDesc('id')
            ->value('hash');

        $row = [
            'id' => $id,
            'event_type' => $event['event_type'],
            'occurred_at' => $occurredAt->toISOString(),
            'request_id' => $this->requestId(),
            'trace_id' => $event['trace_id'] ?? null,
            'actor_client_id' => $event['actor_client_id'] ?? null,
            'subject_ref' => self::ref('sub', $event['subject_id'] ?? null),
            'tenant_ref' => $event['tenant_ref'] ?? null,
            'session_ref' => self::ref('sess', $event['session_id'] ?? null),
            'grant_type' => $event['grant_type'] ?? null,
            'source_audience' => $event['source_audience'] ?? null,
            'target_audience' => $event['target_audience'] ?? null,
            'requested_scopes' => $event['requested_scopes'] ?? null,
            'granted_scopes' => $event['granted_scopes'] ?? null,
            'authentication_methods' => $event['authentication_methods'] ?? null,
            'authentication_assurance' => $event['authentication_assurance'] ?? null,
            'risk_tier' => $event['risk_tier'] ?? null,
            'policy_version' => $event['policy_version'] ?? null,
            'decision' => $event['decision'],
            'reason_code' => $event['reason_code'] ?? null,
            'signing_key_id' => $event['signing_key_id'] ?? null,
            'token_jti_ref' => self::ref('jti', $event['token_jti'] ?? null),
            'prev_hash' => $prevHash,
        ];

        $row['hash'] = AuthEvent::seal($row);

        return AuthEvent::query()->create($row);
    }

    /**
     * HMAC-truncated pseudonymous reference. 'ref_kind:sha256(hmac)[0:32]'
     * — kinded so refs never cross-reference (a session id cannot be
     * mistaken for a subject id), truncated so even a secret compromise
     * does not yield a reversible identifier from the table alone.
     */
    public static function ref(string $kind, ?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $secret = (string) config('identity.hmac_secret', '');

        // Fail closed: pseudonymization is mandatory for D5 tables, so a
        // missing secret is a broken deployment, not a pass-through.
        if ($secret === '') {
            throw new \RuntimeException(
                'identity.hmac_secret is required: auth_events identifiers must be HMAC-pseudonymous (ADR-0020 D5).'
            );
        }

        $hmac = hash_hmac('sha256', $kind.'|'.$raw, $secret);

        return $kind.':'.substr($hmac, 0, 32);
    }

    private function requestId(): ?string
    {
        $request = app('request');

        if (! $request instanceof Request) {
            return null;
        }

        $requestId = $request->attributes->get('request_id');

        return is_string($requestId) && $requestId !== '' ? substr($requestId, 0, 64) : null;
    }
}
