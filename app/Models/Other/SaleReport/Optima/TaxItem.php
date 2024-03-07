<?php

namespace App\Models\Other\SaleReport\Optima;

class TaxItem
{
    /**
     * @var int
     */
    private $tax_rate;

    /**
     * @var int
     */
    private $net_price;

    /**
     * @var int
     */
    private $vat;

    /**
     * @var string
     */
    private $type;

    /**
     * TaxItem constructor.
     *
     * @param int $tax_rate
     * @param int $net_price
     * @param int $vat
     * @param string $type
     */
    public function __construct($tax_rate, $net_price, $vat, $type)
    {
        $this->tax_rate = $tax_rate;
        $this->net_price = $net_price;
        $this->vat = $vat;
        $this->type = $type;
    }

    /**
     * Get tax rate.
     *
     * @return int
     */
    public function getTaxRate()
    {
        return $this->tax_rate;
    }

    /**
     * Get net price.
     *
     * @return int
     */
    public function getNetPrice()
    {
        return $this->net_price;
    }

    /**
     * Get vat tax.
     *
     * @return int
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
