<?php

namespace App\Modules\Company\Services\PayU;

class OrderByCardFirstParams extends OrderSimplyParams
{
    /**
     * OrderRecurringFirstRequestParams constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->data['payMethods'] = null;
        $this->data['recurring'] = 'FIRST';
    }

    /**
     * Set card data.
     *
     * @param $card_number
     * @param $card_exp_month
     * @param $card_exp_year
     * @param $card_cvv
     */
    public function setCard($card_number, $card_exp_month, $card_exp_year, $card_cvv)
    {
        if ($card_number && $card_exp_month && $card_exp_year && $card_cvv) {
            $this->data['payMethods'] = [
                'payMethod' => [
                    'card' => [
                        'number' => $card_number,
                        'expirationMonth' => $card_exp_month,
                        'expirationYear' => $card_exp_year,
                        'cvv' => $card_cvv,
                    ],
                ],
            ];
        }
    }
}
