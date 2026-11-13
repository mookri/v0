<?php

namespace App\Domain\Calendar\Festivals;

/**
 * Registry of 12 Pilot Vedic Festival Rules with scriptural provenance and exact observance anchors.
 * Fulfills Gate G-FEST-1.
 */
final class FestivalRuleRegistry
{
    public const PILOT_FESTIVALS = [
        'DIWALI_LAKSHMI_PUJA' => [
            'name' => 'Diwali (Lakshmi Puja)',
            'tithi_target' => 30, // Amavasya
            'masa_amanta' => 'ASHVINA',
            'masa_purnimanta' => 'KARTIKA',
            'paksha' => 'KRISHNA',
            'anchor' => 'PRADOSHA',
            'scripture' => 'Dharma Sindhu / Nirnaya Sindhu',
            'rule_summary' => 'Lakshmi Puja is performed on Amavasya prevailing during Pradosha Kaal.',
        ],
        'MAHASHIVRATRI' => [
            'name' => 'Maha Shivratri',
            'tithi_target' => 29, // Krishna Chaturdashi
            'masa_amanta' => 'MAGHA',
            'masa_purnimanta' => 'PHALGUNA',
            'paksha' => 'KRISHNA',
            'anchor' => 'NISHITA',
            'scripture' => 'Nirnaya Sindhu',
            'rule_summary' => 'Observed on Krishna Chaturdashi prevailing during Nishita Kaal.',
        ],
        'KRISHNA_JANMASHTAMI' => [
            'name' => 'Krishna Janmashtami',
            'tithi_target' => 23, // Krishna Ashtami
            'masa_amanta' => 'SHRAVANA',
            'masa_purnimanta' => 'BHADRAPADA',
            'paksha' => 'KRISHNA',
            'anchor' => 'NISHITA',
            'preferred_nakshatra' => 4, // Rohini
            'scripture' => 'Hari Bhakti Vilasa / Nirnaya Sindhu',
            'rule_summary' => 'Celebrated during Nishita Kaal when Krishna Ashtami prevails, ideally overlapping Rohini Nakshatra.',
        ],
        'HOLIKA_DAHAN' => [
            'name' => 'Holika Dahan',
            'tithi_target' => 15, // Purnima
            'masa_amanta' => 'PHALGUNA',
            'masa_purnimanta' => 'PHALGUNA',
            'paksha' => 'SHUKLA',
            'anchor' => 'PRADOSHA',
            'exclude_bhadra' => true,
            'scripture' => 'Dharma Sindhu',
            'rule_summary' => 'Lit during Pradosha Kaal on Phalguna Purnima provided Bhadra (Vishti Karana) has ended.',
        ],
        'RANGWALI_HOLI' => [
            'name' => 'Rangwali Holi (Dhulandi)',
            'tithi_target' => 16, // Krishna Pratipada
            'masa_amanta' => 'PHALGUNA',
            'masa_purnimanta' => 'CHAITRA',
            'paksha' => 'KRISHNA',
            'anchor' => 'SUNRISE',
            'scripture' => 'Bhavishya Purana',
            'rule_summary' => 'Celebrated on the civil day following Holika Dahan on Chaitra Krishna Pratipada at sunrise.',
        ],
        'RAM_NAVAMI' => [
            'name' => 'Ram Navami',
            'tithi_target' => 9, // Shukla Navami
            'masa_amanta' => 'CHAITRA',
            'masa_purnimanta' => 'CHAITRA',
            'paksha' => 'SHUKLA',
            'anchor' => 'MADHYAHNA',
            'scripture' => 'Agastya Samhita',
            'rule_summary' => 'Celebrated during Madhyahna Kaal on Chaitra Shukla Navami, the birth hour of Lord Rama.',
        ],
        'GANESH_CHATURTHI' => [
            'name' => 'Ganesh Chaturthi',
            'tithi_target' => 4, // Shukla Chaturthi
            'masa_amanta' => 'BHADRAPADA',
            'masa_purnimanta' => 'BHADRAPADA',
            'paksha' => 'SHUKLA',
            'anchor' => 'MADHYAHNA',
            'scripture' => 'Ganesha Purana',
            'rule_summary' => 'Observed during Madhyahna Kaal on Bhadrapada Shukla Chaturthi.',
        ],
        'KARWA_CHAUTH' => [
            'name' => 'Karwa Chauth',
            'tithi_target' => 19, // Krishna Chaturthi
            'masa_amanta' => 'ASHVINA',
            'masa_purnimanta' => 'KARTIKA',
            'paksha' => 'KRISHNA',
            'anchor' => 'MOONRISE',
            'scripture' => 'Vrata Raj',
            'rule_summary' => 'Vrata is observed on Krishna Chaturthi, concluded with Arghya upon Moonrise.',
        ],
        'MAKAR_SANKRANTI' => [
            'name' => 'Makar Sankranti',
            'event_type' => 'SANKRANTI',
            'target_rashi' => 10, // Makara
            'anchor' => 'DAYLIGHT_INGRESS',
            'scripture' => 'Surya Siddhanta',
            'rule_summary' => 'Observed when the Sun ingresses into sidereal Capricorn (Makara), celebrating the Punyakala.',
        ],
        'GANGA_DUSSEHRA' => [
            'name' => 'Ganga Dussehra',
            'tithi_target' => 10, // Shukla Dashami
            'masa_amanta' => 'JYESHTHA',
            'masa_purnimanta' => 'JYESHTHA',
            'paksha' => 'SHUKLA',
            'anchor' => 'SUNRISE',
            'preferred_nakshatra' => 18, // Hasta or Jyeshtha
            'scripture' => 'Skanda Purana',
            'rule_summary' => 'Observed on Jyeshtha Shukla Dashami at sunrise commemorating the descent of the Ganga.',
        ],
        'EKADASHI_DEVUTHANI' => [
            'name' => 'Devutthana Ekadashi',
            'tithi_target' => 11, // Shukla Ekadashi
            'masa_amanta' => 'KARTIKA',
            'masa_purnimanta' => 'KARTIKA',
            'paksha' => 'SHUKLA',
            'anchor' => 'SUNRISE_PURIFIED',
            'avoid_dashami_vedha' => true,
            'scripture' => 'Padma Purana',
            'rule_summary' => 'Observed on Kartika Shukla Ekadashi free from Dashami contamination (Arunodaya Vedha).',
        ],
        'AYUDHA_PUJA' => [
            'name' => 'Ayudha Puja',
            'tithi_target' => 9, // Shukla Navami
            'masa_amanta' => 'ASHVINA',
            'masa_purnimanta' => 'ASHVINA',
            'paksha' => 'SHUKLA',
            'anchor' => 'MADHYAHNA',
            'scripture' => 'Devi Bhagavata',
            'rule_summary' => 'Worship of implements and weapons conducted during Navami afternoon during Navratri.',
        ],
    ];

    public static function getRule(string $key): ?array
    {
        return self::PILOT_FESTIVALS[$key] ?? null;
    }
}

