<?php

namespace App\Helpers; // hoặc tên namespace phù hợp với ứng dụng của bạn

use Carbon\Carbon;

class DateHelper
{
    public static function getLocalizedDiffForHumans($dateTime)
    {
        return Carbon::parse($dateTime)->locale(app()->getLocale())->diffForHumans(['short' => true]) ?: '-';
    }

    public static function localizeDate($date){
        Carbon::setLocale('vi');
        $formattedDate = optional($date)->format('d/m/Y'); 
        return $formattedDate ?? $date;
    }
}
