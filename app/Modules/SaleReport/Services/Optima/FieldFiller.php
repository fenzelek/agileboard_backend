<?php

namespace App\Modules\SaleReport\Services\Optima;

use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\PaymentMethodType;
use Exception;

class FieldFiller
{
    use StringHelper;

    // payment types
    const PAYMENT_CASH = 1;
    const PAYMENT_BANK_TRANSFER = 3;
    const PAYMENT_OTHER = 5;
    const PAYMENT_CARD = 6;
    const PAYMENT_PREPAID = 8;

    // Contractor fields max lengths
    const MAX_CONTRACTOR_NAME = 40;
    const MAX_ADDRESS_LENGTH = 40;
    const MAX_ZIPCODE_LENGTH = 6;
    const MAX_CITY_LENGTH = 30;
    const MAX_VATIN_LENGTH = 15;

    // Other fields max lengths
    const MAX_DOCUMENT_LENGTH = 15;

    // type of document (KOREKTA field)
    const CORRECTION_INVOICE = 1;
    const NORMAL_INVOICE = 0;

    // possible values for KASA field
    const BASED_ON_RECEIPT = 1;
    const NOT_BASED_ON_RECEIPT = 0;

    // possible values for FIN field
    const COMPANY_TYPE = 0;
    const PERSON_TYPE = 1;

    // possible values for EXPORT field
    const TRANSACTION_IN_COUNTRY = 0;
    const TRANSACTION_EXPORT = 1;
    const TRANSACTION_EXPORT_RETURN = 2;
    const TRANSACTION_UE = 3;

    // possible values for ROZLICZONO field
    const STATUS_PAID = 0;
    const STATUS_NOT_PAID = 1;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * Set invoice.
     *
     * @param Invoice $invoice
     */
    public function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get document number.
     *
     * @return string
     */
    public function getDocumentNumber()
    {
        return $this->sanitizeAndCut($this->invoice->number, static::MAX_DOCUMENT_LENGTH);
    }

    /**
     * Get contractor name split into 2 chunks.
     *
     * @return array
     */
    public function getContractorName()
    {
        $name = mb_str_split(
            $this->sanitizeAndCut($this->getInvoiceContractor()->name),
            static::MAX_CONTRACTOR_NAME
        );

        if (! isset($name[1])) {
            $name[1] = '';
        }

        return [$name[0], $name[1]];
    }

    /**
     * Get contractor address.
     *
     * @return string
     */
    public function getContractorAddress()
    {
        $address = $this->sanitizeAndCut($this->getInvoiceContractor()->main_address_street);
        $street = $this->sanitizeAndCut($this->getInvoiceContractor()->main_address_number);

        return $this->cut($address . ' ' . $street, static::MAX_ADDRESS_LENGTH);
    }

    /**
     * Get contractor zip code.
     *
     * @return string
     */
    public function getContractorZipCode()
    {
        return $this->sanitizeAndCut(
            $this->getInvoiceContractor()->main_address_zip_code,
            static::MAX_ZIPCODE_LENGTH
        );
    }

    /**
     * Get contractor city.
     *
     * @return string
     */
    public function getContractorCity()
    {
        return $this->sanitizeAndCut(
            $this->getInvoiceContractor()->main_address_city,
            static::MAX_CITY_LENGTH
        );
    }

    /**
     * Get contractor Vatin number.
     *
     * @return string
     */
    public function getContractorVatin()
    {
        return $this->sanitizeAndCut(
            $this->getInvoiceContractor()->full_vatin,
            static::MAX_VATIN_LENGTH
        );
    }

    /**
     * Get contractor type.
     *
     * @return int
     */
    public function getContractorType()
    {
        return $this->getInvoiceContractor()->full_vatin
            ? static::COMPANY_TYPE
            : static::PERSON_TYPE;
    }

    /**
     * Get number of corrected invoice.
     *
     * @return string
     */
    public function getCorrectedDocumentNumber()
    {
        return ($corrected_invoice = $this->invoice->correctedInvoice) ?
            $this->sanitizeAndCut($corrected_invoice->number, static::MAX_DOCUMENT_LENGTH)
            : '';
    }

    /**
     * Get document type.
     *
     * @return int
     */
    public function getDocumentType()
    {
        return $this->invoice->correctedInvoice
            ? static::CORRECTION_INVOICE
            : static::NORMAL_INVOICE;
    }

    /**
     * Get receipt status.
     *
     * @return int
     */
    public function getReceiptStatus()
    {
        return $this->invoice->receipts()->count()
            ? static::BASED_ON_RECEIPT
            : static::NOT_BASED_ON_RECEIPT;
    }

    /**
     * Get export type.
     *
     * @return string
     */
    public function getExportType()
    {
        /** @var CountryVatinPrefix $vatin_prefix */
        $vatin_prefix = $this->getInvoiceContractor()->vatinPrefix;
        if (empty($vatin_prefix) || $vatin_prefix->isPoland()) {
            return static::TRANSACTION_IN_COUNTRY;
        }

        if ($vatin_prefix->inEuropeUnion()) {
            return static::TRANSACTION_UE;
        }

        if ($this->invoice->correctedInvoice) {
            return static::TRANSACTION_EXPORT_RETURN;
        }

        return static::TRANSACTION_EXPORT;
    }

    /**
     * Get paid status.
     *
     * @return int
     */
    public function getPaidStatus()
    {
        return $this->invoice->isPaid() ? static::STATUS_PAID : static::STATUS_NOT_PAID;
    }

    /**
     * Get payment method.
     *
     * @return int
     * @throws Exception
     */
    public function getPaymentMethod()
    {
        $payment_method_slug = $this->invoice->paymentMethod->slug;

        switch ($payment_method_slug) {
            case PaymentMethodType::CASH:
                return static::PAYMENT_CASH;
            case PaymentMethodType::BANK_TRANSFER:
                return static::PAYMENT_BANK_TRANSFER;
            case PaymentMethodType::DEBIT_CARD:
                return static::PAYMENT_CARD;
            case PaymentMethodType::PREPAID:
                return static::PAYMENT_PREPAID;
            case PaymentMethodType::OTHER:
                return static::PAYMENT_OTHER;
            case PaymentMethodType::CASH_CARD:
                return static::PAYMENT_OTHER;
            case PaymentMethodType::CASH_ON_DELIVERY:
                return static::PAYMENT_OTHER;
            case PaymentMethodType::PAYU:
                return static::PAYMENT_OTHER;
        }

        throw new Exception('Unknown payment type');
    }

    /**
     * Calculate deduction value.
     *
     * @param int $export_value
     *
     * @return int
     */
    public function getDeductionValue($export_value)
    {
        $value = 1;

        if (in_array($export_value, [static::TRANSACTION_EXPORT, static::TRANSACTION_UE])) {
            $value = $value | 32;
        }

        if ($this->getInvoiceContractor()->vatinPrefix && $this->getInvoiceContractor()->vatinPrefix->inEuropeUnion() &&
            ! $this->getInvoiceContractor()->vatinPrefix->isPoland()) {
            $value = $value | 64;
        }

        if (! $this->invoice->invoiceType->isReverseChargeType()) {
            return $value;
        }

        if ($export_value == static::TRANSACTION_IN_COUNTRY &&
            $this->invoice->invoiceReverseCharge->hasSlug(InvoiceReverseChargeType::CUSTOMER_TAX)) {
            $value = $value | 128;
        }

        if ($export_value == static::TRANSACTION_UE &&
            $this->invoice->invoiceReverseCharge->hasSlug(InvoiceReverseChargeType::IN_EU_TRIPLE)) {
            $value = $value | 128;
        }

        return $value;
    }

    /**
     * Get invoice contractor.
     *
     * @return InvoiceContractor
     */
    protected function getInvoiceContractor()
    {
        return $this->invoice->invoiceContractor;
    }
}
