<?php

namespace App\Modules\Company\Services\PayU;

use App\Models\Db\Payment;
use App\Models\Db\User;

class ParamsFactory
{
    /**
     * @param $inputs
     * @param User $user
     * @param Payment $payment
     * @return OrderByCardFirstParams|OrderByCardNextParams|OrderSimplyParams|null
     */
    public function createOrderParams($inputs, User $user, Payment $payment)
    {
        $params = null;
        if ($inputs['type'] == $payment::TYPE_SIMPLE) {
            $params = new OrderSimplyParams();
        } elseif (isset($inputs['token']) && $inputs['token']) {
            $params = new OrderByCardNextParams();
            $params->setToken($inputs['token']);
        } else {
            if (isset($inputs['card_number']) && $inputs['card_number']) {
                $params = new OrderByCardFirstParams();
                $params->setCard(
                    $inputs['card_number'],
                    $inputs['card_exp_month'],
                    $inputs['card_exp_year'],
                    $inputs['card_cvv']
                );
            }
        }

        $params->setBuyer($user);
        $params->setOrderId($payment->id);
        $params->setTotalAmount($payment->price_total, $payment->currency);

        $product = (object) [
                'name' => $payment->transaction->companyModulesHistory[0]->package_id ? 'Package' : 'Module',
                'price' => $payment->price_total,
            ];
        $params->setProducts([$product]);

        return $params;
    }
}
