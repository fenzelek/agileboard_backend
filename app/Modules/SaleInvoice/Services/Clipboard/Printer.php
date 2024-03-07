<?php

namespace App\Modules\SaleInvoice\Services\Clipboard;

use App\Models\Db\Invoice;
use App\Models\Other\ModuleType;
use App\Modules\SaleInvoice\Exceptions\NoAdditionalInvoiceDependenciesException;
use App\Modules\SaleInvoice\Exceptions\NoLoadInvoiceDependenciesException;
use Illuminate\Support\Collection;
use PDF;

class Printer
{
    private $required_invoice_relations = [
        'paymentMethod',
        'company',
        'invoiceCompany',
        'invoiceContractor',
        'invoiceType',
        'items',
        'taxes',
        'drawer',
    ];

    private $required_invoice_item_relations = [
        'vatRate',
        'serviceUnit',
    ];

    private $required_invoice_tax_relations = [
        'vatRate',
    ];
    private $addition_invoice_relations = [
        'receipts',
        'proforma',
        'correctedInvoice',
        'invoiceDeliveryAddress',
        'invoiceMarginProcedure',
        'specialPayments',
        'bankAccount',
    ];

    private $addition_invoice_item_relations = [
        'positionCorrected',
    ];

    /**
     * @var Invoice
     */
    private $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function render()
    {
        return $this->loadDependencies()->print();
    }

    private function loadDependencies()
    {
        $this->invoice->loadMissing(array_merge(
            $this->required_invoice_relations,
            $this->addition_invoice_relations
        ));
        $this->checkLoadingDependencies($this->invoice, $this->required_invoice_relations);

        collect($this->invoice->items)->each(function ($item) {
            $item->loadMissing(array_merge(
                $this->required_invoice_item_relations,
                $this->addition_invoice_item_relations
            ));
            $this->checkLoadingDependencies($item, $this->required_invoice_item_relations);
        });

        collect($this->invoice->taxes)->each(function ($tax) {
            $tax->loadMissing($this->required_invoice_tax_relations);
            $this->checkLoadingDependencies($tax, $this->required_invoice_tax_relations);
        });

        return $this;
    }

    private function checkLoadingDependencies($resource, array $relations)
    {
        if (empty($relations)) {
            return;
        }
        collect($relations)->each(function ($relation) use ($resource) {
            $dependencies = array_get($resource->getRelations(), $relation);
            if (empty($dependencies) || (is_a($dependencies, Collection::class) && $dependencies->count() === 0)) {
                throw new NoLoadInvoiceDependenciesException('Cannot load ' . $relation);
            }
        });
    }

    private function print()
    {
        try {
            return PDF::loadView('pdf.invoice', [
                'invoice' => $this->invoice,
                'duplicate' => false,
                'bank_info' => $this->invoice->paymentMethod->paymentPostponed(),
                'footer' => $this->invoice->company
                    ->appSettings(ModuleType::INVOICES_FOOTER_ENABLED),
            ])->output();
        } catch (\Exception $exception) {
            throw new NoAdditionalInvoiceDependenciesException($exception->getMessage());
        }
    }
}
