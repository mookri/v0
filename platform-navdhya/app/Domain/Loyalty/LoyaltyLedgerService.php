<?php

declare(strict_types=1);

namespace App\Domain\Loyalty;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class LoyaltyLedgerService
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Accrue points to an account with SHA-256 cryptographic hash chaining.
     * Enforces strict ethical guardrail: points never accrue from fear-based Dosha triggers (doc 01 §3, AC-T-JYRW-01).
     *
     * @param string $userId
     * @param string $eventType
     * @param int $pointsDelta
     * @param array<string, mixed> $metadata
     * @return array{account_id: string, new_balance: int, entry_hash: string}
     */
    public function appendEntry(
        string $userId,
        string $eventType,
        int $pointsDelta,
        array $metadata = []
    ): array {
        // Enforce ethical guardrail: No fear-based rewards
        if (str_contains(strtolower($eventType), 'dosha') || str_contains(strtolower($eventType), 'affliction')) {
            throw new \InvalidArgumentException("Points accrual is strictly prohibited for fear-based Dosha facts.");
        }

        return DB::connection('central')->transaction(function () use ($userId, $eventType, $pointsDelta, $metadata): array {
            // Find or create account
            $account = DB::connection('central')->table('loyalty_accounts')->where('user_id', $userId)->first();
            if ($account === null) {
                $accountId = (string) Str::uuid();
                DB::connection('central')->table('loyalty_accounts')->insert([
                    'account_id' => $accountId,
                    'user_id' => $userId,
                    'tier_code' => 'SADHAK',
                    'points_balance' => 0,
                    'lifetime_points_accrued' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $account = DB::connection('central')->table('loyalty_accounts')->where('account_id', $accountId)->first();
            }

            // Get last entry for hash chaining
            $lastEntry = DB::connection('central')->table('loyalty_entries')
                ->where('account_id', $account->account_id)
                ->orderByDesc('id')
                ->first();

            $previousHash = $lastEntry ? $lastEntry->entry_hash : self::GENESIS_HASH;

            $entryId = (string) Str::uuid();
            $metaJson = json_encode($metadata, JSON_THROW_ON_ERROR);

            // Cryptographic SHA-256 block hash: hash(previous_hash + account_id + event_type + points_delta + metadata)
            $entryHash = hash('sha256', $previousHash . $account->account_id . $eventType . $pointsDelta . $metaJson);

            DB::connection('central')->table('loyalty_entries')->insert([
                'entry_id' => $entryId,
                'account_id' => $account->account_id,
                'event_type' => $eventType,
                'points_delta' => $pointsDelta,
                'previous_hash' => $previousHash,
                'entry_hash' => $entryHash,
                'metadata' => $metaJson,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update cached balance
            $newBalance = (int) $account->points_balance + $pointsDelta;
            $newLifetime = (int) $account->lifetime_points_accrued + max(0, $pointsDelta);

            // Tier evaluation (SADHAK < 500, BHAKTA < 2000, SEVAK < 5000, ACHARYA >= 5000)
            $tierCode = match (true) {
                $newLifetime >= 5000 => 'ACHARYA',
                $newLifetime >= 2000 => 'SEVAK',
                $newLifetime >= 500 => 'BHAKTA',
                default => 'SADHAK',
            };

            DB::connection('central')->table('loyalty_accounts')->where('account_id', $account->account_id)->update([
                'points_balance' => $newBalance,
                'lifetime_points_accrued' => $newLifetime,
                'tier_code' => $tierCode,
                'updated_at' => now(),
            ]);

            return [
                'account_id' => $account->account_id,
                'new_balance' => $newBalance,
                'entry_hash' => $entryHash,
            ];
        });
    }

    /**
     * Rebuild and verify cryptographic hash chain integrity of an account.
     *
     * @param string $accountId
     * @return array{is_valid: bool, reconciled_balance: int, total_entries: int}
     */
    public function verifyAndReconcile(string $accountId): array
    {
        $entries = DB::connection('central')->table('loyalty_entries')
            ->where('account_id', $accountId)
            ->orderBy('id')
            ->get();

        $runningBalance = 0;
        $expectedPrevHash = self::GENESIS_HASH;
        $isValidChain = true;

        foreach ($entries as $entry) {
            if ($entry->previous_hash !== $expectedPrevHash) {
                $isValidChain = false;
                break;
            }

            $computedHash = hash('sha256', $entry->previous_hash . $entry->account_id . $entry->event_type . $entry->points_delta . $entry->metadata);
            if ($computedHash !== $entry->entry_hash) {
                $isValidChain = false;
                break;
            }

            $runningBalance += (int) $entry->points_delta;
            $expectedPrevHash = $entry->entry_hash;
        }

        return [
            'is_valid' => $isValidChain,
            'reconciled_balance' => $runningBalance,
            'total_entries' => $entries->count(),
        ];
    }

    /**
     * Pure balance reducer with exact parity to TypeScript rebuildPointsBalance()
     * in packages/commerce/src/domain/loyalty.ts (T-JYRW-02).
     *
     * @param list<array{type: string, points: int}> $entries
     */
    public static function rebuildPointsBalance(array $entries): int
    {
        $balance = 0;
        foreach ($entries as $e) {
            $type = strtoupper((string) ($e['type'] ?? ''));
            $pts = (int) ($e['points'] ?? 0);
            switch ($type) {
                case 'EARN':
                case 'ADJUST':
                    $balance += $pts;
                    break;
                case 'REDEEM':
                case 'EXPIRE':
                case 'REVERSE':
                    $balance -= $pts;
                    break;
            }
        }
        return $balance;
    }
}

