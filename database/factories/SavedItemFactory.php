<?php

namespace Database\Factories;

use App\Modules\Customers\Models\SavedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedItem>
 */
final class SavedItemFactory extends Factory
{
    protected $model = SavedItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'ref_type' => 'puja',
            'ref_id' => (string) \Illuminate\Support\Str::ulid(),
        ];
    }
}
