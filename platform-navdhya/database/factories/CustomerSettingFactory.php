<?php

namespace Database\Factories;

use App\Modules\Customers\Models\CustomerSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerSetting>
 */
final class CustomerSettingFactory extends Factory
{
    protected $model = CustomerSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'key' => 'locale',
            'value' => 'hi',
        ];
    }
}
