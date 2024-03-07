<?php

namespace App\Models\Other\SaleInvoice\Payers;

use App\Models\Db\VatRate;
use App\Models\Other\InvoiceCountType;
use App\Models\Other\VatRateType;

class NoVat
{
    const COUNT_TYPE = InvoiceCountType::GROSS;

    const VAT_RATE = VatRateType::NP;

    public static function vatAmount()
    {
        return VatRate::findByName(self::VAT_RATE)->rate;
    }
}
