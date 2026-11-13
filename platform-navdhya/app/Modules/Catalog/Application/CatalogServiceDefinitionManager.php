<?php

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Contracts\DurationResolutionResult;
use App\Modules\Catalog\Contracts\ResolvesServiceDurations;
use App\Modules\Catalog\Contracts\ResolvesServiceKitBindings;
use App\Modules\Catalog\Domain\Duration\DurationRules;
use App\Modules\Catalog\Models\CatalogDurationRule;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogServiceKitBinding;
use InvalidArgumentException;

/**
 * Service Definition Manager (F2 / F3).
 * Evaluates authoritative server duration rules and resolves samagri kit bindings.
 */
final class CatalogServiceDefinitionManager implements ResolvesServiceDurations, ResolvesServiceKitBindings
{
    public function resolveDuration(
        string $sku,
        int $participants = 1,
        ?string $packageTier = null,
        ?string $tradition = null,
        ?\DateTimeInterface $at = null
    ): DurationResolutionResult {
        $item = CatalogItem::query()
            ->where('sku', $sku)
            ->first();

        if ($item === null) {
            return new DurationResolutionResult(
                minutes: 60,
                minBound: 15,
                maxBound: 720,
                ruleSnapshotHash: 'fallback_mock',
                evaluatedRules: [],
                isAuthoritative: false
            );
        }

        $isAuthoritative = $item->base_duration_minutes !== null;
        $baseMinutes = (int) ($item->base_duration_minutes ?? 60);

        $rules = CatalogDurationRule::query()
            ->where('item_id', (string) $item->getKey())
            ->get()
            ->map(fn (CatalogDurationRule $r) => [
                'scope' => $r->scope,
                'scope_key' => $r->scope_key,
                'rule' => $r->rule,
            ])
            ->all();

        if (! empty($rules)) {
            $isAuthoritative = true;
        }

        return DurationRules::resolve(
            baseMinutes: $baseMinutes,
            rules: $rules,
            participants: $participants,
            packageTier: $packageTier,
            tradition: $tradition,
            at: $at,
            isAuthoritative: $isAuthoritative
        );
    }

    /**
     * @return list<array{
     *     binding_id: string,
     *     service_sku: string,
     *     kit_item_id: string,
     *     kit_sku: string,
     *     kit_name: string,
     *     obligation: string,
     *     quantity_per_party: int,
     *     sort: int
     * }>
     */
    public function bindingsForService(string $serviceSku): array
    {
        $serviceItem = CatalogItem::query()
            ->where('sku', $serviceSku)
            ->first();

        if ($serviceItem === null) {
            return [];
        }

        $bindings = CatalogServiceKitBinding::query()
            ->with('kitItem')
            ->where('service_item_id', (string) $serviceItem->getKey())
            ->orderBy('sort')
            ->get();

        $result = [];
        foreach ($bindings as $binding) {
            $kitItem = $binding->kitItem;
            if ($kitItem !== null && $kitItem->status === CatalogItem::STATUS_ACTIVE) {
                $result[] = [
                    'binding_id' => (string) $binding->getKey(),
                    'service_sku' => $serviceItem->sku,
                    'kit_item_id' => (string) $kitItem->getKey(),
                    'kit_sku' => $kitItem->sku,
                    'kit_name' => $kitItem->name,
                    'obligation' => $binding->obligation,
                    'quantity_per_party' => $binding->quantity_per_party,
                    'sort' => $binding->sort,
                ];
            }
        }

        return $result;
    }
}
