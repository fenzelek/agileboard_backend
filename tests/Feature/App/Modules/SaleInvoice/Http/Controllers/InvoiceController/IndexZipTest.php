<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Helpers\ErrorCode;
use App\Modules\SaleInvoice\Jobs\CreateInvoicesPackage;
use Illuminate\Support\Facades\Bus;
use App\Models\Db\Invoice;
use App\Models\Other\RoleType;
use App\Models\Other\SaleInvoice\FilterOption;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Request;
use Tests\BrowserKitTestCase;

class IndexZipTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    private $company;

    protected function setUp():void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::OWNER);
    }

    /** @test */
    public function indexZip_has_permission()
    {
        Bus::fake();

        $this->get('invoices/zip?selected_company_id=' . $this->company->id)
            ->assertResponseStatus(200);
    }

    /** @test */
    public function indexZipOverFlowBuffer()
    {
        \Config::set('invoices.package_overflow', 2);
        factory(Invoice::class, 3)->create([
            'company_id' => $this->company->id,
        ]);
        $this->get('invoices/zip?selected_company_id=' . $this->company->id);
        $this->verifyErrorResponse(427, ErrorCode::INVOICE_PACKAGE_BUFFER_OVERFLOW);
    }

    /** @test */
    public function indexZip_validation_error()
    {
        $this->json(Request::METHOD_GET, '/invoices/zip', [
            'selected_company_id' => $this->company->id,
            'status' => 'abc',
            'date_start' => 'abc',
            'date_end' => 'abc',
            'id' => 'no_valid_invoice_id',
            'contractor_id' => 'not_valid_contractor_id',
            'drawer_id' => 'no_valid_drawer_id',
            'proforma_id' => 'not_valid_proforma_id',
            'invoice_type_id' => 'not_valid_invoice_type_id',
            ]);

        $this->verifyValidationResponse([
            'status',
            'date_start',
            'date_end',
            'id',
            'contractor_id',
            'drawer_id',
            'proforma_id',
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function indexZip_with_status_all_extra_filter()
    {
        Bus::fake();

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $this->company->id,
            'paid_at' => null,
        ]);
        factory(Invoice::class, 2)->create([
            'company_id' => $this->company->id,
            'paid_at' => Carbon::now(),
        ]);

        $this->json(Request::METHOD_GET, '/invoices/zip', [
            'selected_company_id' => $this->company->id,
            'status' => FilterOption::NOT_PAID,
        ]);

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(3, $data['count']);
    }

    /** @test */
    public function indexZpi_invoice_package_was_dispatched()
    {
        Bus::fake();

        factory(Invoice::class, 2)->create([
            'company_id' => $this->company->id,
            'paid_at' => Carbon::now(),
        ]);

        $this->json(Request::METHOD_GET, '/invoices/zip', [
            'selected_company_id' => $this->company->id,
        ]);
        Bus::assertDispatched(CreateInvoicesPackage::class, function ($job) {
            return $job->invoices->count() === 2;
        });
    }
}
