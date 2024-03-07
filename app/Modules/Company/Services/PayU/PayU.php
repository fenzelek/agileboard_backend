<?php

namespace App\Modules\Company\Services\PayU;

use App\Models\Db\User;
use App\Modules\Company\Services\PayU\Response\ResponseOrderByCardFirst;
use App\Modules\Company\Services\PayU\Response\ResponseOrderByCardNext;
use App\Modules\Company\Services\PayU\Response\ResponseOrderSimply;
use OauthGrantType;
use OpenPayU_Configuration;
use OauthCacheFile;
use OpenPayU_Order;
use OpenPayU_Exception;
use OpenPayU_Retrieve;
use OpenPayU_Token;

class PayU
{
    /**
     * @var string
     */
    private $grandType = OauthGrantType::CLIENT_CREDENTIAL;
    private $params;
    private $user;

    /**
     * @param OrderSimplyParams $params
     */
    public function setParams(OrderSimplyParams $params)
    {
        $this->params = $params;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * CreateOrder.
     *
     * @param ResponseSaver $responseSaver
     * @return ResponseOrderByCardFirst|ResponseOrderByCardNext|ResponseOrderSimply|bool
     * @throws \Exception
     */
    public function createOrder(ResponseSaver $responseSaver)
    {
        if ($this->params instanceof OrderByCardFirstParams) {
            $response = $this->createOrderByCardFirst($this->params);
        } elseif ($this->params instanceof OrderByCardNextParams) {
            $response = $this->createOrderByCardNext($this->params);
        } else {
            $response = $this->createOrderSimply($this->params);
        }

        $responseSaver->save($response);

        return $response;
    }

    /**
     * Retrieve notification.
     *
     * @param $currency
     * @return bool|object
     * @throws \OpenPayU_Exception_Configuration
     */
    public function getDataFromNotification($currency)
    {
        $this->connect($currency);

        $body = file_get_contents('php://input');
        $data = trim($body);

        try {
            if (! empty($data)) {
                $result = OpenPayU_Order::consumeNotification($data);
            }

            if ($result->getResponse()->order->orderId) {
                $response = OpenPayU_Order::retrieve($result->getResponse()->order->orderId);

                if ($response->getStatus() == 'SUCCESS') {
                    return (object) [
                        'order_id' => $response->getResponse()->orders[0]->orderId,
                        'status' => $response->getResponse()->orders[0]->status,
                        'data' => $response->getResponse(),
                    ];
                }

                return false;
            }
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }

    /**
     * Set order status as completed.
     *
     * @param $order_id
     * @param $currency
     * @return bool
     */
    public function setOrderCompleted($order_id, $currency)
    {
        try {
            $this->connect($currency);

            $response = OpenPayU_Order::statusUpdate([
                'orderId' => $order_id,
                'orderStatus' => self::STATUS_COMPLETED,
            ]);

            return $response->getStatus() == 'SUCCESS';
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }

    /**
     * Cancel order.
     *
     * @param $order_id
     * @param $currency
     * @return bool
     */
    public function cancel($order_id, $currency)
    {
        try {
            $this->connect($currency);

            $response = OpenPayU_Order::cancel($order_id);

            return $response->getStatus() == 'SUCCESS';
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }

    /**
     * Get card tokens.
     *
     * @param $language
     * @param $currency
     * @return array|bool
     */
    public function getCardTokens($language, $currency)
    {
        try {
            $this->connect($currency);
            $this->setGrandTypeTrustedMerchant();

            $response = OpenPayU_Retrieve::payMethods($language);

            if ($response->getStatus() == 'SUCCESS') {
                if (isset($response->getResponse()->cardTokens)) {
                    $cardTokens = $response->getResponse()->cardTokens;

                    return $cardTokens ?: [];
                }

                return [];
            }

            return false;
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }

    /**
     * Delete card token.
     *
     * @param $token
     * @param $currency
     * @return bool
     * @throws \Exception
     */
    public function deleteCardToken($token, $currency)
    {
        try {
            $this->connect($currency);
            $this->setGrandTypeTrustedMerchant();
            OpenPayU_Token::delete($token)->getResponse();

            return true;
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }

    /**
     * Connect to payu.
     *
     * @param $currency
     * @throws \OpenPayU_Exception_Configuration
     */
    private function connect($currency)
    {
        $currency = mb_strtolower($currency);

        //set Sandbox Environment
        OpenPayU_Configuration::setEnvironment(config('payu.sandbox') ? 'sandbox' : 'secure');

        //set POS ID and Second MD5 Key (from merchant admin panel)
        OpenPayU_Configuration::setMerchantPosId(config('payu.' . $currency . '.pos_id'));
        OpenPayU_Configuration::setSignatureKey(config('payu.' . $currency . '.md5'));

        //set Oauth Client Id and Oauth Client Secret (from merchant admin panel)
        OpenPayU_Configuration::setOauthClientId(config('payu.' . $currency . '.client_id'));
        OpenPayU_Configuration::setOauthClientSecret(config('payu.' . $currency . '.client_secret'));

        //path to cache
        OpenPayU_Configuration::setOauthTokenCache(new OauthCacheFile(storage_path('payu-cache')));
    }

    /**
     * set grand type trusted-merchant.
     *
     * @throws \OpenPayU_Exception_Configuration
     */
    private function setGrandTypeTrustedMerchant()
    {
        $this->grandType = OauthGrantType::TRUSTED_MERCHANT;

        //set Oauth Email and Oauth Ext Customer Id
        OpenPayU_Configuration::setOauthEmail($this->user->email);
        OpenPayU_Configuration::setOauthExtCustomerId($this->user->id);

        OpenPayU_Configuration::setOauthGrantType($this->grandType);
    }

    /**
     * Create simply order.
     *
     * @param OrderSimplyParams $params
     * @return ResponseOrderSimply|bool
     * @throws \Exception
     */
    private function createOrderSimply(OrderSimplyParams $params)
    {
        try {
            $this->connect($params->getCurrency());

            $response = OpenPayU_Order::create($params->get());

            return new ResponseOrderSimply($response);
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }

    /**
     * Create first order by card.
     *
     * @param OrderByCardFirstParams $params
     * @return ResponseOrderByCardFirst|bool
     * @throws \Exception
     */
    private function createOrderByCardFirst(OrderByCardFirstParams $params)
    {
        try {
            $this->connect($params->getCurrency());

            $this->setGrandTypeTrustedMerchant();
            $response = OpenPayU_Order::create($params->get());

            return new ResponseOrderByCardFirst($response);
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }

    /**
     * Create next order by card.
     *
     * @param OrderByCardNextParams $params
     * @return ResponseOrderByCardNext|bool
     * @throws \Exception
     */
    private function createOrderByCardNext(OrderByCardNextParams $params)
    {
        try {
            $this->connect($params->getCurrency());

            $this->setGrandTypeTrustedMerchant();
            $response = OpenPayU_Order::create($params->get());

            return new ResponseOrderByCardNext($response);
        } catch (OpenPayU_Exception $e) {
            return false;
        }
    }
}
