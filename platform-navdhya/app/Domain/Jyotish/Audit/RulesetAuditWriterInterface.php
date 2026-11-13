<?php

namespace App\Domain\Jyotish\Audit;

/**
 * Domain port for auditing ruleset mutations and generation status transitions.
 * Pure domain contract — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
interface RulesetAuditWriterInterface
{
    /**
     * @param array<string, mixed> $rules
     */
    public function recordRulesetCreated(string $rulesetCode, array $rules, ?string $actorId = null): void;

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function recordRulesetUpdated(string $rulesetCode, array $before, array $after, ?string $actorId = null): void;

    public function recordRulesetPromoted(string $rulesetCode, string $version, ?string $actorId = null): void;

    public function recordGenerationPromoted(int $generationNumber, string $rulesVersion, ?string $actorId = null): void;

    public function recordGenerationRetired(int $generationNumber, string $rulesVersion, ?string $actorId = null): void;
}

