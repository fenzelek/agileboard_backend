<?php

namespace App\Modules\Company\Services\PayU\Response;

class ResponseOrderSimply extends Response
{
    public function getOrderId()
    {
        if ($this->isSuccess()) {
            return $this->response->getResponse()->orderId;
        }

        return null;
    }

    public function getToken()
    {
        return null;
    }

    public function getRedirectUrl()
    {
        if ($this->isSuccess()) {
            return $this->response->getResponse()->redirectUri;
        }

        return null;
    }
}
