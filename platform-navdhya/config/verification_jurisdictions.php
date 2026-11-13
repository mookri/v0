<?php

return [
    'MH_CHARITY_COMMISSIONER' => [
        'state_code' => 'MH',
        'title' => 'Maharashtra Charity Commissioner ("Know Your Trust" Manual Verification)',
        'mode' => 'manual',
        'automated_scraping_allowed' => false,
        'checklist' => [
            'trust_registration_number',
            'ptr_entry_screenshot_document_id',
            'verified_timestamp',
            'reviewer_id',
        ],
    ],
    'TN_HRCE' => [
        'state_code' => 'TN',
        'title' => 'Tamil Nadu Hindu Religious & Charitable Endowments Department',
        'mode' => 'manual',
        'automated_scraping_allowed' => false,
        'checklist' => [
            'hrce_temple_code',
            'executive_officer_appointment_order',
            'trustee_board_resolution',
        ],
    ],
    'KA_MUZRAI' => [
        'state_code' => 'KA',
        'title' => 'Karnataka Hindu Religious Institutions & Charitable Endowments (Muzrai)',
        'mode' => 'manual',
        'automated_scraping_allowed' => false,
        'checklist' => [
            'muzrai_category_grade',
            'management_committee_order',
        ],
    ],
    'AP_ENDOWMENT' => [
        'state_code' => 'AP',
        'title' => 'Andhra Pradesh Endowments Department',
        'mode' => 'manual',
        'automated_scraping_allowed' => false,
        'checklist' => [
            'section_6_classification_ref',
            'trust_board_proceedings',
        ],
    ],
    'KL_DEVASWOM' => [
        'state_code' => 'KL',
        'title' => 'Kerala Statutory Devaswom Board Order Verification',
        'mode' => 'manual',
        'automated_scraping_allowed' => false,
        'checklist' => [
            'devaswom_board_order_ref',
            'devaswom_secretary_attestation',
        ],
    ],
    'TRUST_BOMBAY_1950' => [
        'state_code' => 'GJ',
        'title' => 'Bombay Public Trusts Act 1950 Registry',
        'mode' => 'manual',
        'automated_scraping_allowed' => false,
        'checklist' => [
            'ptr_registration_number',
            'schedule_i_extract',
        ],
    ],
    'GENERAL_SOCIETY' => [
        'state_code' => 'ALL',
        'title' => 'Societies Registration Act 1860 / Unregistered Community Checklist',
        'mode' => 'manual',
        'automated_scraping_allowed' => false,
        'checklist' => [
            'society_certificate_or_community_letter',
            'electricity_or_land_title_proof',
            'multi_witness_corroboration',
        ],
    ],
];

