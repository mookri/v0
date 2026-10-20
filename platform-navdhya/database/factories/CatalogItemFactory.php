<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItem>
 */
final class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.$this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'name' => $this->faker->words(3, true),
            'status' => CatalogItem::STATUS_ACTIVE,
            'currency' => 'INR',
            'price_minor' => $this->faker->numberBetween(100, 100000),
        ];
    }
}
