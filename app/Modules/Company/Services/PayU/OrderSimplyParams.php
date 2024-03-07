<?php

namespace App\Modules\Company\Services\PayU;

use App\Models\Db\User;
use OpenPayU_Configuration;

class OrderSimplyParams
{
    /**
     * Currency.
     */
    const CURRENCY_PLN = 'PLN';
    const CURRENCY_EUR = 'EUR';
    protected $data;

    /**
     * OrderSimplyRequestParams constructor.
     */
    public function __construct()
    {
        $this->data = [
            'continueUrl' => config('payu.back_url'),
            'notifyUrl' => '',
            'customerIp' => request()->ip(),
            'description' => null,
            'currencyCode' => null,
            'totalAmount' => null,
            'extOrderId' => null,
            'settings' => ['invoiceDisabled' => true],
            'products' => null,
            'buyer' => null,
        ];
    }

    /**
     * set total amount.
     *
     * @param $amount
     * @param $currency
     */
    public function setTotalAmount($amount, $currency)
    {
        $this->data['currencyCode'] = $currency;
        $this->data['totalAmount'] = $amount;
        $this->data['notifyUrl'] = config('payu.' . mb_strtolower($currency) . '.notify_url');
    }

    /**
     * set internal order id.
     *
     * @param $order_id
     */
    public function setOrderId($order_id)
    {
        $this->data['extOrderId'] = $order_id;
        $this->data['description'] = config('app.name') . ' - order ' . $order_id;
    }

    /**
     * set products.
     *
     * @param array $products
     */
    public function setProducts(array $products)
    {
        if (count($products)) {
            $this->data['products'] = [];

            foreach ($products as $product) {
                $this->data['products'] [] = [
                    'name' => $product->name,
                    'unitPrice' => $product->price,
                    'quantity' => 1,
                ];
            }
        }
    }

    /**
     * set buyer.
     *
     * @param User $user
     */
    public function setBuyer(User $user)
    {
        $this->data['buyer'] = [
            'extCustomerId' => $user->id,
            'email' => $user->email,
        ];
    }

    /**
     * get currency.
     *
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->data['currencyCode'];
    }

    /**
     * getData.
     *
     * @return array
     * @throws \Exception
     */
    public function get()
    {
        foreach ($this->data as $value) {
            if (! $value) {
                throw new \Exception('One or more of required values is missing.');
            }
        }

        $this->data['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();

        return $this->data;
    }
}
