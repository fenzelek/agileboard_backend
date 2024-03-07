<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Models\Db\Contractor;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Carbon\Carbon;
use App\Models\Db\Invoice;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class InvoiceReportControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_data_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    'price_net_sum',
                    'price_gross_sum',
                    'vat_sum_sum',
                    'payment_left_sum',
                ],
            ])->isJson();
    }

    /** @test */
    public function test_index_with_correct_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 2415,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(26.49, $data['price_net_sum']);
        $this->assertSame(49.90, $data['price_gross_sum']);
        $this->assertSame(15.22, $data['vat_sum_sum']);
        $this->assertSame(36.79, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_zero_sum_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 0,
            'price_gross' => 0,
            'vat_sum' => 0,
            'payment_left' => 0,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 0,
            'price_gross' => 0,
            'vat_sum' => 0,
            'payment_left' => 0,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(0, $data['price_net_sum']);
        $this->assertSame(0, $data['price_gross_sum']);
        $this->assertSame(0, $data['vat_sum_sum']);
        $this->assertSame(0, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_status_all_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 2415,
            'paid_at' => null,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
            'paid_at' => Carbon::now(),
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&status=all')
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(26.49, $data['price_net_sum']);
        $this->assertSame(49.90, $data['price_gross_sum']);
        $this->assertSame(15.22, $data['vat_sum_sum']);
        $this->assertSame(36.79, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_status_paid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 2415,
            'paid_at' => null,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
            'paid_at' => Carbon::now(),
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&status=paid')
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(11.95, $data['price_net_sum']);
        $this->assertSame(28.45, $data['price_gross_sum']);
        $this->assertSame(9.75, $data['vat_sum_sum']);
        $this->assertSame(12.64, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_status_not_paid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 2415,
            'paid_at' => null,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
            'paid_at' => Carbon::now(),
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&status=not_paid')
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(24.15, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_status_paid_late_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-01-15',
            'payment_term_days' => 4,
            'paid_at' => '2017-01-16 02:00:00',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 2415,
            'issue_date' => '2017-01-15',
            'payment_term_days' => 4,
            'paid_at' => '2017-01-22 02:00:00',
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&status=paid_late')
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(24.15, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_status_deleted_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 2415,
            'paid_at' => null,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
            'paid_at' => Carbon::now(),
            'deleted_at' => '2017-01-22 02:00:00',
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&status=deleted')
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(0, $data['price_net_sum']);
        $this->assertSame(0, $data['price_gross_sum']);
        $this->assertSame(0, $data['vat_sum_sum']);
        $this->assertSame(0, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_status_not_deleted_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 2415,
            'paid_at' => null,
            'deleted_at' => '2017-01-22 02:00:00',
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
            'paid_at' => Carbon::now(),
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&status=not_deleted')
            ->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(11.95, $data['price_net_sum']);
        $this->assertSame(28.45, $data['price_gross_sum']);
        $this->assertSame(9.75, $data['vat_sum_sum']);
        $this->assertSame(12.64, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_status_invalid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&status=abc');

        $this->verifyValidationResponse(['status']);
    }

    /** @test */
    public function test_index_with_date_start_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
            'issue_date' => Carbon::now()->subMonths(60)->toDateString(),
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 2457,
            'price_gross' => 3457,
            'vat_sum' => 428,
            'payment_left' => 1195,
            'issue_date' => Carbon::now()->subMonths(10)->toDateString(),
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
            'issue_date' => Carbon::now()->subMonths(10)->toDateString(),
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&date_start=' . Carbon::now()->subMonths(20)->toDateString());
        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(36.52, $data['price_net_sum']);
        $this->assertSame(63.02, $data['price_gross_sum']);
        $this->assertSame(14.03, $data['vat_sum_sum']);
        $this->assertSame(24.59, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_date_start_invalid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&date_start=abc');

        $this->verifyValidationResponse(['date_start']);
    }

    /** @test */
    public function test_index_with_date_end_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
            'issue_date' => Carbon::now()->subMonths(10)->toDateString(),
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 2457,
            'price_gross' => 3457,
            'vat_sum' => 428,
            'payment_left' => 1195,
            'issue_date' => Carbon::now()->subMonths(60)->toDateString(),
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1195,
            'price_gross' => 2845,
            'vat_sum' => 975,
            'payment_left' => 1264,
            'issue_date' => Carbon::now()->subMonths(60)->toDateString(),
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&date_end=' . Carbon::now()->subMonths(60)->toDateString());
        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(36.52, $data['price_net_sum']);
        $this->assertSame(63.02, $data['price_gross_sum']);
        $this->assertSame(14.03, $data['vat_sum_sum']);
        $this->assertSame(24.59, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_date_end_invalid_extra_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&date_end=abc');

        $this->verifyValidationResponse(['date_end']);
    }

    /** @test */
    public function test_index_with_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&id=' . $invoice->id);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_invalid_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        $invoices = factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&id=' .
            ($invoices[0]->id + 10));

        $this->verifyValidationResponse(['id']);
    }

    /** @test */
    public function test_index_with_number_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 1234,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 1584595,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&number=' . $invoice->number);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_number_like_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 1234,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => '15/a1/2017WK',
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);
        factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => '05/a1/2017TK',
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);
        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&number=a1/2017');

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(29.08, $data['price_net_sum']);
        $this->assertSame(42.90, $data['price_gross_sum']);
        $this->assertSame(10.94, $data['vat_sum_sum']);
        $this->assertSame(29.08, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_contractor_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);

        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&contractor_id=' . $invoice->contractor_id);
        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_invalid_contractor_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $this->user->id,
        ]);
        $invoice->contractor_id = $invoice_contractor->id;
        $invoice->save();

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&contractor_id=' .
            ($invoice->contractor_id + 5));

        $this->verifyValidationResponse(['contractor_id']);
    }

    /** @test */
    public function test_index_with_drawer_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&drawer_id=' . $invoice->drawer_id);
        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_invalid_drawer_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Invoice::whereRaw('1 = 1')->delete();
        factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'drawer_id' => $this->user->id,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&drawer_id=' .
            ($invoice->drawer_id + 5));

        $this->verifyValidationResponse(['drawer_id']);
    }

    /** @test */
    public function test_index_with_contractor_vatin_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $this->user->id,
            'vatin' => '123456789',
        ]);
        $invoice->contractor_id = $invoice_contractor->id;
        $invoice->save();

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&contractor=45678');
        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function test_index_with_contractor_name_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $this->user->id,
            'name' => 'example_name',
        ]);
        $invoice->contractor_id = $invoice_contractor->id;
        $invoice->save();

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&contractor=mple_na');
        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function index_proforma_id_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $invoice_proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advance = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'proforma_id' => $invoice_proforma->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id .
            '&proforma_id=' . $invoice_advance->id)->seeStatusCode(422);

        $this->verifyValidationResponse(['proforma_id']);
    }

    /** @test */
    public function index_proforma_id_filter_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $invoice_proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advance = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'proforma_id' => $invoice_proforma->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id .
            '&proforma_id=' . $invoice_proforma->id);
        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function index_invoice_type_id_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id
            . '&invoice_type_id=923')->seeStatusCode(422);

        $this->verifyValidationResponse(['invoice_type_id']);
    }

    /** @test */
    public function index_invoice_type_id_filter_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $invoice_proforma = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        $invoice_advance = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'proforma_id' => $invoice_proforma->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id .
            '&invoice_type_id=' . InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id);

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }

    /** @test */
    public function index_invoice_registry_id_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id
            . '&invoice_registry_id=0')->seeStatusCode(422);

        $this->verifyValidationResponse(['invoice_registry_id']);
    }

    /** @test */
    public function index_invoice_registry_id_other_company_filter_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class, 2)->create([
            'company_id' => $company->id,
            'invoice_registry_id' => InvoiceRegistry::create()->id,
        ]);

        $this->get(
            '/reports/company-invoices?selected_company_id=' . $company->id
            . '&invoice_registry_id=' . $invoice[0]->invoice_registry_id
        )->seeStatusCode(422);

        $this->verifyValidationResponse(['invoice_registry_id']);
    }

    /** @test */
    public function index_invoice_registry_id_filter_with_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $registry = factory(InvoiceRegistry::class)->create(['company_id' => $company->id]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_registry_id' => $registry->id,
            'price_net' => 1454,
            'price_gross' => 2145,
            'vat_sum' => 547,
            'payment_left' => 1454,
        ]);

        factory(Invoice::class)->create(['company_id' => $company->id]);

        $this->get('/reports/company-invoices?selected_company_id=' . $company->id . '&invoice_registry_id=' . $registry->id)
            ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(14.54, $data['price_net_sum']);
        $this->assertSame(21.45, $data['price_gross_sum']);
        $this->assertSame(5.47, $data['vat_sum_sum']);
        $this->assertSame(14.54, $data['payment_left_sum']);
    }
}
