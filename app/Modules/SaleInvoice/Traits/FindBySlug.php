<?php

namespace App\Modules\SaleInvoice\Traits;

trait FindBySlug
{
    /**
     * Find model by given slug.
     *
     * @param $slug
     * @return mixed
     */
    public static function findBySlug($slug)
    {
        return self::where('slug', trim($slug))->firstOrFail();
    }
}
