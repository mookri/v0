<?php

namespace App\Domain\Verification\Risk;

use InvalidArgumentException;

/**
 * Governance registry for risk signals (§20 R-09 / T-TR07-01.3).
 * Every signal MUST declare a lawful_justification and is barred from using caste,
 * sect, belief, region, or accent attributes.
 */
final class RiskSignalRegistry
{
    private const PROHIBITED_ATTRIBUTES = [
        'caste',
        'varna',
        'gotra',
        'sect',
        'religion',
        'belief',
        'region',
        'state_of_origin',
        'mother_tongue',
        'accent',
        'surname_caste',
    ];

    /** @var array<string, array{code: string, lawful_justification: string, category: string}> */
    private array $signals = [];

    public function __construct()
    {
        $this->register('DUPLICATE_CERTIFICATE_CLAIM', 'Prevent impersonation or shared forged credentials across distinct accounts.', 'fraud_integrity');
        $this->register('CONFLICTING_TRUSTEE_CLAIM', 'Prevent unauthorized institutional takeover during disputed trustee governance.', 'governance');
        $this->register('DUPLICATE_INSTITUTION_REGISTRATION', 'Prevent double-listing of the same religious trust or endowment.', 'fraud_integrity');
        $this->register('ISSUER_DENIED_CREDENTIAL', 'Escalate explicitly disavowed educational/traditional credential for human review.', 'qualification');
        $this->register('GSTIN_SUSPENDED_HOLD', 'Verify current tax standing with partner before enabling commercial settlement.', 'fiscal');
        $this->register('SANCTIONS_POSSIBLE_MATCH', 'Mandatory human false-positive adjudication when G-LEGAL-0 is active.', 'regulatory');
    }

    public function register(string $code, string $lawfulJustification, string $category = 'compliance'): void
    {
        $normCode = strtoupper(trim($code));
        $haystack = strtolower($normCode . ' ' . $category . ' ' . $lawfulJustification);

        foreach (self::PROHIBITED_ATTRIBUTES as $banned) {
            if (preg_match('/\b' . preg_quote($banned, '/') . '\b/', $haystack) === 1) {
                throw new InvalidArgumentException("Prohibited discriminatory risk signal attribute [{$banned}] violates §20 R-09.");
            }
        }

        if (trim($lawfulJustification) === '' || mb_strlen(trim($lawfulJustification)) < 12) {
            throw new InvalidArgumentException("Risk signal [{$normCode}] must declare a concrete lawful_justification.");
        }

        $this->signals[$normCode] = [
            'code' => $normCode,
            'lawful_justification' => trim($lawfulJustification),
            'category' => $category,
        ];
    }

    public function isRegistered(string $code): bool
    {
        return isset($this->signals[strtoupper(trim($code))]);
    }

    /**
     * @return array<string, array{code: string, lawful_justification: string, category: string}>
     */
    public function all(): array
    {
        return $this->signals;
    }
}

