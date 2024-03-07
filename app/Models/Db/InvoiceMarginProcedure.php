<?php

namespace App\Models\Db;

use App\Modules\SaleInvoice\Traits\FindBySlug;

class InvoiceMarginProcedure extends Model
{
    use FindBySlug;

    protected $guarded = [];
}
