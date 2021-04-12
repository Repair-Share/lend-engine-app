<?php

namespace AppBundle\Helpers;

class DateTimeHelper
{

    /**
     * @param $string
     * @return mixed
     */
    public static function parseTime($string)
    {
        $string = trim($string);

        $time = (int)$string;

        $timeStr = '';

        if (!$time) {
            return '';
        }

        if (strlen($string) === 3) { // HMM format

            $hours   = substr($string, 0, 1);
            $minutes = substr($string, 1, 2);

        } else { // HHMM format

            $hours   = substr($string, 0, 2);
            $minutes = substr($string, 2, 2);

        }

        if (!$hours) {
            return '';
        }

        if ($minutes) {
            $timeStr .= $hours . $minutes;
        } else {
            $timeStr .= $hours . ':00';
        }

        return $timeStr;
    }
}