<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Commission Fallback
    |--------------------------------------------------------------------------
    |
    | Used only when there is no active commission policy row yet.
    | Admin-defined policies still take precedence for all future purchases.
    |
    */
    'default_commission_percent' => (float) env('MONETIZATION_DEFAULT_COMMISSION_PERCENT', 10.00),
    'default_tax_basis' => env('MONETIZATION_DEFAULT_TAX_BASIS', 'gross'),
    'default_refund_policy' => env('MONETIZATION_DEFAULT_REFUND_POLICY', 'disabled'),
];
