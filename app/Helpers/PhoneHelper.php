<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * List of country phone codes with flags, ISO codes and country names.
     */
    public static function getCountryCodes(): array
    {
        return [
            '+58' => ['flag' => '🇻🇪', 'iso' => 've', 'name' => 'Venezuela', 'code' => '+58'],
            '+1'  => ['flag' => '🇺🇸', 'iso' => 'us', 'name' => 'EE. UU. / Canadá', 'code' => '+1'],
            '+34' => ['flag' => '🇪🇸', 'iso' => 'es', 'name' => 'España', 'code' => '+34'],
            '+57' => ['flag' => '🇨🇴', 'iso' => 'co', 'name' => 'Colombia', 'code' => '+57'],
            '+52' => ['flag' => '🇲🇽', 'iso' => 'mx', 'name' => 'México', 'code' => '+52'],
            '+54' => ['flag' => '🇦🇷', 'iso' => 'ar', 'name' => 'Argentina', 'code' => '+54'],
            '+56' => ['flag' => '🇨🇱', 'iso' => 'cl', 'name' => 'Chile', 'code' => '+56'],
            '+51' => ['flag' => '🇵🇪', 'iso' => 'pe', 'name' => 'Perú', 'code' => '+51'],
            '+593' => ['flag' => '🇪🇨', 'iso' => 'ec', 'name' => 'Ecuador', 'code' => '+593'],
            '+591' => ['flag' => '🇧🇴', 'iso' => 'bo', 'name' => 'Bolivia', 'code' => '+591'],
            '+598' => ['flag' => '🇺🇾', 'iso' => 'uy', 'name' => 'Uruguay', 'code' => '+598'],
            '+595' => ['flag' => '🇵🇾', 'iso' => 'py', 'name' => 'Paraguay', 'code' => '+595'],
            '+502' => ['flag' => '🇬🇹', 'iso' => 'gt', 'name' => 'Guatemala', 'code' => '+502'],
            '+503' => ['flag' => '🇸🇻', 'iso' => 'sv', 'name' => 'El Salvador', 'code' => '+503'],
            '+504' => ['flag' => '🇭🇳', 'iso' => 'hn', 'name' => 'Honduras', 'code' => '+504'],
            '+505' => ['flag' => '🇳🇮', 'iso' => 'ni', 'name' => 'Nicaragua', 'code' => '+505'],
            '+506' => ['flag' => '🇨🇷', 'iso' => 'cr', 'name' => 'Costa Rica', 'code' => '+506'],
            '+507' => ['flag' => '🇵🇦', 'iso' => 'pa', 'name' => 'Panamá', 'code' => '+507'],
            '+1787' => ['flag' => '🇵🇷', 'iso' => 'pr', 'name' => 'Puerto Rico', 'code' => '+1787'],
            '+1809' => ['flag' => '🇩🇴', 'iso' => 'do', 'name' => 'Rep. Dominicana', 'code' => '+1809'],
            '+53'  => ['flag' => '🇨🇺', 'iso' => 'cu', 'name' => 'Cuba', 'code' => '+53'],
        ];
    }

    /**
     * Dictionary of area codes per country.
     */
    public static function getAreaCodes(): array
    {
        return [
            '+58' => [
                '0414' => '0414',
                '0424' => '0424',
                '0412' => '0412',
                '0416' => '0416',
                '0426' => '0426',
                '414' => '414',
                '424' => '424',
                '412' => '412',
                '416' => '416',
                '426' => '426',
                '0212' => '0212',
                '0241' => '0241',
                '0243' => '0243',
                '0251' => '0251',
                '0261' => '0261',
                '0274' => '0274',
                '0276' => '0276',
                '0281' => '0281',
                '0286' => '0286',
                '0295' => '0295',
            ],
        ];
    }
}
