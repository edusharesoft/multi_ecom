<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

/**
 * Safe "from / to" filters. Comparing the raw column with a range (instead of whereDate())
 * lets MySQL use the (branch_id, created_at) indexes; bad input is ignored instead of a 500.
 */
class Dates
{
    public static function start(mixed $value): ?Carbon
    {
        try {
            return is_string($value) && trim($value) !== '' ? Carbon::parse($value)->startOfDay() : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function end(mixed $value): ?Carbon
    {
        return static::start($value)?->endOfDay();
    }
}
