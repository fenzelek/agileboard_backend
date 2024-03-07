<?php

namespace App\Models\Db;

use App\Modules\SaleInvoice\Traits\FindBySlug;

class VatReleaseReason extends Model
{
    use FindBySlug;

    protected $guarded = [];
}
