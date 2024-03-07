<?php

namespace App\Modules\Company\Services\Gus\API;

use App\Modules\Company\Exceptions\UnKnownNip;
use GusApi\Exception\InvalidUserKeyException;
use GusApi\GusApi;
use GusApi\RegonConstantsInterface;
use GusApi\SearchReport;

class ClientGus
{
    protected $login;

    /**
     * @var GusApi
     */
    protected $gus;

    /**
     * @var string
     */
    protected $user_key;

    /**
     * @var ClientGus
     */
    protected static $instance;

    /**
     * Gus constructor.
     */
    private function __construct()
    {
        $this->user_key = config('services.gus_api_user_key');
        if (empty($this->user_key)) {
            throw new InvalidUserKeyException();
        }

        $this->gus = new GusApi(
            $this->user_key,
            new \GusApi\Adapter\Soap\SoapAdapter(
                RegonConstantsInterface::BASE_WSDL_URL,
                RegonConstantsInterface::BASE_WSDL_ADDRESS
            )
        );
    }

    /**
     * Singleton Instance.
     *
     * @return ClientGus
     */
    public static function GetInstance()
    {
        if (self::$instance) {
            return self::$instance;
        }

        return new self();
    }

    /**
     * Login to Gus Service.
     *
     * @return bool
     */
    public function open()
    {
        // set timeout time for connection. May not work on Linux kernel.
        ini_set('default_socket_timeout', 10);

        if ($this->gus->serviceStatus() !== RegonConstantsInterface::SERVICE_AVAILABLE) {
            return false;
        }

        try {
            if (empty($this->login) || ! $this->gus->isLogged($this->login)) {
                $this->login = $this->gus->login();
            }
        } catch (InvalidUserKeyException $e) {
            return false;
        }

        return true;
    }

    /**
     * Find Company by Nip in Gus Service.
     *
     * @param $vatin
     * @return array
     * @throws UnKnownNip
     */
    public function getByNip($vatin)
    {
        if (empty($vatin)) {
            throw new UnKnownNip();
        }

        try {
            return $gus_reports = $this->gus->getByNip($this->login, $vatin);
        } catch (\GusApi\Exception\NotFoundException $e) {
            return [];
        }
    }

    /**
     * Get full report from GUS.
     *
     * @param $report_type
     *
     * @return mixed
     */
    public function getFullReport(SearchReport $search_report, $report_type)
    {
        try {
            return $this->gus->getFullReport($this->login, $search_report, $report_type)->dane;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
