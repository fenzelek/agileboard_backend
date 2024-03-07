<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers;

use App\Models\Db\BankAccount;
use App\Models\Db\Company;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceDeliveryAddress;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoicePayment;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\PaymentMethod;
use App\Models\Db\Receipt;
use App\Models\Db\ServiceUnit;
use App\Models\Db\User;
use App\Models\Db\VatRate;
use App\Modules\SaleInvoice\Services\Clipboard\FileManager;
use Illuminate\Database\Eloquent\Collection;

trait CreateInvoice
{
    public function getInvoice()
    {
        return new Invoice();
    }

    /**
     * @param $file_name
     * @return string
     */
    protected function getStorageDiskFilepath($file_name): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            FileManager::CLIPBOARD_DIRECTORY,
            FileManager::PREFIX_DIRECTORY . $this->company->id,
            $file_name,
        ]);
    }

    /**
     * @param $file_name
     * @return string
     */
    protected function getRootFilePath($file_name): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            config('filesystems.disks.local.root'),
            FileManager::CLIPBOARD_DIRECTORY,
            FileManager::PREFIX_DIRECTORY . $this->company->id,
            $file_name,
        ]);
    }

    private function setRequiredDependencies($invoice)
    {
        $invoice->setRelations($this->requiredDependencies());
    }

    private function allDependencies($invoice)
    {
        $invoice->setRelations(array_merge(
            $this->requiredDependencies(),
            $this->additionalDependencies()
        ));
    }

    /**
     * @return array
     */
    private function requiredDependencies(): array
    {
        return [
            'paymentMethod' => new PaymentMethod(),
            'company' => new Company(),
            'invoiceCompany' => new InvoiceCompany(),
            'invoiceContractor' => new InvoiceContractor(),
            'invoiceType' => new InvoiceType(),
            'items' => Collection::make([
                (new InvoiceItem())->setRelations(['vatRate' => new VatRate(), 'serviceUnit' => new ServiceUnit()]),
                (new InvoiceItem())->setRelations(['vatRate' => new VatRate(), 'serviceUnit' => new ServiceUnit()]),
            ]),
            'taxes' => Collection::make([
                (new InvoiceTaxReport())->setRelations(['vatRate' => new VatRate()]),
                (new InvoiceTaxReport())->setRelations(['vatRate' => new VatRate()]),
            ]),
            'drawer' => new User(),
        ];
    }

    /**
     * @return array
     */
    private function additionalDependencies(): array
    {
        return [
            'bankAccount' => new BankAccount(),
            'receipts' => Collection::make(new Receipt(), new Receipt()),
            'proforma' => new Invoice(),
            'correctedInvoice' => new Invoice(),
            'invoiceDeliveryAddress' => new InvoiceDeliveryAddress(),
            'invoiceMarginProcedure' => new InvoiceMarginProcedure(),
            'items.positionCorrected' => new InvoiceItem(),
            'items.positionCorrected.serviceUnit' => new ServiceUnit(),
            'specialPayments' => new InvoicePayment(),
            'specialPayments.paymentMethod' => new PaymentMethod(),
        ];
    }
}
