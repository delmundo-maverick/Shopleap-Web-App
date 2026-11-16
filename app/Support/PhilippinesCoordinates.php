<?php

namespace App\Support;

class PhilippinesCoordinates
{
    public static function contains(float $latitude, float $longitude): bool
    {
        if ($latitude < 4 || $latitude > 22 || $longitude < 116 || $longitude > 127) {
            return false;
        }

        static $boundary;
        $boundary ??= json_decode(file_get_contents(resource_path('js/pages/company/philippines.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($boundary['features'] as $feature) {
            $geometry = $feature['geometry'];
            $polygons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
            foreach ($polygons as $polygon) {
                if (! self::inRing($latitude, $longitude, $polygon[0])) {
                    continue;
                }
                foreach (array_slice($polygon, 1) as $hole) {
                    if (self::inRing($latitude, $longitude, $hole)) {
                        continue 2;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /** @param array<int, array{0: float, 1: float}> $ring */
    private static function inRing(float $latitude, float $longitude, array $ring): bool
    {
        $inside = false;
        for ($i = 0, $j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$x, $y] = $ring[$i];
            [$previousX, $previousY] = $ring[$j];
            $cross = ($longitude - $x) * ($previousY - $y) - ($latitude - $y) * ($previousX - $x);
            if (abs($cross) < 1.0e-10 && $longitude >= min($x, $previousX) && $longitude <= max($x, $previousX)
                && $latitude >= min($y, $previousY) && $latitude <= max($y, $previousY)) {
                return true;
            }
            if (($y > $latitude) !== ($previousY > $latitude)
                && $longitude < ($previousX - $x) * ($latitude - $y) / ($previousY - $y) + $x) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
