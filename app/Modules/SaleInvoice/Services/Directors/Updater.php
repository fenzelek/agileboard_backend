<?php

namespace App\Modules\SaleInvoice\Services\Directors;

use App\Http\Requests\Request;
use App\Interfaces\BuilderUpdateInvoice;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\User;

class Updater extends Director
{
    /**
     * @var ModelInvoice
     */
    protected $invoice;

    /**
     * Build updating invoice.
     *
     * @param BuilderUpdateInvoice $builder_invoice
     *
     * @return ModelInvoice
     */
    public function build(BuilderUpdateInvoice $builder_invoice): ModelInvoice
    {
        $builder_invoice->initUpdate($this->request, $this->invoice, $this->user);
        $builder_invoice->updateWithCurrentCompanyData();
        $builder_invoice->updateWithCurrentContractorData();
        $builder_invoice->updateDeliveryAddress();
        $builder_invoice->update();
        $builder_invoice->updateSpecialPayment();

        return $builder_invoice->getInvoice();
    }

    /**
     * Set incoming params for new invoice.
     *
     * @param Request $request
     * @param ModelInvoice $invoice
     * @param User $user
     */
    public function incomingParams(Request $request, ModelInvoice $invoice, User $user)
    {
        $this->user = $user;
        $this->request = $request;
        $this->invoice = $invoice;
    }
}
