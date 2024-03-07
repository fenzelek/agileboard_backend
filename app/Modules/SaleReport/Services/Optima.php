<?php

namespace App\Modules\SaleReport\Services;

use App\Modules\SaleReport\Services\Contracts\ExternalExportProvider;
use App\Modules\SaleReport\Services\Optima\InvoiceFiller;
use App\Modules\SaleReport\Services\Optima\StringHelper;
use App\Modules\SaleReport\Services\Optima\MazoviaConverter;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Log;

class Optima implements ExternalExportProvider
{
    use StringHelper;

    /**
     * Separator for rows.
     */
    const ROW_SEPARATOR = "\r\n";

    /**
     * Separator for columns.
     */
    const COLUMN_SEPARATOR = ',';

    /**
     * @var InvoiceFiller
     */
    protected $invoice_filler;

    /**
     * @var MazoviaConverter
     */
    protected $mazovia;

    /**
     * Optima constructor.
     *
     * @param InvoiceFiller $invoice_filler
     * @param MazoviaConverter $mazovia
     */
    public function __construct(
        InvoiceFiller $invoice_filler,
        MazoviaConverter $mazovia
    ) {
        $this->invoice_filler = $invoice_filler;
        $this->mazovia = $mazovia;
    }

    /**
     * @inheritdoc
     */
    public function getFileContent(Collection $invoices)
    {
        $this->loadRelationships($invoices);

        return $this->mazovia->fromUtf8($this->parseData($invoices));
    }

    /**
     * @inheritdoc
     */
    public function getFileName()
    {
        return 'VAT_R.TXT';
    }

    /**
     * @inheritdoc
     */
    public function getFileContentType()
    {
        return 'text/plain';
    }

    /**
     * Parse data to specific structure.
     *
     * @param Collection $invoices
     *
     * @return string
     * @throws \Exception
     */
    protected function parseData(Collection $invoices)
    {
        $lines = $invoices->map(function ($invoice) {
            return $this->invoice_filler->getFields($invoice);
        })->all();

        return $this->buildCsv($lines);
    }

    /**
     * Build Csv from given lines.
     *
     * @param array $lines
     *
     * @return string
     */
    protected function buildCsv(array $lines)
    {
        $formatted_lines = array_map(function ($row) {
            return implode(static::COLUMN_SEPARATOR, $this->formatStrings($row));
        }, $lines);

        return implode(static::ROW_SEPARATOR, $formatted_lines);
    }

    /**
     * Convert string columns into required format.
     *
     * @param array $row
     *
     * @return array
     */
    protected function formatStrings(array $row)
    {
        return array_map(function ($column) {
            return (is_string($column) && ! is_numeric($column)) ?
                $this->formatString($column) : $column;
        }, $row);
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
                'paymentMethod',
                'invoiceType',
                'taxes.vatRate',
                'invoiceContractor.vatinPrefix',
                'correctedInvoice',
                'receipts'
            );
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
