<?php

    if(!function_exists('headingCorrect')) {
        function headingCorrect(float $heading){
            if($heading > 360.0){
                $heading = $heading - 360;
            } elseif($heading < 1.0) {
                $heading = 360 - abs($heading);
            }
            
            return $heading;
        }        
    }

    if(!function_exists('headingLessThan')) {
        function headingLessThan(float $heading, float $heading2){
            
            if($heading < $heading2 || headingCorrect($heading-$heading2) < $heading2){
                return true;
            }
            
            return false;
        }        
    }

?>