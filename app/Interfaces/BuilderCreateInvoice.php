<?php

namespace App\Interfaces;

use App\Http\Requests\Request;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\User;

interface BuilderCreateInvoice
{
    public function create(Request $request, InvoiceRegistry $registry, User $user);

    public function addItems();

    public function copyInvoiceCompanyData();

    public function copyInvoiceContractorData();

    public function setDeliveryAddress();

    public function makeTaxesBilling();

    public function getInvoice(): ModelInvoice;

    public function createSpecialPayment();
}
