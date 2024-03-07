<?php

namespace App\Modules\SaleReport\Services\Optima;

trait StringHelper
{
    /**
     * Sanitize, cut with max length and add quotes.
     *
     * @param string $string
     *
     * @return string
     */
    protected function formatString($string)
    {
        return '"' . $this->sanitizeAndCut($string, $this->getMaxStringLength()) . '"';
    }

    /**
     * Sanitize string and cut it after $length characters.
     *
     * @param string $string
     * @param int $length
     *
     * @return string
     */
    protected function sanitizeAndCut($string, $length = null)
    {
        $length = $length ?: $this->getMaxStringLength();

        return trim($this->cut(trim($this->sanitize($string)), $length));
    }

    /**
     * Take only $length first characters from string.
     *
     * @param string $string
     * @param int $length
     *
     * @return string
     */
    protected function cut($string, $length)
    {
        return mb_substr($string, 0, $length);
    }

    /**
     * Remove characters that are not allowed.
     *
     * @param $string
     *
     * @return string
     */
    protected function sanitize($string)
    {
        return str_replace(['"', ','], ' ', $string);
    }

    /**
     * Get maximum string length.
     *
     * @return int
     */
    protected function getMaxStringLength()
    {
        return 255;
    }
}
