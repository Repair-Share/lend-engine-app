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
     * @return \DateTime (Default fallback is the current datetime)
     */
    public static function parseDateTime($string)
    {
        // Tries to parse in a standard way, if it's ok, yay
        try {
            return new \DateTime($string);
        } catch (\Exception $e) {
        }

        // Error on the given param, tries to sanitize
        try {

            // Expected format YYYY-MM-DDThh:mm:ss
            $dString = substr($string, 0, 19);
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $dString, $matches)) {
                return new \DateTime($dString);
            }

        } catch (\Exception $e) {
        }

        return new \DateTime();
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

    /**
     * Change local time to UTC
     */
    public static function changeLocalTimeToUtc($settingsTimeZone, \DateTime $time)
    {
        if (!$settingsTimeZone) {
            $settingsTimeZone = 'Europe/London';
        }

        $tz = new \DateTimeZone($settingsTimeZone);

        $utc    = new \DateTime('now', new \DateTimeZone('UTC'));
        $offSet = $tz->getOffset($utc) / 3600 * -1;

        $time->modify("{$offSet} hours");

        return $time;
    }
}