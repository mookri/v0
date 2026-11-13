<?php

namespace App\Modules\Jyotish\Audit;

use App\Domain\Jyotish\Audit\RulesetAuditWriterInterface;
use App\Platform\Audit\AuditWriter;
use Illuminate\Support\Facades\DB;

/**
 * Audit writer for Jyotish ruleset mutations and generation lifecycle transitions (T-JY00-13).
 * Persists audit rows into jyotish_ruleset_audit and dispatches platform audit records.
 */
final readonly class RulesetAuditWriter implements RulesetAuditWriterInterface
{
    public function __construct(
        private AuditWriter $audit,
    ) {
    }

    public function recordRulesetCreated(string $rulesetCode, array $rules, ?string $actorId = null): void
    {
        DB::connection('central')->table('jyotish_ruleset_audit')->insert([
            'ruleset_code' => $rulesetCode,
            'action' => 'jyotish.ruleset.created',
            'previous_state_json' => null,
            'new_state_json' => json_encode($rules),
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);

        $this->audit->record(
            action: 'jyotish.ruleset.created',
            resource: ['module' => 'jyotish', 'resource_type' => 'ruleset', 'resource_id' => $rulesetCode],
            after: ['ruleset_code' => $rulesetCode],
        );
    }

    public function recordRulesetUpdated(string $rulesetCode, array $before, array $after, ?string $actorId = null): void
    {
        DB::connection('central')->table('jyotish_ruleset_audit')->insert([
            'ruleset_code' => $rulesetCode,
            'action' => 'jyotish.ruleset.updated',
            'previous_state_json' => json_encode($before),
            'new_state_json' => json_encode($after),
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);

        $this->audit->record(
            action: 'jyotish.ruleset.updated',
            resource: ['module' => 'jyotish', 'resource_type' => 'ruleset', 'resource_id' => $rulesetCode],
            before: ['rules' => $before],
            after: ['rules' => $after],
        );
    }

    public function recordRulesetPromoted(string $rulesetCode, string $version, ?string $actorId = null): void
    {
        DB::connection('central')->table('jyotish_ruleset_audit')->insert([
            'ruleset_code' => $rulesetCode,
            'action' => 'jyotish.ruleset.promoted',
            'previous_state_json' => null,
            'new_state_json' => json_encode(['version' => $version]),
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);

        $this->audit->record(
            action: 'jyotish.ruleset.promoted',
            resource: ['module' => 'jyotish', 'resource_type' => 'ruleset', 'resource_id' => $rulesetCode],
            after: ['version' => $version],
        );
    }

    public function recordGenerationPromoted(int $generationNumber, string $rulesVersion, ?string $actorId = null): void
    {
        DB::connection('central')->table('jyotish_ruleset_audit')->insert([
            'ruleset_code' => "gen_{$generationNumber}",
            'action' => 'jyotish.generation.promoted',
            'previous_state_json' => null,
            'new_state_json' => json_encode(['generation' => $generationNumber, 'rules_version' => $rulesVersion]),
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);

        $this->audit->record(
            action: 'jyotish.generation.promoted',
            resource: ['module' => 'jyotish', 'resource_type' => 'generation', 'resource_id' => (string) $generationNumber],
            after: ['generation' => $generationNumber, 'rules_version' => $rulesVersion],
        );
    }

    public function recordGenerationRetired(int $generationNumber, string $rulesVersion, ?string $actorId = null): void
    {
        DB::connection('central')->table('jyotish_ruleset_audit')->insert([
            'ruleset_code' => "gen_{$generationNumber}",
            'action' => 'jyotish.generation.retired',
            'previous_state_json' => null,
            'new_state_json' => json_encode(['generation' => $generationNumber, 'rules_version' => $rulesVersion]),
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);

        $this->audit->record(
            action: 'jyotish.generation.retired',
            resource: ['module' => 'jyotish', 'resource_type' => 'generation', 'resource_id' => (string) $generationNumber],
            after: ['generation' => $generationNumber, 'rules_version' => $rulesVersion],
        );
    }
}

