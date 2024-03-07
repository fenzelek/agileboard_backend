<?php

namespace Tests\Helpers;

use App\Models\Db\Company;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\ServiceUnit;
use App\Models\Db\VatRate;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Models\Db\Invoice as InvoiceModel;
use App\Models\Db\Company as CompanyModel;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType as InvoiceTypeHelper;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\MarginProcedure;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTaxes;
use App\Models\Db\InvoiceItem as InvoiceItemModel;
use Faker\Factory;
use Mockery;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem as HelperInvoiceItem;

trait Jpk
{
    /**
     * Find first child element with given name.
     *
     * @param Element $element
     * @param string $child_name
     *
     * @return Element|null
     */
    protected function findChildElement(Element $element, $child_name)
    {
        return array_first($element->getChildren(), function (Element $element) use ($child_name) {
            return $element->getName() == $child_name;
        });
    }

    /**
     * Find child element and verify whether it's correct.
     *
     * @param Element $element
     * @param string $child_name
     * @param mixed $child_expected_value
     */
    protected function findAndVerifyField(Element $element, $child_name, $child_expected_value)
    {
        $comparing_element = $this->findChildElement($element, $child_name);
        $this->assertTrue($comparing_element instanceof Element);

        $this->assertSame([
            'name' => $child_name,
            'value' => $child_expected_value,
            'attributes' => [],
            'children' => [],
        ], $comparing_element->toArray());
    }

    /**
     * Build Invoice service and set up default mock.
     *
     * @param InvoiceModel $invoice
     * @param CompanyModel|null $company
     * @param InvoiceTypeHelper $invoice_type
     * @param MarginProcedure|null $margin_procedure
     *
     * @return Element|null
     */
    protected function buildAndCreateResult(
        InvoiceModel $invoice,
        CompanyModel $company = null,
        InvoiceTypeHelper $invoice_type = null,
        MarginProcedure $margin_procedure = null
    ) {
        $invoice_taxes = Mockery::mock(InvoiceTaxes::class);
        $invoice_taxes->shouldReceive('create')->andReturn([]);
        $address = Mockery::mock(Address::class);
        $address->shouldReceive('getCompanyAddress')->once()->andReturn('whatever company');
        $address->shouldReceive('getContractorAddress')->once()->andReturn('whatever contractor');

        if ($company === null) {
            $company = new CompanyModel(['vat_payer' => true]);
        }

        if ($invoice_type === null) {
            $invoice_type = Mockery::mock(InvoiceTypeHelper::class);
            $invoice_type->shouldReceive('calculate')->andReturn('sample calculated type');
        }
        if ($margin_procedure === null) {
            $margin_procedure = $this->getDefaultMarginProcedure();
        }

        $invoice_element = new Invoice($invoice_taxes, $address, $invoice_type, $margin_procedure);

        return $invoice_element->create($invoice, $company);
    }

    /**
     * Get default margin procedure.
     *
     * @return Mockery\MockInterface
     */
    protected function getDefaultMarginProcedure()
    {
        $margin_procedure = Mockery::mock(MarginProcedure::class);
        $margin_procedure->shouldReceive('isUsedProductArtOrAntiqueMargin')->andReturn(false);
        $margin_procedure->shouldNotReceive('getName');
        $margin_procedure->shouldReceive('isTourOperatorMargin')->andReturn(false);

        return $margin_procedure;
    }

    /**
     * Create default invoice.
     *
     * @return InvoiceModel
     */
    protected function getDefaultInvoiceModel()
    {
        $invoice = new InvoiceModel();
        $invoice_contractor = new InvoiceContractor();
        $invoice_contractor->vatin = Factory::create()->randomNumber();
        $invoice_contractor->setRelation('vatinPrefix', null);
        $invoice_company = new InvoiceCompany();
        $invoice_company->vatin = Factory::create()->randomNumber();
        $invoice_company->setRelation('vatinPrefix', null);

        $invoice->setRelation('invoiceContractor', $invoice_contractor);
        $invoice->setRelation('invoiceCompany', $invoice_company);
        $invoice->setRelation('invoiceType', new InvoiceType());
        $invoice->setRelation('company', new Company([
            'vat_payer' => true,
        ]));

        return $invoice;
    }

    /**
     * Get default invoice item model.
     *
     * @return InvoiceItemModel
     */
    protected function getDefaultInvoiceItemModel()
    {
        $invoice_item_model = new InvoiceItemModel(['quantity' => 1000]);
        $invoice_item_model->setRelation('serviceUnit', new ServiceUnit());
        $invoice_item_model->setRelation('vatRate', new VatRate());

        return $invoice_item_model;
    }

    /**
     * @param $invoice
     */
    protected function setUpPropertiesForAdvance($invoice, $advance_type)
    {
        $proforma = new InvoiceModel();
        $proforma->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::PROFORMA])
        );
        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => $advance_type])
        );
        $invoice->setRelation('proforma', $proforma);

        $invoice->save();

        factory(InvoiceTaxReport::class, 2)->create([
            'price_net' => 1000,
            'price_gross' => 1230,
            'invoice_id' => $invoice->id,
        ]);
    }

    protected function mockInvoiceItemHelper()
    {
        $invoice_item_helper = \Mockery::mock(HelperInvoiceItem::class)->makePartial();
        $invoice_item_helper->shouldReceive('getRealRawNetPrice')->once()->withNoArgs()->andReturn(4182387883);
        $invoice_item_helper->shouldReceive('getRealRawBruttoPrice')->once()->withNoArgs()->andReturn(0);
        $invoice_item_helper->shouldReceive('getRealRawNetPriceSum')->once()->withNoArgs()->andReturn(418238788300);

        return $invoice_item_helper;
    }
}
