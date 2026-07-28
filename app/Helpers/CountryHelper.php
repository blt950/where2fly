<?php

namespace App\Helpers;

use Symfony\Component\Intl\Countries;

class CountryHelper
{
    private const ADDITIONS = ['XK' => 'Kosovo'];
    private const OVERRIDES = ['TR' => 'Türkiye (Turkey)'];

    public const US_STATES = ['AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'];

    /**
     * All country names keyed by ISO 3166-1 alpha-2 code, sorted by name
     */
    public static function names(): array
    {
        $names = Countries::getNames('en') + self::ADDITIONS;
        asort($names);

        // Manual overrides
        foreach(self::OVERRIDES as $country => $override){
            $names[$country] = $override;
        }

        return $names;
    }

    public static function name(?string $code): string
    {
        if (isset(self::ADDITIONS[$code])) {
            return self::ADDITIONS[$code];
        }

        return $code !== null && Countries::exists($code) ? Countries::getName($code, 'en') : 'Unknown Country';
    }
}
