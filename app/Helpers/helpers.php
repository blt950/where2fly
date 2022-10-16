<?php

    if(!function_exists('correctHeading')) {
        function correctHeading(float $heading){
            if($heading > 360.0){
                $heading = $heading - 360;
            } elseif($heading < 1.0) {
                $heading = 360 - abs($heading);
            }
            
            return $heading;
        }        
    }

?>