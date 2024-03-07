<?php

namespace App\Interfaces;

use App\Http\Requests\Request;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\User;

interface BuilderUpdateInvoice
{
    public function initUpdate(Request $request, ModelInvoice $invoice, User $user);

    public function updateWithCurrentCompanyData();

    public function updateWithCurrentContractorData();

    public function updateDeliveryAddress();

    public function update();

    public function getInvoice(): ModelInvoice;

    public function updateSpecialPayment();
}
