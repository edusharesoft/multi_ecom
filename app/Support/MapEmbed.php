<?php

namespace App\Support;

use App\Models\Branch;

/**
 * Google Maps for the "Location" popup (no API key needed).
 * Short share links (maps.app.goo.gl) cannot be embedded, so the map is built from
 * coordinates found in map_url, or else from the store name + address.
 */
class MapEmbed
{
    public static function query(Branch $b): string
    {
        $url = (string) $b->map_url;
        if ($url !== '') {
            if (preg_match('~@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)~', $url, $m)
                || preg_match('~[?&](?:q|query|ll)=(-?\d{1,2}\.\d+)(?:,|%2C)\s*(-?\d{1,3}\.\d+)~i', $url, $m)
                || preg_match('~!3d(-?\d{1,2}\.\d+)!4d(-?\d{1,3}\.\d+)~', $url, $m)) {
                return $m[1].','.$m[2];
            }
            if (preg_match('~/maps/place/([^/@?]+)~', $url, $m)) {
                return urldecode(str_replace('+', ' ', $m[1]));
            }
        }

        return trim($b->display_name.', '.$b->address, ' ,');
    }

    public static function src(Branch $b): string
    {
        $url = (string) $b->map_url;
        if (preg_match('~^https://(www\.)?google\.[a-z.]+/maps/embed\?~i', $url)) {
            return $url;   // already an embed link
        }

        return 'https://maps.google.com/maps?q='.rawurlencode(self::query($b)).'&z=15&output=embed';
    }

    /** Link for the "open in Google Maps" button. */
    public static function openUrl(Branch $b): string
    {
        $url = (string) $b->map_url;

        return preg_match('~^https?://~i', $url) ? $url : 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(self::query($b));
    }
}
