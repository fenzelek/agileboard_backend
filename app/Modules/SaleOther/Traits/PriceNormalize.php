<?php

namespace App\Modules\SaleOther\Traits;

trait PriceNormalize
{
    /**
     * Undo normalize amount from integer to decimal.
     *
     * @param $amount
     * @return float
     */
    public function undoNormalizeAmount($amount)
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
        return $this->undoNormalizeAmount($this->price_net);
    }

    /**
     * Undo normalize price net sum.
     *
     * @return float
     */
    public function undoNormalizePriceNetSum()
    {
        return $this->undoNormalizeAmount($this->price_net_sum);
    }

    /**
     * Undo normalize price gross.
     *
     * @return float
     */
    public function undoNormalizePriceGross()
    {
        return $this->undoNormalizeAmount($this->price_gross);
    }

    /**
     * Undo normalize price gross sum.
     *
     * @return float
     */
    public function undoNormalizePriceGrossSum()
    {
        return $this->undoNormalizeAmount($this->price_gross_sum);
    }

    /**
     * Undo normalize vat sum.
     *
     * @return float
     */
    public function undoNormalizeVatSum()
    {
        return $this->undoNormalizeAmount($this->vat_sum);
    }

    /**
     * Undo normalize payment left.
     *
     * @return float
     */
    public function undoNormalizePaymentLeft()
    {
        if (empty($this->payment_left)) {
            return;
        }

        return $this->undoNormalizeAmount($this->payment_left);
    }
}
