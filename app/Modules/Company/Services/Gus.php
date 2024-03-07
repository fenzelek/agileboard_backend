<?php

namespace App\Modules\Company\Services;

use App\Models\Db\GusCompany;
use App\Modules\Company\Exceptions\UnKnownNip;
use App\Modules\Company\Exceptions\UnKnownSubject;
use App\Modules\Company\Services\Gus\API\ClientGus;
use App\Modules\Company\Services\Gus\Factories\CreateSubject;
use Illuminate\Support\Facades\DB;

class Gus
{
    protected $gus;

    /**
     * @var GusCompany
     */
    protected $gus_company;

    /**
     * Gus constructor.
     *
     * @param GusCompany $gus_company
     */
    public function __construct(GusCompany $gus_company)
    {
        $this->gus_company = $gus_company;
    }

    /**
     * Returns company data from DB or GUS.
     *
     * @param $vatin
     *
     * @return array|bool
     */
    public function getDataByVatin($vatin)
    {
        $vatin = str_replace('-', '', $vatin);

        $data = $this->pullDataFromServer($vatin);
        if ($data == false) {
            $company_items = $this->gus_company->findByVatin($vatin);
            if ($company_items->isNotEmpty()) {
                return $company_items;
            }
        }

        try {
            DB::transaction(function () use ($vatin, $data) {
                $this->gus_company->findAndDestroy($vatin);
                $this->saveDataToDB($data);
            });
        } catch (\Exception $exception) {
            return $data;
        }

        return $data;
    }

    /**
     * Get company data from GUS servers.
     *
     * @param $vatin
     *
     * @return array|bool
     */
    protected function pullDataFromServer($vatin)
    {
        try {
            $this->gus = ClientGus::GetInstance();

            if ($this->gus->open()) {
                try {
                    $gus_reports = $this->gus->getByNip($vatin);

                    return $this->prepareResponse($gus_reports);
                } catch (UnKnownNip $e) {
                    return false;
                }
            }
        } catch (\SoapFault $e) {
            return false;
        }

        return false;
    }

    /**
     * Get detailed data adn prepare response.
     *
     * @param $gus_reports
     *
     * @return array
     */
    protected function prepareResponse($gus_reports)
    {
        $response = [];

        foreach ($gus_reports as $report) {
            try {
                $subject = (new CreateSubject($report))->getSubject();
                if ($subject->isValid()) {
                    $response[] = $subject->getSubject();
                }
            } catch (UnKnownSubject $exception) {
                //
            }
        }

        return $response;
    }

    /**
     * Saving $data to DB.
     *
     * @param $data
     */
    protected function saveDataToDB($data)
    {
        foreach ($data as $item) {
            $this->gus_company->create((array) $item);
        }
    }
}
