<?php

namespace App\Helpers;

class AirportCallsignHelper
{
    public static function returnAustralianAirport($string)
    {
        $australianCallsigns = ['AD' => 'YPAD', 'BN' => 'YBBN', 'CS' => 'YBCS', 'CB' => 'YSCB', 'DN' => 'YPDN', 'EN' => 'YMEN', 'CG' => 'YBCG', 'ML' => 'YMML', 'PH' => 'YPPH', 'SY' => 'YSSY', 'TL' => 'YBTL', 'WLM' => 'YWLM', 'AMB' => 'YAMB', 'CIN' => 'YCIN', 'AF' => 'YBAF', 'AV' => 'YMAV', 'BK' => 'YSBK', 'CN' => 'YSCN', 'HB' => 'YMHB', 'JT' => 'YPJT', 'LT' => 'YMLT', 'MK' => 'YBMK', 'MB' => 'YMMB', 'PF' => 'YPPF', 'RK' => 'YBRK', 'AY' => 'YMAY', 'AS' => 'YBAS', 'BRM' => 'YBRM', 'CFS' => 'YCFS', 'HM' => 'YBHM', 'KA' => 'YPKA', 'SU' => 'YBSU', 'TW' => 'YSTW', 'WR' => 'YPWR'];

        return $australianCallsigns[$string] ?? false;
    }

    public static function returnAmericanIcao($string)
    {
        return 'K' . $string;
    }
}
