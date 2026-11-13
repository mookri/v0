<?php

namespace App\Modules\Catalog\Contracts;

/**
 * Service to Samagri Kit Bindings Port (F3).
 *
 * @phpstan-type KitBindingDto array{
 *     binding_id: string,
 *     service_sku: string,
 *     kit_item_id: string,
 *     kit_sku: string,
 *     kit_name: string,
 *     obligation: string,
 *     quantity_per_party: int,
 *     sort: int
 * }
 */
interface ResolvesServiceKitBindings
{
    /**
     * @return list<KitBindingDto>
     */
    public function bindingsForService(string $serviceSku): array;
}

