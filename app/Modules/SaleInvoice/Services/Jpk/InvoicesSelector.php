<?php

namespace App\Modules\SaleInvoice\Services\Jpk;

use App\Models\Db\Company;
use App\Models\Db\Invoice;
use App\Models\Other\InvoiceTypeStatus;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class InvoicesSelector
{
    /**
     * Empty model just to run query (this is not existing Eloquent model).
     *
     * @var Invoice
     */
    private $invoice;

    /**
     * InvoicesSelector constructor.
     *
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get invoices for JPK for selected company and period.
     *
     * @param Company $company
     * @param string $start_date
     * @param string $end_date
     *
     * @return Collection
     */
    public function get(Company $company, $start_date, $end_date)
    {
        $invoices = $this->runQuery($company, $start_date, $end_date);

        $this->loadRelationships($invoices);

        return $invoices;
    }

    /**
     * Run query to get invoices.
     *
     * @param Company $company
     * @param string $start_date
     * @param string $end_date
     *
     * @return Collection
     */
    protected function runQuery(Company $company, $start_date, $end_date)
    {
        return $this->invoice->withoutTrashed()
            ->companyId($company->getCompanyId())
            ->whereDoesntHave('invoiceType', function ($q) {
                $q->where('slug', InvoiceTypeStatus::PROFORMA);
            })
            ->where('sale_date', '>=', $start_date)
            ->where('sale_date', '<=', $end_date)
            ->orderBy('id', 'ASC')
            ->get();
    }

    /**
     * Try to eager load relationships to reduce database queries amount. It might fail in case too
     * many invoices and too many binding parameters and in such case we only save it to log and
     * proceed further with running multiple required queries.
     *
     * @param Collection $invoices
     */
    protected function loadRelationships(Collection $invoices)
    {
        try {
            $invoices->load(
                'invoiceContractor.vatinPrefix',
                'invoiceCompany.vatinPrefix',
                'invoiceType.parentType',
                'invoiceMarginProcedure',
                'invoiceReverseCharge',
                'correctedInvoice',
                'items.serviceUnit',
                'items.vatRate',
                'taxes.vatRate'
            );
        } catch (Exception $e) {
            \Log::error($e);
        }
    }
}
