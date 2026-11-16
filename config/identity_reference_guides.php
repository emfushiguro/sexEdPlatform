<?php

return [
    'dependent_birth_record' => ['source' => 'https://psa.gov.ph/birth-certificate', 'rules_url' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/2/100958', 'note' => 'Check the dependent name, birthdate, and named parent against registration and guardian identity. PSA, former NSO, and local civil registry formats may differ. Covered civil registry certificates have permanent validity; an older issue date alone is not a reason to reject. Confirm legibility, authenticity, and any applicable amendments with the issuing authority.', 'checked' => '2026-11-15'],
    'philhealth' => ['source' => 'https://www.philhealth.gov.ph/advisories/2016/adv2016-0012.pdf', 'note' => 'Official PVC card example published in 2016; other formats may exist. Expiry and current format are not confirmed.', 'checked' => '2026-11-14'],
    'nbi_clearance' => ['source' => 'https://clearance.nbi.gov.ph/', 'verification_url' => 'https://clearance.nbi.gov.ph/', 'note' => 'The NBI portal provides clearance authentication and validity verification.', 'checked' => '2026-11-15'],
    'tin_card' => ['source' => 'https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%20120-2023.pdf', 'verification_url' => 'https://orus.bir.gov.ph/', 'note' => 'For ORUS-issued digital TIN IDs, follow BIR QR verification guidance. Other formats may require different checks.', 'checked' => '2026-11-15'],
    'national_id' => ['source' => 'https://psa.gov.ph/content/digital-national-id-enables-more-filipinos-access-services-government-financial', 'note' => 'PSA describes the digital National ID and authentication; formats can differ.', 'checked' => '2026-11-14'],
    'drivers_license' => ['source' => 'https://www.lto.gov.ph/wp-content/uploads/2023/09/IRR-RA10930.pdf', 'note' => 'LTO describes licence validity and renewal; check the actual document and applicable current guidance.', 'checked' => '2026-11-14'],
    'prc_id' => ['source' => 'https://www.prc.gov.ph/node/6509', 'note' => 'PRC describes its electronic professional ID and authentication.', 'checked' => '2026-11-14'],
];
