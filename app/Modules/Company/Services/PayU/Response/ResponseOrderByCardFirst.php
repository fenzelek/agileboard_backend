<?php

namespace App\Modules\Company\Services\PayU\Response;

class ResponseOrderByCardFirst extends Response
{
    public function getOrderId()
    {
        if ($this->isSuccess()
            || $this->getError() == $this::WARNING_CONTINUE_3DS
            || $this->getError() == $this::WARNING_CONTINUE_CVV
        ) {
            return $this->response->getResponse()->orderId;
        }

        return null;
    }

    public function getToken()
    {
        if ($this->isSuccess() || $this->getError() == $this::WARNING_CONTINUE_3DS) {
            return $this->response->getResponse()->payMethods->payMethod->value;
        }

        return null;
    }

    public function getRedirectUrl()
    {
        if ($this->getError() == $this::WARNING_CONTINUE_3DS) {
            return $this->response->getResponse()->redirectUri;
        }

        return null;
    }
}
