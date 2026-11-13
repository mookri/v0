<?php

namespace App\Domain\Jyotish\Contracts;

/**
 * Contract for versioned, scripture-referenced traditional rulesets.
 * Pure domain contract — zero HTTP or framework dependencies.
 */
interface ResolvesRuleSet
{
    /**
     * Unique identifier for the ruleset (e.g. "parashari.dignity", "jaimini.karaka").
     */
    public function getRulesetCode(): string;

    /**
     * Integer version for the ruleset (ADR-0035 immutability).
     */
    public function getRulesVersion(): int;

    /**
     * Classical text and chapter provenance citation.
     */
    public function getSourceReference(): string;

    /**
     * School/tradition (e.g. "parashari", "jaimini", "kp", "tajika").
     */
    public function getSchool(): string;

    /**
     * Retrieve parsed rule dataset.
     *
     * @return array<string, mixed>
     */
    public function getRulePayload(): array;
}

