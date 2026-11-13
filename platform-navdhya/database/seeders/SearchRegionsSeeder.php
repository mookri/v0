<?php

namespace Database\Seeders;

use App\Modules\Search\Models\SearchRegion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SearchRegionsSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            [
                'name_en' => 'Varanasi',
                'name_dev' => 'वाराणसी',
                'kind' => 'city',
                'name_alt_json' => ['Banaras', 'Benares', 'Kashi', 'काशी', 'बनारस'],
                'geohash6' => 'tu71wt',
                'lat_milli' => 25317,
                'lng_milli' => 82973,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Haridwar',
                'name_dev' => 'हरिद्वार',
                'kind' => 'city',
                'name_alt_json' => ['Hardwar', 'Mayapuri', 'मायापुरी'],
                'geohash6' => 'ttrh6b',
                'lat_milli' => 29945,
                'lng_milli' => 78164,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Rishikesh',
                'name_dev' => 'ऋषिकेश',
                'kind' => 'city',
                'name_alt_json' => ['Hrishikesh'],
                'geohash6' => 'ttrv8k',
                'lat_milli' => 30086,
                'lng_milli' => 78267,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Ayodhya',
                'name_dev' => 'अयोध्या',
                'kind' => 'city',
                'name_alt_json' => ['Saket', 'साकेत', 'Faizabad'],
                'geohash6' => 'tudm87',
                'lat_milli' => 26792,
                'lng_milli' => 82199,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Ujjain',
                'name_dev' => 'उज्जैन',
                'kind' => 'city',
                'name_alt_json' => ['Avantika', 'अवंतिका', 'Ujjayini'],
                'geohash6' => 'tsjt3z',
                'lat_milli' => 23176,
                'lng_milli' => 75788,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Mathura',
                'name_dev' => 'मथुरा',
                'kind' => 'city',
                'name_alt_json' => ['Braj', 'Muttra', 'ब्रज'],
                'geohash6' => 'ttpn87',
                'lat_milli' => 27492,
                'lng_milli' => 77673,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Vrindavan',
                'name_dev' => 'वृन्दावन',
                'kind' => 'city',
                'name_alt_json' => ['Brindavan', 'Vrindaban'],
                'geohash6' => 'ttpp0h',
                'lat_milli' => 27580,
                'lng_milli' => 77700,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Prayagraj',
                'name_dev' => 'प्रयागराज',
                'kind' => 'city',
                'name_alt_json' => ['Allahabad', 'इलाहाबाद', 'Triveni'],
                'geohash6' => 'tu4v9p',
                'lat_milli' => 25435,
                'lng_milli' => 81846,
                'population_tier' => 'TIER_1_SPIRITUAL',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Bengaluru',
                'name_dev' => 'बेंगलुरु',
                'kind' => 'city',
                'name_alt_json' => ['Bangalore', 'बैंगलोर', 'Bengaluru Urban'],
                'geohash6' => 'tdr1v7',
                'lat_milli' => 12971,
                'lng_milli' => 77594,
                'population_tier' => 'TIER_1_METRO',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Mumbai',
                'name_dev' => 'मुंबई',
                'kind' => 'city',
                'name_alt_json' => ['Bombay', 'बम्बई', 'Mumbai Suburban'],
                'geohash6' => 'te7u9m',
                'lat_milli' => 19076,
                'lng_milli' => 72877,
                'population_tier' => 'TIER_1_METRO',
                'is_serviceable' => true,
            ],
            [
                'name_en' => 'Delhi-NCR',
                'name_dev' => 'दिल्ली-एनसीआर',
                'kind' => 'city',
                'name_alt_json' => ['Delhi', 'New Delhi', 'Noida', 'Gurugram', 'Gurgaon', 'दिल्ली', 'नई दिल्ली'],
                'geohash6' => 'ttnfv7',
                'lat_milli' => 28613,
                'lng_milli' => 77209,
                'population_tier' => 'TIER_1_METRO',
                'is_serviceable' => true,
            ],
        ];

        foreach ($regions as $r) {
            SearchRegion::updateOrCreate(
                ['kind' => $r['kind'], 'name_en' => $r['name_en']],
                [
                    'id' => (string) Str::ulid(),
                    'name_dev' => $r['name_dev'],
                    'name_alt_json' => $r['name_alt_json'],
                    'geohash6' => $r['geohash6'],
                    'lat_milli' => $r['lat_milli'],
                    'lng_milli' => $r['lng_milli'],
                    'population_tier' => $r['population_tier'],
                    'is_serviceable' => $r['is_serviceable'],
                ]
            );
        }
    }
}

