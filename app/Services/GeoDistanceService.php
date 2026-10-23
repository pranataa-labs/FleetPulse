<?php

namespace App\Services;

class GeoDistanceService
{
    /**
     * Earth radius in kilometers.
     */
    public const EARTH_RADIUS_KM = 6371.0;

    /**
     * Calculate spherical distance between two coordinates using the Haversine formula.
     *
     * @param float $lat1 Latitude of point 1 in degrees
     * @param float $lon1 Longitude of point 1 in degrees
     * @param float $lat2 Latitude of point 2 in degrees
     * @param float $lon2 Longitude of point 2 in degrees
     * @return float Distance in kilometers (rounded to 2 decimals)
     */
    public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $dLat = $lat2Rad - $lat1Rad;
        $dLon = $lon2Rad - $lon1Rad;

        $a = sin($dLat / 2) ** 2 + cos($lat1Rad) * cos($lat2Rad) * (sin($dLon / 2) ** 2);
        $c = 2 * asin(min(1.0, sqrt($a)));

        $distance = self::EARTH_RADIUS_KM * $c;

        return round($distance, 2);
    }
}
