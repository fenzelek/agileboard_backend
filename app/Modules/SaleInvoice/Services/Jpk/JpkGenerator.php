<?php

namespace App\Modules\SaleInvoice\Services\Jpk;

use App\Models\Db\Company;
use Illuminate\Database\Eloquent\Collection;

class JpkGenerator
{
    /**
     * @var string|null
     */
    protected $start_date;

    /**
     * @var string|null
     */
    protected $end_date;

    /**
     * @var JpkBuilder
     */
    protected $builder;

    /**
     * JpkGenerator constructor.
     *
     * @param JpkBuilder $builder
     */
    public function __construct(JpkBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Set start date.
     *
     * @param string|null $start_date
     *
     * @return $this
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;

        return $this;
    }

    /**
     * Set end date.
     *
     * @param string|null $end_date
     *
     * @return $this
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;

        return $this;
    }

    /**
     * Get file content.
     *
     * @param Company $company
     * @param Collection $invoices
     *
     * @return string
     */
    public function getFileContent(Company $company, Collection $invoices)
    {
        return $this->builder->create($company, $invoices, $this->start_date, $this->end_date);
    }

    /**
     * Get file content type.
     *
     * @return string
     */
    public function getFileContentType()
    {
        return 'text/xml';
    }

    /**
     * Get file name.
     *
     * @return string
     */
    public function getFileName()
    {
        return 'Jpk_FA_' . $this->start_date . '_' . $this->end_date . '.xml';
    }
}
