<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Db\Company as CompanyModel;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;

class CompanyAddress
{
    use ElementAdder;

    const POLAND = 'PL';

    /**
     * @var CompanyModel
     */
    protected $company;

    /**
     * Create company address block.
     *
     * @param CompanyModel $company
     *
     * @return Element
     */
    public function create(CompanyModel $company)
    {
        $this->setParentElement(new Element('tns:AdresPodmiotu'));
        $this->company = $company;

        $this->addCountryCode();
        $this->addState();
        $this->addCounty();
        $this->addCommunity();
        $this->addStreet();
        $this->addBuildingNumber();
        $this->addFlatNumber();
        $this->addCity();
        $this->addZipCode();
        $this->addPostalCity();

        return $this->getParentElement();
    }

    /**
     * Add country code.
     */
    protected function addCountryCode()
    {
        $this->addChildElement(new Element('etd:KodKraju', static::POLAND));
    }

    /**
     * Add state code.
     */
    protected function addState()
    {
        $this->addChildElement(new Element('etd:Wojewodztwo', $this->company->jpkDetail->state));
    }

    /**
     * Add county code.
     */
    protected function addCounty()
    {
        $this->addChildElement(new Element('etd:Powiat', $this->company->jpkDetail->county));
    }

    /**
     * Add community code.
     */
    protected function addCommunity()
    {
        $this->addChildElement(new Element('etd:Gmina', $this->company->jpkDetail->community));
    }

    /**
     * Add street.
     */
    protected function addStreet()
    {
        $this->addChildElement(new Element('etd:Ulica', $this->company->jpkDetail->street));
    }

    /**
     * Add building number.
     */
    protected function addBuildingNumber()
    {
        if ($this->company->jpkDetail->building_number) {
            $this->addChildElement(new Element(
                'etd:NrDomu',
                $this->company->jpkDetail->building_number
            ));
        }
    }

    /**
     * Add flat number.
     */
    protected function addFlatNumber()
    {
        if ($this->company->jpkDetail->flat_number) {
            $this->addChildElement(new Element(
                'etd:NrLokalu',
                $this->company->jpkDetail->flat_number
            ));
        }
    }

    /**
     * Add city.
     */
    protected function addCity()
    {
        $this->addChildElement(new Element('etd:Miejscowosc', $this->company->jpkDetail->city));
    }

    /**
     * Add zip code.
     */
    protected function addZipCode()
    {
        $this->addChildElement(new Element(
            'etd:KodPocztowy',
            $this->company->jpkDetail->zip_code
        ));
    }

    /**
     * Add postal city.
     */
    protected function addPostalCity()
    {
        $this->addChildElement(new Element('etd:Poczta', $this->company->jpkDetail->postal));
    }
}
