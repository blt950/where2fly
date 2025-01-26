<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Website Id
    |--------------------------------------------------------------------------
    |
    | For Umami website tracking, this provides the correct website id for the current environment.
    |
    */

    'website_id_prod' => env('UMAMI_WEBSITE_ID_PROD', null),
    'website_id_dev' => env('UMAMI_WEBSITE_ID_DEV', null),

];
