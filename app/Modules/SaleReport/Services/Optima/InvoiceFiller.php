<?php

namespace App\Modules\SaleReport\Services\Optima;

use App\Models\Db\Invoice;
use App\Models\Other\SaleReport\Optima\TaxItem;
use Illuminate\Support\Carbon;

class InvoiceFiller
{
    /**
     * Date format.
     */
    const DATE_FORMAT = 'y/m/d';

    /**
     * Value for TYP field.
     */
    const SALE_REGISTRY = 2;

    /**
     * Value for ZAKUP field.
     */
    const NO_SALE = 0;

    /**
     * @var FieldFiller
     */
    protected $filler;

    /**
     * @var GenericFieldFiller
     */
    protected $generic_filler;

    /**
     * @var TaxesFiller
     */
    protected $tax_filler;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * InvoiceFiller constructor.
     *
     * @param GenericFieldFiller $generic_filler
     * @param FieldFiller $filler
     * @param TaxesFiller $tax_filler
     */
    public function __construct(
        GenericFieldFiller $generic_filler,
        FieldFiller $filler,
        TaxesFiller $tax_filler
    ) {
        $this->filler = $filler;
        $this->generic_filler = $generic_filler;
        $this->tax_filler = $tax_filler;
    }

    /**
     * Get row fields for single Invoice for Optima.
     *
     * @param Invoice $invoice
     *
     * @return array
     * @throws \Exception
     */
    public function getFields(Invoice $invoice)
    {
        $this->fields = $this->generic_filler->fieldsWithDefaultValues();
        $this->filler->setInvoice($invoice);

        $this->setBasicFields($invoice);
        $this->setContractorFields();
        $this->setAdditionalFields($invoice);
        $this->setTaxesFields($this->tax_filler->calculate($invoice));

        return $this->fields;
    }

    /**
     * Set basic row fields.
     *
     * @param Invoice $invoice
     */
    protected function setBasicFields(Invoice $invoice)
    {
        $this->fields['ID'] = $invoice->id;
        $this->fields['GRUPA'] = 'SPRZE';
        $this->fields['DATA_TR'] = $this->formatDate($invoice->issue_date);
        $this->fields['DATA_WYST'] = $this->formatDate($invoice->sale_date);
        $this->fields['DOKUMENT'] = $this->filler->getDocumentNumber();
        $this->fields['KOREKTA_DO'] = $this->filler->getCorrectedDocumentNumber();
        $this->fields['TYP'] = static::SALE_REGISTRY;
        $this->fields['KOREKTA'] = $this->filler->getDocumentType();
        $this->fields['ZAKUP'] = static::NO_SALE;
        $this->fields['KASA'] = $this->filler->getReceiptStatus();
    }

    /**
     * Set contractor related fields.
     */
    protected function setContractorFields()
    {
        list($contractor_name_1, $contractor_name_2) = $this->filler->getContractorName();
        $this->fields['K_NAZWA1'] = $contractor_name_1;
        $this->fields['K_NAZWA2'] = $contractor_name_2;
        $this->fields['K_ADRES1'] = $this->filler->getContractorAddress();
        $this->fields['K_KODP'] = $this->filler->getContractorZipCode();
        $this->fields['K_MIASTO'] = $this->filler->getContractorCity();
        $this->fields['NIP'] = $this->filler->getContractorVatin();
        $this->fields['FIN'] = $this->filler->getContractorType();
    }

    /**
     * Set additional row fields.
     *
     * @param Invoice $invoice
     *
     * @throws \Exception
     */
    protected function setAdditionalFields(Invoice $invoice)
    {
        $this->fields['EXPORT'] = $this->filler->getExportType();
        $this->fields['ODLICZENIA'] = $this->filler->getDeductionValue($this->fields['EXPORT']);
        $this->fields['ROZLICZONO'] = $this->filler->getPaidStatus();
        $this->fields['PLATNOSC'] = $this->filler->getPaymentMethod();
        $this->fields['TERMIN'] = $this->formatDate($invoice->getPaymentDue());
        $this->fields['BRUTTO'] = number_format_output($invoice->price_gross, '.');
        $this->fields['ZAPLATA'] = number_format_output($invoice->getRawPaidAmount(), '.');
    }

    /**
     * Format date into required date format.
     *
     * @param string $date
     *
     * @return string
     */
    protected function formatDate($date)
    {
        return Carbon::parse($date)->format(static::DATE_FORMAT);
    }

    /**
     * Set taxes fields.
     *
     * @param array $taxes
     */
    protected function setTaxesFields(array $taxes)
    {
        foreach ($taxes as $index => $tax) {
            $this->setTaxFields($tax, $index + 1);
        }
    }

    /**
     * Set taxes fields for single TaxItem.
     *
     * @param TaxItem $tax_item
     * @param $index
     */
    protected function setTaxFields(TaxItem $tax_item, $index)
    {
        $this->fields['FLAGA_' . $index] = $tax_item->getType();
        $this->fields['STAWKA_' . $index] = number_format($tax_item->getTaxRate(), 2);
        $this->fields['NETTO_' . $index] = number_format_output($tax_item->getNetPrice(), '.');
        $this->fields['VAT_' . $index] = number_format_output($tax_item->getVat(), '.');
    }
}
