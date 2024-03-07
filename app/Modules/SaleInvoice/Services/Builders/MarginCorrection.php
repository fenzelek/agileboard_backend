<?php

namespace App\Modules\SaleInvoice\Services\Builders;

use App\Http\Requests\Request;
use App\Interfaces\BuilderCorrection;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\User;

class MarginCorrection extends Correction implements BuilderCorrection
{
    /**
     * Create new invoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     */
    public function create(Request $request, InvoiceRegistry $registry, User $user)
    {
        parent::create($request, $registry, $user);

        $this->invoice->invoice_margin_procedure_id = $this->request->input('invoice_margin_procedure_id');
        $this->invoice->save();
    }

    /**
     * Update invoice.
     */
    public function update()
    {
        $this->invoice->invoice_margin_procedure_id = $this->request->input('invoice_margin_procedure_id');
        parent::update();
    }
}
