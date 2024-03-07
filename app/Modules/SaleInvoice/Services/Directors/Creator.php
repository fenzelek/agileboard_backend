<?php

namespace App\Modules\SaleInvoice\Services\Directors;

use App\Http\Requests\Request;
use App\Interfaces\BuilderCreateInvoice;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\User;
use App\Modules\SaleInvoice\Services\Builders\Correction;
use App\Modules\SaleInvoice\Services\Builders\Vat;

class Creator extends Director
{
    /**
     * @var InvoiceRegistry
     */
    protected $registry;

    /**
     * Build invoice.
     *
     * @param BuilderCreateInvoice $builder_invoice
     *
     * @return ModelInvoice
     */
    public function build(BuilderCreateInvoice $builder_invoice): ModelInvoice
    {
        $builder_invoice->create($this->request, $this->registry, $this->user);
        $builder_invoice->addItems();
        $builder_invoice->copyInvoiceCompanyData();
        $builder_invoice->copyInvoiceContractorData();
        $builder_invoice->setDeliveryAddress();
        $builder_invoice->makeTaxesBilling();
        if ($builder_invoice instanceof Correction || $builder_invoice instanceof Vat) {
            $builder_invoice->setParent();
            $builder_invoice->setDocument();
        }
        $builder_invoice->createSpecialPayment();

        $invoice = $builder_invoice->getInvoice();

        // Marking invoice registry that it was used
        $this->registry->update(['is_used' => true]);

        // Marking contractor that it was used
        $invoice->contractor->update(['is_used' => true]);

        return $invoice;
    }

    /**
     * Set incoming params for new invoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     */
    public function incomingParams(Request $request, InvoiceRegistry $registry, User $user)
    {
        $this->user = $user;
        $this->request = $request;
        $this->registry = $registry;
    }
}
