<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tax Rate
    |--------------------------------------------------------------------------
    |
    | Global default tax rate applied to invoices (0.0 – 1.0).
    | A per-team billing_tax_rate field overrides this when set.
    | Example: 0.18 = 18 % GST, 0.20 = 20 % VAT.
    |
    */

    'tax_rate' => (float) env('BILLING_TAX_RATE', 0.0),

];
