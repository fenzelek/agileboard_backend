<?php

namespace App\Modules\SaleInvoice\Services\Factory;

use App\Interfaces\BuilderCreateInvoice;

abstract class Method
{
    /**
     * @param int $type_id
     *
     * @return BuilderCreateInvoice
     */
    public function create(int $type_id): BuilderCreateInvoice
    {
        return $this->createBuilder($type_id);
    }

    /**
     * Create new instance of CreateInvoice Builders.
     *
     * @param int $type_id
     *
     * @return BuilderCreateInvoice
     */
    abstract protected function createBuilder(int $type_id): BuilderCreateInvoice;
}
