<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Models\Db\Company;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class InvoiceCorrectionTypeControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $company;

    public function setUp():void
    {
        parent::setUp();
        $this->createUser();
        $this->company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
    }

    /** @test */
    public function index_validation_error_not_found_invoice()
    {
        $other_company = factory(Company::class)->create();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $other_company->id,
        ]);
        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id . '&invoice_id=' . $invoice->id)
            ->seeStatusCode(422);

        $this->verifyValidationResponse([
            'invoice_id',
        ]);
    }

    /** @test */
    public function index_data_structure()
    {
        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200)->isJson();
    }

    /** @test */
    public function test_index_with_correct_data()
    {
        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id)
        ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $expected_types = [
            [
                'slug' => InvoiceCorrectionType::PRICE,
                'description' => 'Korekta wartości/ceny',
            ],
            [
                'slug' => InvoiceCorrectionType::QUANTITY,
                'description' => 'Korekta ilości',
            ],
            [
                'slug' => InvoiceCorrectionType::TAX,
                'description' => 'Korekta stawki VAT',
            ],
        ];
        $this->assertSame($expected_types, $data);
    }

    /** @test */
    public function index_filter_by_margin_invoice()
    {
        $invoice = factory(Invoice::class)->create([
            'company_id' => $this->company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
        ]);
        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id . '&invoice_id=' . $invoice->id)
        ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $expected_types = [
            [
                'slug' => InvoiceCorrectionType::PRICE,
                'description' => 'Korekta wartości/ceny',
            ],
            [
                'slug' => InvoiceCorrectionType::QUANTITY,
                'description' => 'Korekta ilości',
            ],
        ];
        $this->assertSame($expected_types, $data);
    }

    /** @test */
    public function index_filter_by_proforma()
    {
        $invoice = factory(Invoice::class)->create([
            'company_id' => $this->company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id . '&invoice_id=' . $invoice->id)
        ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $expected_types = [];
        $this->assertSame($expected_types, $data);
    }

    /** @test */
    public function index_filter_by_invoice_vat()
    {
        $invoice = factory(Invoice::class)->create([
            'company_id' => $this->company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id . '&invoice_id=' . $invoice->id)
        ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $expected_types = [
            [
                'slug' => InvoiceCorrectionType::PRICE,
                'description' => 'Korekta wartości/ceny',
            ],
            [
                'slug' => InvoiceCorrectionType::QUANTITY,
                'description' => 'Korekta ilości',
            ],
            [
                'slug' => InvoiceCorrectionType::TAX,
                'description' => 'Korekta stawki VAT',
            ],
        ];
        $this->assertSame($expected_types, $data);
    }

    /** @test */
    public function index_filter_by_reverse_charge_invoice()
    {
        $invoice = factory(Invoice::class)->create([
            'company_id' => $this->company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
        ]);
        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id . '&invoice_id=' . $invoice->id)
            ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $expected_types = [
            [
                'slug' => InvoiceCorrectionType::PRICE,
                'description' => 'Korekta wartości/ceny',
            ],
            [
                'slug' => InvoiceCorrectionType::QUANTITY,
                'description' => 'Korekta ilości',
            ],
        ];
        $this->assertSame($expected_types, $data);
    }

    /** @test */
    public function test_index_exclude_tax_type_for_no_vat_payer()
    {
        $this->company->update([
            'vat_payer' => false,
        ]);

        $this->get('/invoice-correction-types?selected_company_id=' . $this->company->id)
            ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $expected_types = [
            [
                'slug' => InvoiceCorrectionType::PRICE,
                'description' => 'Korekta wartości/ceny',
            ],
            [
                'slug' => InvoiceCorrectionType::QUANTITY,
                'description' => 'Korekta ilości',
            ],
        ];
        $this->assertSame($expected_types, $data);
    }
}
