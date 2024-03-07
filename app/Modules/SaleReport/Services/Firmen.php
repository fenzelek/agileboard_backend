<?php

namespace App\Modules\SaleReport\Services;

use App\Models\Db\CompanyService;
use App\Models\Db\Invoice as InvoiceModel;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\PaymentMethod;
use App\Models\Db\VatRate;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleReport\Services\Contracts\ExternalExportProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class Firmen implements ExternalExportProvider
{
    const CASH = 1;
    const CHECK = 2;
    const BANK_TRANSFER = 3;

    protected $taxes_ids;

    /**
     * @inheritdoc
     */
    public function getFileContent(Collection $invoices)
    {
        return mb_convert_encoding($this->parseData($invoices), 'ISO-8859-2', 'UTF-8');
    }

    /**
     * @inheritdoc
     */
    public function getFileName()
    {
        return 'firmen' . Carbon::now()->toDateString() . '.csv';
    }

    /**
     * @inheritdoc
     */
    public function getFileContentType()
    {
        return 'text/csv';
    }

    /**
     * Parsing data to specific structure.
     *
     * @param Collection $invoices
     *
     * @return string
     */
    protected function parseData(Collection $invoices)
    {
        $this->taxes_ids = $this->getTaxesIds();
        $data = [];

        foreach ($invoices as $key => $invoice) {
            $data[$key] = $this->getTemplateArray();

            $contractor = $invoice->invoiceContractor;
            $corrected_invoice = $invoice->correctedInvoice;
            $taxes = $invoice->taxes;

            $data[$key] = $this->getContractorData($contractor, $data[$key]);
            $data[$key] = $this->getInvoiceData($invoice, $corrected_invoice, $data[$key]);
            if ($invoice->invoiceType->slug != InvoiceTypeStatus::REVERSE_CHARGE &&
                $invoice->invoiceType->slug != InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION
            ) {
                $data[$key] = $this->getTaxesData($taxes, $corrected_invoice, $data[$key]);
            }
            $data[$key] = $this->getCorrectedInvoiceData($corrected_invoice, $data[$key], $invoice);

            // @todo invoice advance
            // $data[$key]['ZALICZKA'];
            // $data[$key]['STAWKA_Z'];
        }
        foreach ($data as $key => $row) {
            $data[$key] = implode(';', $row);
        }
        $data = implode("\r\n", $data);

        return $data;
    }

    /**
     * Return correct Firmen payment method based on given invoice payment method id.
     *
     * @param int $invoice_payment_method
     *
     * @return int
     */
    protected function getPaymentMethod($invoice_payment_method)
    {
        if (PaymentMethod::paymentInAdvance($invoice_payment_method)) {
            return self::CASH;
        }

        return self::BANK_TRANSFER;
    }

    /**
     * Change date format to be accepted by Firmen client.
     *
     * @param string $date
     *
     * @return string
     */
    protected function formatDate($date)
    {
        return Carbon::parse($date)->format('Ymd');
    }

    /**
     * Returns Firmen document type.
     *
     * @param InvoiceModel $invoice
     *
     * @return int|string
     */
    protected function getDocumentType(InvoiceModel $invoice)
    {
        if ($invoice->invoiceType->slug == InvoiceTypeStatus::REVERSE_CHARGE) {
            return 'G';
        }
        if ($invoice->invoiceType->slug == InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION) {
            return 'H';
        }
        if ($corrected_invoice = $invoice->correctedInvoice) {
            if ($corrected_invoice->receipts()->count()) {
                return 'A';
            }

            return 4;
        }
        if ($invoice->receipts()->count()) {
            return 0;
        }

        return 1;
    }

    /**
     * Returns array of Firmen structure of the row in csv file.
     *
     * @return array
     */
    protected function getTemplateArray()
    {
        return array_fill_keys([
            'K_KONTRAH',
            'K_KONTRAH1',
            'K_ULICA',
            'K_KOD',
            'K_MIEJSCE',
            'K_NUMERNIP',
            'NRDOK',
            'DATA',
            'WARTBRUTTO',
            'NET22',
            'VAT22',
            'NET7',
            'VAT7',
            'EKSP0',
            'SPKRAJ0',
            'NET12',
            'VAT12',
            'BEZ_VAT',
            'SPWOLNA',
            'VATSPRZ',
            'SPZAP',
            'DATAZAP',
            'ZAPLAC',
            'RODZDOK',
            'DATASP',
            'DATAOPOD',
            'DATATERMIN',
            'ZALICZKA',
            'STAWKA_Z',
            'NRDOKORG',
            'DATAORG',
            'ZMCENA',
            'ZMPODAT',
            'TYTULKOR',
            'ZMZWOL',
            'NETTOTOW',
            'NETTOUSL',
            'Rezerwa 1',
            'Rezerwa 2',
            'Rezerwa 3',
        ], '');
    }

    /**
     * Parsing contractor data and return array with filled fields.
     *
     * @param $contractor
     * @param $data
     *
     * @return array
     */
    protected function getContractorData($contractor, $data)
    {
        $contractor_name = mb_str_split($this->noSemicolon($contractor->name), 56);
        $data['K_KONTRAH'] = $contractor_name[0];
        if (isset($contractor_name[1])) {
            $data['K_KONTRAH1'] = $contractor_name[1];
        }
        $data['K_ULICA'] = $this->cutString($contractor->main_address_street . ' '
            . $contractor->main_address_number, 45);
        $data['K_KOD'] = $this->cutString($contractor->main_address_zip_code, 10);
        $data['K_MIEJSCE'] = $this->cutString($contractor->main_address_city, 40);
        $data['K_NUMERNIP'] = $this->cutString($contractor->fullVatin, 15);

        return $data;
    }

    /**
     * Parsing invoice data and return array with filled fields.
     *
     * @param $invoice
     * @param $corrected_invoice
     * @param $data
     *
     * @return array
     */
    protected function getInvoiceData($invoice, $corrected_invoice, $data)
    {
        $data['NRDOK'] = $this->noSemicolon($invoice->number);
        $data['DATA'] = $this->formatDate($invoice->issue_date);
        $data['SPZAP'] = $this->getPaymentMethod($invoice->payment_method_id);
        if ($invoice->paid_at) {
            $data['DATAZAP'] = $this->formatDate($invoice->paid_at);
        }
        $data['ZAPLAC'] = number_format_output($invoice->payment_left, '.');
        $data['RODZDOK'] = $this->getDocumentType($invoice);
        $data['DATASP'] = $this->formatDate($invoice->sale_date);
        $data['DATATERMIN'] = Carbon::parse($invoice->issue_date)
            ->addDays($invoice->payment_term_days)->format('Ymd');
        if (! $corrected_invoice) {
            $data['WARTBRUTTO'] = number_format_output($invoice->price_gross, '.');
            if ($invoice->invoiceType->slug != InvoiceTypeStatus::REVERSE_CHARGE &&
                $invoice->invoiceType->slug != InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION
            ) {
                $data['VATSPRZ'] = number_format_output($invoice->vat_sum, '.');
            }
        }
        if ($invoice->invoiceType->slug == InvoiceTypeStatus::REVERSE_CHARGE ||
            $invoice->invoiceType->slug == InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION
        ) {
            $data['NETTOTOW'] = number_format_output(
                $invoice->itemsType(CompanyService::TYPE_ARTICLE)->sum('price_gross_sum'),
                '.'
            );
            $data['NETTOUSL'] = number_format_output(
                $invoice->itemsType(CompanyService::TYPE_SERVICE)->sum('price_gross_sum'),
                '.'
            );
        }

        return $data;
    }

    /**
     * Parsing taxes data and return array with filled fields.
     *
     * @param Collection $taxes
     * @param Invoice|null $corrected_invoice
     * @param array $data
     *
     * @return array
     */
    protected function getTaxesData(Collection $taxes, $corrected_invoice, $data)
    {
        if ($tax23 = $this->getTax($taxes, '23')) {
            $data['NET22'] = number_format_output($tax23->price_net, '.');
            $data['VAT22'] =
                number_format_output($tax23->price_gross - $tax23->price_net, '.');
        }
        if ($tax8 = $this->getTax($taxes, '8')) {
            $data['NET7'] = number_format_output($tax8->price_net, '.');
            $data['VAT7'] = number_format_output($tax8->price_gross - $tax8->price_net, '.');
        }
        if ($tax0exp = $this->getTax($taxes, '0EXP')) {
            $data['EKSP0'] = number_format_output($tax0exp->price_gross, '.');
        }
        if ($tax0wdt = $this->getTax($taxes, '0WDT')) {
            $data['EKSP0'] = number_format_output(
                $data['EKSP0'] * 100 + $tax0wdt->price_gross,
                '.'
            );
        }
        if ($tax0 = $this->getTax($taxes, '0')) {
            $data['SPKRAJ0'] = number_format_output($tax0->price_gross, '.');
        }
        if ($tax5 = $this->getTax($taxes, '5')) {
            $data['NET12'] = number_format_output($tax5->price_net, '.');
            $data['VAT12'] = number_format_output($tax5->price_gross - $tax5->price_net, '.');
        }
        if ($taxNp = $this->getTax($taxes, 'np.')) {
            $data['BEZ_VAT'] = number_format_output($taxNp->price_gross, '.');
        }
        if ($taxNpEu = $this->getTax($taxes, 'np.UE')) {
            $data['BEZ_VAT'] = number_format_output(
                $data['BEZ_VAT'] * 100 + $taxNpEu->price_gross,
                '.'
            );
        }
        if ($corrected_invoice) {
            if ($tax_zw = $this->getTax($taxes, 'zw.')) {
                $data['ZMZWOL'] = number_format_output($tax_zw->price_gross, '.');
            }
        } else {
            if ($tax_zw = $this->getTax($taxes, 'zw.')) {
                $data['SPWOLNA'] = number_format_output($tax_zw->price_gross, '.');
            }
        }

        return $data;
    }

    /**
     * Parsing corrected invoice data and return array with filled fields.
     *
     * @param Invoice|null $corrected_invoice
     * @param array $data
     * @param InvoiceModel $invoice
     *
     * @return array
     */
    protected function getCorrectedInvoiceData($corrected_invoice, $data, InvoiceModel $invoice)
    {
        if ($corrected_invoice) {
            $data['NRDOKORG'] = $this->noSemicolon($corrected_invoice->number);
            $data['DATAORG'] = $this->formatDate($corrected_invoice->issue_date);
            $data['ZMCENA'] = number_format_output($invoice->price_net, '.');
            $data['ZMPODAT'] = number_format_output($invoice->vat_sum, '.');
            $data['TYTULKOR'] = $this->cutString(
                InvoiceCorrectionType::all($invoice->company)[$invoice->correction_type],
                33
            );
        }

        return $data;
    }

    /**
     * Return tax from taxes based on pass tax name.
     *
     * @param Collection $taxes
     * @param string $tax_name
     *
     * @return InvoiceTaxReport
     */
    protected function getTax(Collection $taxes, $tax_name)
    {
        return $taxes->where('vat_rate_id', $this->taxes_ids[$tax_name])->first();
    }

    /**
     * Return array with taxes ids.
     *
     * @return array
     */
    protected function getTaxesIds()
    {
        return [
            '23' => VatRate::findByName('23%')->id,
            '8' => VatRate::findByName('8%')->id,
            '0' => VatRate::findByName('0%')->id,
            '5' => VatRate::findByName('5%')->id,
            'np.' => VatRate::findByName('np.')->id,
            'np.UE' => VatRate::findByName('np. UE')->id,
            'zw.' => VatRate::findByName('zw.')->id,
            '0EXP' => VatRate::findByName('0% EXP')->id,
            '0WDT' => VatRate::findByName('0% WDT')->id,
        ];
    }

    /**
     * Cut string to given max length.
     *
     * @param string $string
     * @param int $max_length
     *
     * @return bool|string
     */
    protected function cutString($string, $max_length)
    {
        return mb_substr($this->noSemicolon($string), 0, $max_length);
    }

    /**
     * Replace semicolons with spaces.
     *
     * @param $string
     *
     * @return string
     */
    protected function noSemicolon($string)
    {
        return trim(str_replace(';', ' ', $string));
    }
}
