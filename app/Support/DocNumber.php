<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Unique document numbers like PUR-260923-7KQ2. */
class DocNumber
{
    public static function make(string $prefix, string $modelClass, string $column = 'number'): string
    {
        do {
            $n = $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while ($modelClass::where($column, $n)->exists());

        return $n;
    }
}
