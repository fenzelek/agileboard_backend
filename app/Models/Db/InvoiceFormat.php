<?php

namespace App\Models\Db;

class InvoiceFormat extends Model
{
    const YEARLY_FORMAT = '{%nr}/{%Y}';
    const MONTHLY_FORMAT = '{%nr}/{%m}/{%Y}';

    protected $fillable = [
        'name',
        'format',
        'example',
    ];

    /**
     * Find InvoiceFormat by format column.
     *
     * @param $format
     *
     * @return mixed
     */
    public static function findByFormatStrict($format)
    {
        return self::where('format', $format)->firstOrFail();
    }
}
