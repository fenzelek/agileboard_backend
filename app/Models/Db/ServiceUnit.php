<?php

namespace App\Models\Db;

class ServiceUnit extends Model
{
    const SERVICE = 'usł.';
    const UNIT = 'szt.';
    const METR = 'm';
    const MONTH = 'mies.';
    const PACKAGE = 'opak.';

    const RUNNING_METRE = 'm.b.';
    const HOUR = 'godz.';
    const TON = 't';
    const KILOGRAM = 'kg';

    const NO_INDEXING = 999;

    protected $guarded = [];

    public static function findBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }
}
