<?php

namespace App\Modules\SaleReport\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ExternalExportProvider
{
    /**
     * Get file content.
     *
     * @param Collection $invoices
     *
     * @return string
     */
    public function getFileContent(Collection $invoices);

    /**
     * Get file name.
     *
     * @return string
     */
    public function getFileName();

    /**
     * Get file content type.
     *
     * @return string
     */
    public function getFileContentType();
}
