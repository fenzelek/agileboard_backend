<?php

namespace App\Modules\Company\Services\PayU;

class OrderByCardNextParams extends OrderSimplyParams
{
    /**
     * OrderRecurringNextRequestParams constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->data['payMethods'] = null;
        $this->data['recurring'] = 'STANDARD';
    }

    /**
     * Set token.
     *
     * @param $token
     */
    public function setToken($token)
    {
        if ($token) {
            $this->data['payMethods'] = [
                'payMethod' => [
                    'value' => $token,
                    'type' => 'CARD_TOKEN',
                ],
            ];
        }
    }
}
