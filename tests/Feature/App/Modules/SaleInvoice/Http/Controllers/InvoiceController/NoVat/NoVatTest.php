<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\NoVat;

use App\Models\Db\InvoiceRegistry;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use Carbon\Carbon;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment as FinancialEnvironmentTrait;

class NoVatTest extends FinancialEnvironment
{
    use DatabaseTransactions, FinancialEnvironmentTrait;

    private $now;
    private $registry;
    private $company;
    private $delivery_address;
    private $incoming_data;
    private $np_vat_rate;
    private $taxes_data;
    private $items_data;
    private $payment_method;
    private $invoice;
    private $invoice_type;

    public function setUp():void
    {
        parent::setUp();
        $this->registry = factory(InvoiceRegistry::class)->create();
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);

        list($this->company, $this->payment_method, $this->contractor, $this->invoice, $this->items_data, $this->taxes_data) = $this->setFinancialEnvironmentForUpdate();

        $this->invoice->update([
            'gross_counted' => NoVat::COUNT_TYPE,
        ]);
        $this->company->update([
            'vat_payer' => false,
        ]);
    }

    /** @test */
    public function index_see_only_no_vat_invoice()
    {
        $this->get('invoices?selected_company_id=' . $this->company->id)
            ->assertResponseStatus(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($response));
        $this->assertSame($this->invoice->id, $response[0]['id']);
    }

    /** @test */
    public function show_see_only_no_vat_invoice()
    {
        $this->get('invoices/' . $this->invoice->id . '/?selected_company_id=' . $this->company->id)
            ->assertResponseStatus(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertSame($this->invoice->id, $response['id']);
    }
}
