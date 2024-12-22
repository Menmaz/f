<?php

namespace App\Helpers; // hoặc tên namespace phù hợp với ứng dụng của bạn

class ViewHelper
{
    public static function formatViews($views)
    {
        if ($views === null) {
            return null; // or return '0' or any other default value
        }
        
        if ($views >= 1000000000) {
            return number_format($views / 1000000000, 1) . 'B';
        } elseif ($views >= 1000000) {
            return number_format($views / 1000000, 1) . 'M';
        } elseif ($views >= 1000) {
            return number_format($views / 1000, 1) . 'k';
        } else {
            return number_format($views);
        }
    }

}
