<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Db\Company as CompanyModel;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;

class CompanyIdentification
{
    use ElementAdder;

    /**
     * Create company identification block.
     *
     * @param CompanyModel $company
     *
     * @return Element
     */
    public function create(CompanyModel $company)
    {
        $this->setParentElement(new Element('tns:IdentyfikatorPodmiotu'));
        $this->addVatin($company);
        $this->addName($company);
        $this->addRegon($company);

        return $this->getParentElement();
    }

    /**
     * Add vatin number.
     *
     * @param CompanyModel $company
     */
    protected function addVatin(CompanyModel $company)
    {
        $this->addChildElement(new Element('etd:NIP', $company->vatin));
    }

    /**
     * Add company name.
     *
     * @param CompanyModel $company
     */
    protected function addName(CompanyModel $company)
    {
        $this->addChildElement(new Element('etd:PelnaNazwa', $company->name));
    }

    /**
     * Add REGON.
     *
     * @param CompanyModel $company
     */
    protected function addRegon(CompanyModel $company)
    {
        if ($company->jpkDetail->regon) {
            $this->addChildElement(new Element('etd:REGON', $company->jpkDetail->regon));
        }
    }
}
