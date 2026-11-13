<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\CatalogCategory;
use Illuminate\Database\Seeder;

/**
 * Authoritative Catalog Taxonomy Seeder (F1).
 * Seeds the 5 root categories (Rituals, Havans, Sanskars, Astrology, Temple Pujas)
 * and verified canonical children with provenance tracking.
 */
class CatalogTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $taxonomy = [
            [
                'name' => 'Rituals & Pujas',
                'slug' => 'rituals',
                'kind' => CatalogCategory::KIND_RITUALS,
                'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC,
                'children' => [
                    ['name' => 'Satyanarayan Puja', 'slug' => 'satyanarayan-puja', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Griha Pravesh', 'slug' => 'griha-pravesh', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Lakshmi Puja', 'slug' => 'lakshmi-puja', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Rudrabhishek', 'slug' => 'rudrabhishek', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                ],
            ],
            [
                'name' => 'Havans & Sacred Fires',
                'slug' => 'havans',
                'kind' => CatalogCategory::KIND_HAVANS,
                'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC,
                'children' => [
                    ['name' => 'Ganapati Homa', 'slug' => 'ganapati-homa', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Navagraha Homa', 'slug' => 'navagraha-homa', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Sudarshana Homa', 'slug' => 'sudarshana-homa', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Chandi Homa', 'slug' => 'chandi-homa', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Maha Mrityunjaya Homa', 'slug' => 'maha-mrityunjaya-homa', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                ],
            ],
            [
                'name' => 'Vedic Sanskars & Samskaras',
                'slug' => 'sanskars',
                'kind' => CatalogCategory::KIND_SANSKARS,
                'provenance' => CatalogCategory::PROVENANCE_INFERRED,
                'children' => [
                    ['name' => 'Namakarana Sanskar', 'slug' => 'namakarana', 'provenance' => CatalogCategory::PROVENANCE_INFERRED],
                    ['name' => 'Upanayana (Janeu)', 'slug' => 'upanayana', 'provenance' => CatalogCategory::PROVENANCE_INFERRED],
                    ['name' => 'Vivaha Sanskar', 'slug' => 'vivaha-sanskar', 'provenance' => CatalogCategory::PROVENANCE_INFERRED],
                    ['name' => 'Antyesti & Shraddha', 'slug' => 'antyesti-shraddha', 'provenance' => CatalogCategory::PROVENANCE_INFERRED],
                ],
            ],
            [
                'name' => 'Jyotish & Remedial Shanti',
                'slug' => 'astrology',
                'kind' => CatalogCategory::KIND_ASTROLOGY,
                'provenance' => CatalogCategory::PROVENANCE_INFERRED,
                'children' => [
                    ['name' => 'Kala Sarpa Dosha Shanti', 'slug' => 'kala-sarpa-shanti', 'provenance' => CatalogCategory::PROVENANCE_INFERRED],
                    ['name' => 'Manglik Dosha Nivaran', 'slug' => 'manglik-dosha-nivaran', 'provenance' => CatalogCategory::PROVENANCE_INFERRED],
                    ['name' => 'Sade Sati Shanti', 'slug' => 'sade-sati-shanti', 'provenance' => CatalogCategory::PROVENANCE_INFERRED],
                ],
            ],
            [
                'name' => 'Temple Sevas & Darshan',
                'slug' => 'temple-pujas',
                'kind' => CatalogCategory::KIND_TEMPLE_PUJAS,
                'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC,
                'children' => [
                    ['name' => 'Darshan Pass', 'slug' => 'darshan-pass', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Special Archana', 'slug' => 'special-archana', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                    ['name' => 'Abhisheka Seva', 'slug' => 'abhisheka-seva', 'provenance' => CatalogCategory::PROVENANCE_AUTHENTIC],
                ],
            ],
        ];

        foreach ($taxonomy as $sort => $rootData) {
            $root = CatalogCategory::query()->withoutGlobalScopes()->updateOrCreate(
                ['tenancy_key' => '__PLATFORM_GLOBAL__', 'slug' => $rootData['slug']],
                [
                    'tenant_id' => null,
                    'name' => $rootData['name'],
                    'kind' => $rootData['kind'],
                    'provenance' => $rootData['provenance'],
                    'sort' => ($sort + 1) * 10,
                    'status' => CatalogCategory::STATUS_ACTIVE,
                ]
            );

            foreach ($rootData['children'] as $childSort => $childData) {
                CatalogCategory::query()->withoutGlobalScopes()->updateOrCreate(
                    ['tenancy_key' => '__PLATFORM_GLOBAL__', 'slug' => $childData['slug']],
                    [
                        'tenant_id' => null,
                        'parent_id' => $root->id,
                        'name' => $childData['name'],
                        'kind' => $rootData['kind'],
                        'provenance' => $childData['provenance'],
                        'sort' => ($childSort + 1) * 10,
                        'status' => CatalogCategory::STATUS_ACTIVE,
                    ]
                );
            }
        }
    }
}

