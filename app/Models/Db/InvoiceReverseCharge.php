<?php

namespace App\Models\Db;

use App\Modules\SaleInvoice\Traits\FindBySlug;

class InvoiceReverseCharge extends Model
{
    use FindBySlug;

    protected $guarded = [];

    /**
     * Verify whether slug is same as given.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function hasSlug($slug)
    {
        return $this->slug == $slug;
    }
}
