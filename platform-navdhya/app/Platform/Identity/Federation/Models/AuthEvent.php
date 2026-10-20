<?php

namespace App\Platform\Identity\Federation\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One append-only federation audit row (ADR-0020 D5). Never updated,
 * never deleted by application code; the chain seal covers every field
 * so silent column edits break `auth:verify-chain`.
 *
 * @property string $id
 * @property string $event_type
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property string|null $request_id
 * @property string|null $trace_id
 * @property string|null $actor_client_id
 * @property string|null $subject_ref
 * @property string|null $tenant_ref
 * @property string|null $session_ref
 * @property string|null $grant_type
 * @property string|null $source_audience
 * @property string|null $target_audience
 * @property array<int, string>|null $requested_scopes
 * @property array<int, string>|null $granted_scopes
 * @property array<int, string>|null $authentication_methods
 * @property string|null $authentication_assurance
 * @property string|null $risk_tier
 * @property string|null $policy_version
 * @property string $decision
 * @property string|null $reason_code
 * @property string|null $signing_key_id
 * @property string|null $token_jti_ref
 * @property string|null $prev_hash
 * @property string $hash
 */
final class AuthEvent extends Model
{
    public const UPDATED_AT = null;

    public $timestamps = false;

    // String ULID primary key: never let Eloquent substitute the
    // last-insert-id for the assigned id after create().
    public $incrementing = false;

    protected $keyType = 'string';

    protected $connection = 'central';

    protected $fillable = [
        'id', 'event_type', 'occurred_at', 'request_id', 'trace_id',
        'actor_client_id', 'subject_ref', 'tenant_ref', 'session_ref',
        'grant_type', 'source_audience', 'target_audience',
        'requested_scopes', 'granted_scopes', 'authentication_methods',
        'authentication_assurance', 'risk_tier', 'policy_version',
        'decision', 'reason_code', 'signing_key_id', 'token_jti_ref',
        'prev_hash', 'hash',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'requested_scopes' => 'array',
            'granted_scopes' => 'array',
            'authentication_methods' => 'array',
        ];
    }

    /**
     * The seal a row is written with.
     *
     * The chain seal is an HMAC over the canonical row, not a bare
     * hash: an unkeyed digest only proves that the row was not edited
     * *without* recomputing the hash, which anyone with write access to
     * the table can do. The key defaults to the identity HMAC secret
     * (which already fails closed when missing); a dedicated
     * `FEDERATION_CHAIN_SIGNING_KEY` may override it.
     *
     * @param  array<string, mixed>  $row  model attributes or recorder row
     */
    public static function seal(array $row): string
    {
        $key = self::chainKey();

        if ($key === '') {
            return self::digest($row, 1, null);
        }

        return self::digest($row, 2, $key);
    }

    /**
     * Every seal a stored row may legitimately carry.
     *
     * Rows written before the keyed chain existed carry the legacy
     * unkeyed digest, so the verifier accepts both. A row that matches
     * neither was tampered with.
     *
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    public static function seals(array $row): array
    {
        $seals = [self::digest($row, 1, null)];
        $key = self::chainKey();

        if ($key !== '') {
            $seals[] = self::digest($row, 2, $key);
        }

        return array_values(array_unique($seals));
    }

    /**
     * The key the chain seal is computed with.
     */
    public static function chainKey(): string
    {
        $key = (string) config('identity.chain_signing_key', '');

        if ($key !== '') {
            return $key;
        }

        return (string) config('identity.hmac_secret', '');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function digest(array $row, int $version, ?string $key): string
    {
        $json = (string) json_encode(self::canonical($row, $version), JSON_UNESCAPED_SLASHES);

        return $key === null
            ? hash('sha256', $json)
            : hash_hmac('sha256', $json, $key);
    }

    /**
     * Canonical seal input: fixed field order, datetime as ISO-8601,
     * json columns as decoded arrays. Both the recorder (pre-persist)
     * and the verifier (post-read) seal through this function, so
     * Eloquent's storage normalization (datetime format, json flags)
     * cannot desynchronize the two sides.
     *
     * @param  array<string, mixed>  $row  model attributes or recorder row
     * @return array<string, mixed>
     */
    private static function canonical(array $row, int $version): array
    {
        $get = static fn (string $key): mixed => $row[$key] ?? null;
        $iso = static function (mixed $value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            // Second precision: storage formats (datetime columns) drop
            // sub-second digits, so the seal must not depend on them.
            return \Illuminate\Support\Carbon::parse($value)->setMicrosecond(0)->toISOString();
        };
        $json = static function (mixed $value): mixed {
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            }

            return $value;
        };

        $canonical = [
            'id' => $get('id'),
            'event_type' => $get('event_type'),
            'occurred_at' => $iso($get('occurred_at')),
            'request_id' => $get('request_id'),
            'trace_id' => $get('trace_id'),
            'actor_client_id' => $get('actor_client_id'),
            'subject_ref' => $get('subject_ref'),
            'tenant_ref' => $get('tenant_ref'),
            'session_ref' => $get('session_ref'),
            'grant_type' => $get('grant_type'),
            'source_audience' => $get('source_audience'),
            'target_audience' => $get('target_audience'),
            'requested_scopes' => $json($get('requested_scopes')),
            'granted_scopes' => $json($get('granted_scopes')),
            'authentication_methods' => $json($get('authentication_methods')),
            'authentication_assurance' => $get('authentication_assurance'),
            'risk_tier' => $get('risk_tier'),
            'policy_version' => $get('policy_version'),
            'decision' => $get('decision'),
            'reason_code' => $get('reason_code'),
            'signing_key_id' => $get('signing_key_id'),
            'token_jti_ref' => $get('token_jti_ref'),
            'prev_hash' => $get('prev_hash'),
            'chain_v' => $version,
        ];

        return $canonical;
    }
}
