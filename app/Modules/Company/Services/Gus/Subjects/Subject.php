<?php

namespace App\Modules\Company\Services\Gus\Subjects;

use App\Modules\Company\Services\Gus\API\ClientGus;
use GusApi\SearchReport;

abstract class Subject
{
    const NODE_HOUSE_NUMBER = 'adSiedzNumerNieruchomosci';
    const NODE_LOCAL_NUMBER = 'adSiedzNumerLokalu';

    const NODE_NAME = 'nazwa';
    const NODE_COUNTRY = 'adSiedzKraj_Nazwa';
    const NODE_ZIP_CODE = 'adSiedzKodPocztowy';
    const NODE_CITY = 'adSiedzMiejscowosc_Nazwa';
    const NODE_STREET = 'adSiedzUlica_Nazwa';
    const NODE_PHONE = 'numerTelefonu';
    const NODE_EMAIL = 'adresEmail';
    const NODE_WEBSITE = 'adresStronyinternetowej';

    const TYPE = null;
    const PREFIX = null;

    const NODE_NIP = null;
    const NODE_REGON = null;

    const ACTIVE_NODE = '1';

    /**
     * @var SearchReport
     */
    protected $searchReport;
    protected $client_gus;
    protected $full_report;
    protected $response;

    public function __construct(SearchReport $searchReport)
    {
        $this->searchReport = $searchReport;
        $this->client_gus = ClientGus::GetInstance();
    }

    protected function getSubject()
    {
        $this->response = $this->assignValues();
        $this->response = $this->addAddressNumber();
        $this->response = $this->trimAndZipCodeFormat();
        $this->response['regon'] = $this->getFullReportNode(static::NODE_REGON);

        return $this->response;
    }

    /**
     * Trim data and format zip code.
     *
     *
     * @return array
     */
    protected function trimAndZipCodeFormat()
    {
        foreach ($this->response as $key => $item) {
            // Format zip code string
            if ($key == 'main_address_zip_code') {
                $this->response[$key] = substr_replace(trim((string) $item), '-', 2, 0);
                continue;
            }
            $this->response[$key] = trim((string) $item);
        }

        return $this->response;
    }

    /**
     *  Add address to response.
     *
     * @param $response
     * @param $full_report
     * @param $prefix
     *
     * return array
     */
    protected function addAddressNumber()
    {
        $this->response['main_address_number'] = trim((string) $this->full_report
            ->{static::PREFIX . self::NODE_HOUSE_NUMBER});
        if ((string) $this->full_report->{static::PREFIX . self::NODE_LOCAL_NUMBER}) {
            $this->response['main_address_number'] .= '/'
                . trim((string) $this->full_report->{static::PREFIX . self::NODE_LOCAL_NUMBER});
        }

        return $this->response;
    }

    /**
     * Assign values from full report to array.
     *
     * @return array
     */
    protected function assignValues()
    {
        $response = $this->getTemplateArray();
        $mappingArray = [
            'name' => self::NODE_NAME,
            'main_address_country' => self::NODE_COUNTRY,
            'main_address_zip_code' => self::NODE_ZIP_CODE,
            'main_address_city' => self::NODE_CITY,
            'main_address_street' => self::NODE_STREET,
            'phone' => self::NODE_PHONE,
            'email' => self::NODE_EMAIL,
            'website' => self::NODE_WEBSITE,
        ];

        foreach ($mappingArray as $key => $value) {
            $response[$key] = $this->full_report->{static::PREFIX . $value};
        }

        return $response;
    }

    /**
     * Generate template array.
     *
     * @return array
     */
    protected function getTemplateArray()
    {
        return array_fill_keys([
            'name',
            'vatin',
            'regon',
            'main_address_country',
            'main_address_zip_code',
            'main_address_city',
            'main_address_street',
            'main_address_number',
            'phone',
            'email',
            'website',
        ], '');
    }

    /**
     * Get full report node value.
     *
     * @param $node
     * @return string
     */
    protected function getFullReportNode($node)
    {
        return (string) $this->full_report->{$node};
    }
}
