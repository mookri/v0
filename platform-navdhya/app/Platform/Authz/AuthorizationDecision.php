<?php

namespace App\Platform\Authz;

use App\Platform\Authz\Contracts\DecidingStage;
use InvalidArgumentException;

/**
 * K-013 AuthorizationDecision: the outcome of one SEC-022 chain
 * evaluation — decision, deciding stage, the evaluated policy ids and
 * the full per-stage trace consumed by the explain surface (T-0K01-02).
 * `logged` is set by the decision log (T-0K01-03), never by the
 * evaluator itself.
 */
final class AuthorizationDecision
{
    public const DECISION_ALLOW = 'allow';

    public const DECISION_DENY = 'deny';

    /**
     * @param  list<string>  $evaluatedPolicies
     * @param  list<array<string, mixed>>  $stages
     */
    public function __construct(
        public readonly string $decision,
        public readonly DecidingStage $decidingStage,
        public readonly string $capability,
        public readonly ?string $principalId,
        public readonly array $evaluatedPolicies = [],
        public readonly array $stages = [],
        public readonly bool $cached = false,
        public readonly bool $logged = false,
    ) {
    }

    public function allowed(): bool
    {
        return $this->decision === self::DECISION_ALLOW;
    }

    public function withCached(bool $cached = true): self
    {
        return new self(
            $this->decision,
            $this->decidingStage,
            $this->capability,
            $this->principalId,
            $this->evaluatedPolicies,
            $this->stages,
            $cached,
            $this->logged,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'object' => 'authz_decision',
            'decision' => $this->decision,
            'deciding_stage' => $this->decidingStage->value,
            'capability' => $this->capability,
            'principal_id' => $this->principalId,
            'evaluated_policies' => $this->evaluatedPolicies,
            'stages' => $this->stages,
            'cached' => $this->cached,
            'logged' => $this->logged,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $payload): self
    {
        $decision = $payload['decision'] ?? null;
        $stage = $payload['deciding_stage'] ?? null;

        if (! is_string($decision) || ! is_string($stage)) {
            throw new InvalidArgumentException('Invalid authorization decision payload.');
        }

        return new self(
            decision: $decision,
            decidingStage: DecidingStage::from($stage),
            capability: (string) ($payload['capability'] ?? ''),
            principalId: isset($payload['principal_id'])
                ? (string) $payload['principal_id']
                : null,
            evaluatedPolicies: self::stringList($payload['evaluated_policies'] ?? []),
            stages: self::stageList($payload['stages'] ?? []),
            cached: (bool) ($payload['cached'] ?? false),
            logged: (bool) ($payload['logged'] ?? false),
        );
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $list = [];

        foreach ($values as $value) {
            if (is_string($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function stageList(mixed $stages): array
    {
        if (! is_array($stages)) {
            return [];
        }

        $list = [];

        foreach ($stages as $stage) {
            if (is_array($stage)) {
                $list[] = $stage;
            }
        }

        return $list;
    }
}
