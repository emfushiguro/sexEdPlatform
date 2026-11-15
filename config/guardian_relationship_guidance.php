<?php

return [
    'biological_parent' => [
        'note' => 'A civil registry birth record naming the parent can support parentage. Include relevant custody or court records if names, parentage or authority are disputed. Parentage and present authority must be reviewed separately.',
        'sources' => ['Family Code, Articles 172, 175, 176 and 213' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/5/95173'],
    ],
    'adoptive_parent' => [
        'note' => 'An adoption order and relevant amended civil registry record can support the claimed adoptive relationship. Check that the order is effective and identifies this dependent; an application or pending placement alone is not a completed adoption.',
        'sources' => ['RA 11642, adoption orders and parental authority' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/2/94036'],
    ],
    'foster_parent' => [
        'note' => 'A Foster Family Care License identifies an approved foster carer; a Foster Placement Authority identifies the placement of a particular child. Supply relevant placement or agency records and explain any applicable alternative arrangement for reviewer assessment.',
        'sources' => ['NACC foster care process and placement documents' => 'https://www.nacc.gov.ph/accd-foster-parent/'],
    ],
    'non_parental_care' => [
        'note' => 'For grandparents, siblings, relatives or other carers, explain the care arrangement and provide relevant custody, court or agency evidence. Family Code Articles 214 and 216 set conditions and an order for substitute authority; a family connection alone does not establish it.',
        'sources' => ['Family Code, Articles 214 and 216' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/5/95173'],
    ],
    'guardianship' => [
        'note' => 'Provide the appointment or other relevant authority record. Check whether it covers the person, property or both and whether it remains effective; authority over property alone does not establish authority for personal registration decisions.',
        'sources' => ['Supreme Court: guardianship and its scope' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/1/69577'],
    ],
    'court_appointed_guardianship' => [
        'note' => 'A court appointment and corresponding letters of guardianship can support this claim. Review the named guardian and dependent, the scope of appointment and any conditions; a pending petition alone does not establish appointment.',
        'sources' => ['Supreme Court: appointment and letters of guardianship' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/1/69577'],
    ],
    'custom_care' => [
        'note' => 'Describe the specific arrangement and provide relevant official evidence of care or authority. A personal description, informal agreement or family connection alone does not establish parental authority. Unclear or disputed authority needs further review.',
        'sources' => ['Family Code, Articles 210, 214 and 216' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/5/95173'],
    ],
];
