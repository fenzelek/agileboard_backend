<?php

namespace App\Modules\Company\Services\PayU\Response;

abstract class Response
{
    const SUCCESS = 'SUCCESS';
    const WARNING_CONTINUE_3DS = 'WARNING_CONTINUE_3DS';
    const WARNING_CONTINUE_CVV = 'WARNING_CONTINUE_CVV';
    protected $response;

    /**
     * Response constructor.
     * @param $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    public function isSuccess()
    {
        return $this->response->getStatus() == $this::SUCCESS;
    }

    public function getError()
    {
        if (! $this->isSuccess()) {
            return $this->response->getStatus();
        }

        return false;
    }

    public function getData()
    {
        return $this->response->getResponse();
    }

    abstract public function getOrderId();

    abstract public function getToken();

    abstract public function getRedirectUrl();
}
