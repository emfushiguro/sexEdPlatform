<?php

return [
    'verification_statuses' => [
        'not_required' => 'Legacy Declaration',
        'pending' => 'Verification Required',
        'under_review' => 'Pending Review',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'resubmission_required' => 'Resubmission Required',
        'revoked' => 'Revoked',
        'reserved' => 'Reserved for Verification',
        'claim_closed' => 'Relationship claim withdrawn or administratively closed',
    ],

    'document_types' => [
        'civil_registry_record' => 'Civil Registry or Parentage Record',
        'adoption_order' => 'Adoption-Related Order or Record',
        'court_order' => 'Court Order',
        'guardianship_document' => 'Guardianship Documentation',
        'agency_document' => 'Government or Agency Documentation',
        'official_appointment' => 'Official Appointment Documentation',
        'care_arrangement' => 'Custody or Caregiving Arrangement Evidence',
        'other_official_document' => 'Other Official Supporting Evidence',
        'other_supporting_document' => 'Other Contextual Supporting Evidence',
    ],

    'pathways' => [
        'biological_parent' => [
            'label' => 'Biological Parent Evidence Review',
            'document_types' => ['civil_registry_record', 'court_order', 'agency_document', 'other_official_document', 'other_supporting_document'],
            'required_any_of' => ['civil_registry_record', 'court_order', 'agency_document'],
            'requires_circumstances' => false,
        ],
        'adoptive_parent' => [
            'label' => 'Adoptive Parent Evidence Review',
            'document_types' => ['adoption_order', 'court_order', 'agency_document', 'other_official_document', 'other_supporting_document'],
            'required_any_of' => ['adoption_order', 'court_order', 'agency_document'],
            'requires_circumstances' => false,
        ],
        'non_parental_care' => [
            'label' => 'Non-Parental Care or Custody Review',
            'document_types' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document', 'other_supporting_document'],
            'required_any_of' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document'],
            'requires_circumstances' => true,
        ],
        'guardianship' => [
            'label' => 'Guardianship or Authority Review',
            'document_types' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'other_official_document', 'other_supporting_document'],
            'required_any_of' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment'],
            'requires_circumstances' => false,
        ],
        'court_appointed_guardianship' => [
            'label' => 'Court-Appointed Guardianship Review',
            'document_types' => ['court_order', 'official_appointment', 'other_official_document', 'other_supporting_document'],
            'required_any_of' => ['court_order', 'official_appointment'],
            'requires_circumstances' => false,
        ],
        'custom_care' => [
            'label' => 'Custom Care Relationship Review',
            'document_types' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document', 'other_supporting_document'],
            'required_any_of' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document'],
            'requires_circumstances' => true,
        ],
    ],

    'types' => [
        'biological_mother' => ['pathway' => 'biological_parent'],
        'biological_father' => ['pathway' => 'biological_parent'],
        'adoptive_parent' => ['pathway' => 'adoptive_parent'],
        'foster_parent' => ['pathway' => 'non_parental_care'],
        'grandmother' => ['pathway' => 'non_parental_care'],
        'grandfather' => ['pathway' => 'non_parental_care'],
        'aunt' => ['pathway' => 'non_parental_care'],
        'uncle' => ['pathway' => 'non_parental_care'],
        'older_sister' => ['pathway' => 'non_parental_care'],
        'older_brother' => ['pathway' => 'non_parental_care'],
        'legal_guardian' => ['pathway' => 'guardianship'],
        'court_appointed_guardian' => ['pathway' => 'court_appointed_guardianship'],
        'relative' => ['pathway' => 'non_parental_care'],
        'family_friend' => ['pathway' => 'non_parental_care'],
        'caregiver' => ['pathway' => 'non_parental_care'],
        'other' => ['pathway' => 'custom_care'],
    ],

    'rejection_reasons' => [
        'unclear_document' => 'Document is unclear',
        'incomplete_document' => 'Document is incomplete',
        'incorrect_document_type' => 'Incorrect document type',
        'cannot_verify' => 'Document cannot be verified',
        'expired_document' => 'Document appears expired',
        'insufficient_support' => 'Information does not sufficiently support the relationship',
        'missing_required_information' => 'Missing required information',
        'other' => 'Other',
    ],
];
