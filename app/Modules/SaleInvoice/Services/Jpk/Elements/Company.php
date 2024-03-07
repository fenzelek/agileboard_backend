<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Db\Company as CompanyModel;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;

class Company
{
    use ElementAdder;

    /**
     * @var CompanyIdentification
     */
    protected $id_creator;

    /**
     * @var CompanyAddress
     */
    protected $address_creator;

    /**
     * Company constructor.
     *
     * @param CompanyIdentification $id_creator
     * @param CompanyAddress $address_creator
     */
    public function __construct(
        CompanyIdentification $id_creator,
        CompanyAddress $address_creator
    ) {
        $this->id_creator = $id_creator;
        $this->address_creator = $address_creator;
    }

    /**
     * Create company block.
     *
     * @param CompanyModel $company
     *
     * @return Element|null
     */
    public function create(CompanyModel $company)
    {
        $this->setParentElement(new Element('tns:Podmiot1'));

        $this->createCompanyId($company);
        $this->createCompanyAddress($company);

        return $this->getParentElement();
    }

    /**
     * Add CompanyId sub-block.
     *
     * @param CompanyModel $company
     */
    protected function createCompanyId(CompanyModel $company)
    {
        $this->addChildElement($this->id_creator->create($company));
    }

    /**
     * Add CompanyAddress sub-block.
     *
     * @param CompanyModel $company
     */
    protected function createCompanyAddress(CompanyModel $company)
    {
        $this->addChildElement($this->address_creator->create($company));
    }
}
