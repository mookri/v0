<?php

declare(strict_types=1);

namespace App\Domain\Trust\Workflow;

use BackedEnum;

/**
 * Unified state-machine guard for Feedback, Support, and Incident workflows.
 *
 * Enforces legal transitions, terminal-state immutability, and column width
 * assertions (standing rule against TS-03 / Communications C-09 truncation).
 */
final class TrustStateMachine
{
    /**
     * Assert transition from $from to $to is permitted in $transitionsMap.
     *
     * @param  array<string, list<string>>  $transitionsMap
     */
    public static function assertCanTransition(string $from, string $to, array $transitionsMap, string $entityLabel = 'record'): void
    {
        $fromUpper = strtoupper(trim($from));
        $toUpper = strtoupper(trim($to));

        $allowed = $transitionsMap[$fromUpper] ?? [];
        if (! in_array($toUpper, $allowed, true)) {
            throw new \DomainException(
                sprintf("Illegal %s transition from '%s' to '%s'.", $entityLabel, $fromUpper, $toUpper)
            );
        }
    }

    /**
     * Verify every declared string-backed enum value fits comfortably inside the DB column width.
     * Standing regression guard against varchar(16) truncation traps (TS-03 / C-09).
     *
     * @param  class-string<BackedEnum>  $enumClass
     */
    public static function assertColumnWidthFits(string $enumClass, int $columnLength, int $safetyMargin = 8): void
    {
        $maxLen = 0;
        $longest = '';
        foreach ($enumClass::cases() as $case) {
            $val = (string) $case->value;
            if (strlen($val) > $maxLen) {
                $maxLen = strlen($val);
                $longest = $val;
            }
        }

        if ($columnLength < ($maxLen + $safetyMargin)) {
            throw new \LogicException(
                sprintf(
                    "Column width %d for %s is too narrow for longest state '%s' (%d chars + %d margin).",
                    $columnLength,
                    $enumClass,
                    $longest,
                    $maxLen,
                    $safetyMargin
                )
            );
        }
    }
}

