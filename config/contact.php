<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Country calling code
    |--------------------------------------------------------------------------
    |
    | Phones are stored in international form, so this value prefixes every
    | number that arrives in national form. It is deliberately not read from
    | the environment and not exposed as an editable setting: the academy
    | operates in a single country, and letting the code change at runtime
    | would reinterpret new entries without rewriting the ones already stored.
    | Changing it therefore requires a code change, which is the point.
    |
    */

    'country_code' => '55',
];
