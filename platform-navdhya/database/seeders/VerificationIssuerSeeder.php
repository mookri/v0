<?php

namespace Database\Seeders;

use App\Domain\Verification\Models\VerificationIssuer;
use Illuminate\Database\Seeder;

final class VerificationIssuerSeeder extends Seeder
{
    public function run(): void
    {
        $issuers = [
            [
                'code' => 'sampurnanand_sanskrit_univ',
                'name' => 'Sampurnanand Sanskrit Vishwavidyalaya',
                'name_devanagari' => 'सम्पूर्णानन्द संस्कृत विश्वविद्यालय',
                'kind' => 'university',
                'jurisdiction_state' => 'UP',
                'established_year' => 1791,
                'registry_reference' => 'UGC-UP-SSVV-1958',
                'contact_channel' => 'registrar@ssvv.ac.in',
                'evidence_grade' => 'authoritative',
                'equivalence_notes' => 'Shastri equates to Bachelor of Arts (Sanskrit); Acharya equates to Master degree.',
                'response_rate' => 92.5,
            ],
            [
                'code' => 'nsu_tirupati',
                'name' => 'National Sanskrit University, Tirupati',
                'name_devanagari' => 'राष्ट्रीय संस्कृत विश्वविद्यालय, तिरुपति',
                'kind' => 'university',
                'jurisdiction_state' => 'AP',
                'established_year' => 1961,
                'registry_reference' => 'UGC-CENTRAL-NSKTU',
                'contact_channel' => 'verification@nsktu.ac.in',
                'evidence_grade' => 'authoritative',
                'equivalence_notes' => 'Central University under Act 3 of 2020.',
                'response_rate' => 89.0,
            ],
            [
                'code' => 'karnataka_samskrit_univ',
                'name' => 'Karnataka Samskrit University',
                'name_devanagari' => 'कर्नाटक संस्कृत विश्वविद्यालय',
                'kind' => 'university',
                'jurisdiction_state' => 'KA',
                'established_year' => 2010,
                'registry_reference' => 'UGC-KA-KSU-2010',
                'contact_channel' => 'eval@ksu.ac.in',
                'evidence_grade' => 'responding',
                'equivalence_notes' => 'Vidwat Madhyama and Vidwat Uttama recognized under state Sanskrit board.',
                'response_rate' => 76.0,
            ],
            [
                'code' => 'msrvvp_ujjain',
                'name' => 'Maharshi Sandipani Rashtriya Vedavidya Pratishthan',
                'name_devanagari' => 'महर्षि सांदीपनि राष्ट्रीय वेदविद्या प्रतिष्ठान',
                'kind' => 'board',
                'jurisdiction_state' => 'MP',
                'established_year' => 1987,
                'registry_reference' => 'MOE-MSRVVP-1987',
                'contact_channel' => 'academic@msrvvp.ac.in',
                'evidence_grade' => 'authoritative',
                'equivalence_notes' => 'Vedabhushan and Vedavibhushan certificates map to Vedic recitation tracks.',
                'response_rate' => 88.0,
            ],
            [
                'code' => 'tn_agamic_board',
                'name' => 'Agamic Patashala State Certification Board',
                'name_devanagari' => null,
                'kind' => 'board',
                'jurisdiction_state' => 'TN',
                'established_year' => 1995,
                'registry_reference' => 'HRCE-TN-AGAMA',
                'contact_channel' => 'records@agamicboard.tn.gov.in',
                'evidence_grade' => 'registered_entity',
                'equivalence_notes' => 'Requires manual PTR/order cross-check.',
                'response_rate' => 35.0,
            ],
            [
                'code' => 'kashi_gurukul_pathashala',
                'name' => 'Shri Kashi Veda Pathashala',
                'name_devanagari' => 'श्री काशी वेद पाठशाला',
                'kind' => 'pathashala',
                'jurisdiction_state' => 'UP',
                'established_year' => 1952,
                'registry_reference' => null,
                'contact_channel' => 'acharya.kashi.pathashala@example.invalid',
                'evidence_grade' => 'contact_found',
                'equivalence_notes' => 'Traditional oral sanad; capped at manual review.',
                'response_rate' => 20.0,
            ],
            [
                'code' => 'unconfirmed_parampara_guru',
                'name' => 'Traditional Gurukul / Parampara Lineage (Unconfirmed)',
                'name_devanagari' => 'परम्परागत गुरुकुल',
                'kind' => 'individual_guru',
                'jurisdiction_state' => null,
                'established_year' => null,
                'registry_reference' => null,
                'contact_channel' => null,
                'evidence_grade' => 'unconfirmed',
                'equivalence_notes' => 'Offline/informal lineage; never auto-rejected, routed to manual + competency review.',
                'response_rate' => 0.0,
            ],
        ];

        foreach ($issuers as $row) {
            VerificationIssuer::query()->updateOrCreate(
                ['code' => $row['code']],
                $row,
            );
        }
    }
}

