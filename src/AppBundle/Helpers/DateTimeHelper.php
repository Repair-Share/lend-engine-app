<?php

namespace AppBundle\Helpers;

class DateTimeHelper
{
    public static function leadingZero($string)
    {
        return str_pad($string, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @param $string
     * @return mixed
     */
    public static function parseTime($string)
    {
        $string = trim($string);

        $hours = $minutes = -1;

        // Missing string -> No further action needed
        if (!$string) {
            return '';
        }

        // If this is not a numeric value, give up the parsing
        $numericTest = (int)$string;

        if (!$numericTest) {
            return '';
        }

        if (strlen($string) <= 2) { // Only hours added
            $hours   = (int)$string;
            $minutes = 0;
        } else {

            if (preg_match('/^[0-9]{1,2}:[0-9]{2}$/', $string, $matches)) { // Expected format HH:MM, H:MM

                $parts = explode(':', $string);

                $hours   = $parts[0];
                $minutes = $parts[1];

            } elseif (preg_match('/^[0-9]{1,2}:[0-9]{1}$/', $string, $matches)) { // HH:M format

                $parts = explode(':', $string);

                $hours   = $parts[0];
                $minutes = $parts[1];

            } elseif (preg_match('/^[0-9]{3}$/', $string, $matches)) { // HMM

                $hours   = substr($string, 0, 1);
                $minutes = substr($string, 1, 2);

            } elseif (preg_match('/^[0-9]{4}$/', $string, $matches)) { // HHMM

                $hours   = substr($string, 0, 2);
                $minutes = substr($string, 2, 2);

            }

        }

        if ($hours < 0 || $hours > 23) {
            return '';
        }

        if ($minutes < 0 || $minutes > 59) {
            return '';
        }

        return self::leadingZero($hours) . ':' . self::leadingZero($minutes);
    }
}