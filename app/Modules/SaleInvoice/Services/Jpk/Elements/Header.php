<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Db\Company as CompanyModel;
use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;
use Illuminate\Support\Carbon;

class Header
{
    use ElementAdder;

    /**
     * Document type.
     */
    const DOCUMENT_TYPE = 'JPK_FA';

    /**
     * System code.
     */
    const SYSTEM_CODE = 'JPK_FA (1)';

    /**
     * Schema version.
     */
    const SCHEMA_VERSION = '1-0';

    /**
     * Form variant.
     */
    const VARIANT = 1;

    /**
     * JPK created for first time.
     */
    const FIRST_TIME = 1;

    /**
     * Polish currency value.
     */
    const PLN_CURRENCY = 'PLN';

    /**
     * Create new header.
     *
     * @param CompanyModel $company
     * @param Carbon $start_date
     * @param Carbon $end_date
     *
     * @return Element|null
     */
    public function create(CompanyModel $company, Carbon $start_date, Carbon $end_date)
    {
        $this->setParentElement(new Element('tns:Naglowek'));
        $this->addFormCode();
        $this->addVariant();
        $this->addGoal();
        $this->addCreationDate();
        $this->addDateFrom($start_date);
        $this->addDateTo($end_date);
        $this->addDefaultCurrencyCode();
        $this->addTaxOfficeCode($company);

        return $this->getParentElement();
    }

    /**
     * Add form code.
     */
    protected function addFormCode()
    {
        $form_code = new Element('tns:KodFormularza', static::DOCUMENT_TYPE);
        $form_code->addAttribute(new Attribute('kodSystemowy', static::SYSTEM_CODE));
        $form_code->addAttribute(new Attribute('wersjaSchemy', static::SCHEMA_VERSION));

        $this->addChildElement($form_code);
    }

    /**
     * Add form variant.
     */
    protected function addVariant()
    {
        $this->addChildElement(new Element('tns:WariantFormularza', static::VARIANT));
    }

    /**
     * Add goal of creating document.
     */
    protected function addGoal()
    {
        $this->addChildElement(new Element('tns:CelZlozenia', static::FIRST_TIME));
    }

    /**
     * Add creation date.
     */
    protected function addCreationDate()
    {
        $date = Carbon::now();
        $this->addChildElement(new Element(
            'tns:DataWytworzeniaJPK',
            $date->toDateString() . 'T' . $date->toTimeString()
        ));
    }

    /**
     * Add date from of selected invoices.
     *
     * @param Carbon $start_date
     */
    protected function addDateFrom(Carbon $start_date)
    {
        $this->addChildElement(new Element('tns:DataOd', $start_date->toDateString()));
    }

    /**
     * Add date to of selected invoices.
     *
     * @param Carbon $end_date
     */
    protected function addDateTo(Carbon $end_date)
    {
        $this->addChildElement(new Element('tns:DataDo', $end_date->toDateString()));
    }

    /**
     * Add default currency code.
     */
    protected function addDefaultCurrencyCode()
    {
        $this->addChildElement(new Element('tns:DomyslnyKodWaluty', static::PLN_CURRENCY));
    }

    /**
     * Add tax office of given company.
     *
     * @param CompanyModel $company
     */
    protected function addTaxOfficeCode(CompanyModel $company)
    {
        $this->addChildElement(new Element('tns:KodUrzedu', $company->jpkDetail->taxOffice->code));
    }
}
