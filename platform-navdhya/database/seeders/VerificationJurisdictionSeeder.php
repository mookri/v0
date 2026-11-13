<?php

namespace Database\Seeders;

use App\Domain\Verification\Models\VerificationJurisdiction;
use Illuminate\Database\Seeder;

final class VerificationJurisdictionSeeder extends Seeder
{
    public function run(): void
    {
        $jurisdictions = config('verification_jurisdictions', []);

        foreach ($jurisdictions as $code => $entry) {
            VerificationJurisdiction::query()->updateOrCreate(
                ['code' => (string) $code],
                [
                    'state_code' => $entry['state_code'],
                    'title' => $entry['title'],
                    'checklist_json' => $entry['checklist'],
                    'manual_mode_only' => true,
                ],
            );
        }
    }
}

