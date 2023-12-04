<?php

namespace AppBundle\Helpers;

class GoogleMaps
{
    public static function geocodeAddress($address, $postcode, $country, $lookedUpAddress)
    {
        $lat = $lng = null;

        $addressLookup = '';

        if (trim($address)) {
            $addressLookup .= trim($address) . ',';
        }

        if (trim($postcode)) {
            $addressLookup .= trim($postcode) . ',';
        }

        if (trim($country)) {
            $addressLookup .= trim($country) . ',';
        }

        $addressLookup = rtrim($addressLookup, ',');

        // Address has not changed since the last geocode, do not lookup now
        if (trim($addressLookup) === trim($lookedUpAddress)) {
            return null;
        }

        if ($addressLookup) {

            $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($addressLookup) . '&key=' . getenv('GOOGLE_MAPS_API_KEY');

            $json = file_get_contents($url);

            $details = json_decode($json);

            if ($details) {

                if (isset($details->results[0])) {

                    $geometry = $details->results[0]->geometry->location;

                    $lat = $geometry->lat;
                    $lng = $geometry->lng;

                }

            }

        }

        return [
            'lat'             => $lat,
            'lng'             => $lng,
            'lookedUpAddress' => $addressLookup
        ];
    }

    public static function geocodeAddressLines($addressLine1, $addressLine2, $addressLine3, $addressLine4, $country)
    {
        $lat = $lng = null;

        $addressLookup = '';

        if (trim($addressLine1)) {
            $addressLookup .= trim($addressLine1) . ',';
        }

        if (trim($addressLine2)) {
            $addressLookup .= trim($addressLine2) . ',';
        }

        if (trim($addressLine3)) {
            $addressLookup .= trim($addressLine3) . ',';
        }

        if (trim($addressLine4)) {
            $addressLookup .= trim($addressLine4) . ',';
        }

        if (trim($country)) {
            $addressLookup .= trim($country) . ',';
        }

        $addressLookup = rtrim($addressLookup, ',');

        if ($addressLookup) {

            $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($addressLookup) . '&key=' . getenv('GOOGLE_MAPS_API_KEY');

            $json = file_get_contents($url);

            $details = json_decode($json);

            if ($details) {

                if (isset($details->results[0])) {

                    $geometry = $details->results[0]->geometry->location;

                    $lat = $geometry->lat;
                    $lng = $geometry->lng;

                }

            }

        }

        return [
            'lat'             => $lat,
            'lng'             => $lng,
            'lookedUpAddress' => $addressLookup
        ];
    }
}