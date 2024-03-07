<?php

namespace App\Modules\SaleInvoice\Traits;

trait PriceNormalize
{
    /**
     * Undo normalize amount from integer to decimal.
     *
     * @param $amount
     * @return float
     */
    public function undoNormalizeFloatAmount($amount)
    {
        return denormalize_price($amount);
    }

    /**
     * Undo normalize price net.
     *
     * @return float
     */
    public function undoNormalizePriceNet()
    {
        return $this->undoNormalizeFloatAmount($this->price_net);
    }

    /**
     * Undo normalize price net sum.
     *
     * @return float
     */
    public function undoNormalizePriceNetSum()
    {
        return $this->undoNormalizeFloatAmount($this->price_net_sum);
    }

    /**
     * Undo normalize price gross.
     *
     * @return float
     */
    public function undoNormalizePriceGross()
    {
        return $this->undoNormalizeFloatAmount($this->price_gross);
    }

    /**
     * Undo normalize price gross sum.
     *
     * @return float
     */
    public function undoNormalizePriceGrossSum()
    {
        return $this->undoNormalizeFloatAmount($this->price_gross_sum);
    }

    /**
     * Undo normalize vat sum.
     *
     * @return float
     */
    public function undoNormalizeVatSum()
    {
        return $this->undoNormalizeFloatAmount($this->vat_sum);
    }

    /**
     * Undo normalize amount.
     *
     * @return float
     */
    public function undoNormalizeAmount()
    {
        return $this->undoNormalizeFloatAmount($this->amount);
    }
}
