<?php

// Matching rules for the MSFS 2024 Content.xml import. The file is undocumented and these are
// reverse-engineered from one real file, so they live here to be changed without touching code.
return [

    'max_upload_kb' => 5120,

    // Exact package name (lowercase) => ICAO, for names with no usable token
    'package_overrides' => [
        'communityfs20-gatwick' => 'EGKK',
        'fs24-microsoft-eddk-cologne-bonn' => 'EDDK',
    ],

    // A package whose name contains any of these is not airport scenery
    'ignore_patterns' => [
        '-models', 'assetpack', 'modellib', '-library', 'landingchallenge', 'challenge', 'discovery',
        'travelbook', 'livery', 'liveries', 'aircraft', 'genericairports', 'fs-base', 'activities', 'training',
    ],

    // Four-letter name tokens that are real airport codes but never mean the airport in a package name
    'token_denylist' => [
        'orbx', 'pmdg', 'wasm', 'base', 'data', 'pack', 'mods', 'tool', 'ship', 'core', 'free', 'lite', 'beta', 'test',
    ],

];
