<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoiceType;
use App\Models\Db\Role;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;
use Auth;

class PrzyczynaKorektyFieldTest extends TestCase
{
    use Jpk;
    use DatabaseTransactions;

    private $company;

    protected function setUp():void
    {
        parent::setUp();
        $this->createUser();
        $this->company = $this->createCompanyWithRole(RoleType::OWNER);
        $this->user->setSelectedCompany($this->company->id, Role::findByName(RoleType::OWNER));
        Auth::shouldReceive('user')->andReturn($this->user);
    }

    /** @test */
    public function it_doesnt_add_correction_reason_for_non_correction_invoice()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::VAT])
        );

        $result = $this->buildAndCreateResult($invoice);

        $this->assertNull($this->findChildElement($result, 'tns:PrzyczynaKorekty'));
    }

    /** @test */
    public function it_sets_tax_correction_reason_when_tax_reason_set()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->correction_type = InvoiceCorrectionType::TAX;
        $invoice->issue_date = Carbon::now();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::CORRECTION])
        );
        $invoice->setRelation('correctedInvoice', new InvoiceModel([
            'issue_date' => Carbon::now(),
        ]));

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:PrzyczynaKorekty', 'Korekta stawki VAT');
    }

    /** @test */
    public function it_sets_price_correction_reason_when_price_reason_set()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->correction_type = InvoiceCorrectionType::PRICE;
        $invoice->issue_date = Carbon::now();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::ADVANCE_CORRECTION])
        );
        $invoice->setRelation('correctedInvoice', new InvoiceModel([
            'issue_date' => Carbon::now(),
        ]));

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:PrzyczynaKorekty', 'Korekta wartości/ceny');
    }

    /** @test */
    public function it_sets_quantity_correction_reason_when_quantity_reason_set()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->correction_type = InvoiceCorrectionType::QUANTITY;
        $invoice->issue_date = Carbon::now();
        $margin_type = new InvoiceType([
            'slug' => InvoiceTypeStatus::MARGIN_CORRECTION,
            'parent_type_id' => 'whatever',
        ]);
        $margin_type->setRelation(
            'parentType',
            new InvoiceType(['slug' => InvoiceTypeStatus::CORRECTION])
        );
        $invoice->setRelation('invoiceType', $margin_type);
        $invoice->setRelation('correctedInvoice', new InvoiceModel([
            'issue_date' => Carbon::now(),
        ]));
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::TOUR_OPERATOR])
        );

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:PrzyczynaKorekty', 'Korekta ilości');
    }
}
