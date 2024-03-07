<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Models\Db\InvoiceType;
use App\Models\Db\Package;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\PaymentMethodType;
use Carbon\Carbon;
use App\Models\Db\PaymentMethod;
use App\Models\Db\CashFlow;
use App\Models\Db\Invoice;
use App\Models\Db\InvoicePayment;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class InvoicePaymentsControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_data_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        factory(InvoicePayment::class, 2)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
        ]);

        $this->get('/invoice-payments?selected_company_id=' . $company->id . '&invoice_id=' . $invoice->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'invoice_id',
                        'amount',
                        'payment_method_id',
                        'registrar_id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function index_it_returns_validation_error_without_invoice_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoice-payments?selected_company_id=' . $company->id)
            ->seeStatusCode(422);

        $this->verifyValidationResponse(['invoice_id']);
    }

    /** @test */
    public function index_with_invalid_company_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id + 10,
        ]);

        $this->get('/invoice-payments?selected_company_id=' . $company->id . '&invoice_id=' . $invoice->id)
            ->seeStatusCode(422);
    }

    /** @test */
    public function index_with_correct_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        $invoice_payments = factory(InvoicePayment::class, 2)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'amount' => 2514,
            'payment_method_id' => $payment_method->id,
            'created_at' => '2017-01-27 11:08:21',
            'updated_at' => '2017-01-27 11:08:21',
        ]);

        $this->get('/invoice-payments?selected_company_id=' . $company->id . '&invoice_id=' . $invoice->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($invoice_payments->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($invoice_payments[$key]->id, $item['id']);
            $this->assertEquals($invoice_payments[$key]->invoice_id, $item['invoice_id']);
            $this->assertEquals(25.14, $item['amount']);
            $this->assertEquals($invoice_payments[$key]->payment_method_id, $item['payment_method_id']);
            $this->assertEquals($invoice_payments[$key]->registrar_id, $item['registrar_id']);
            $this->assertEquals('2017-01-27 11:08:21', $item['created_at']);
            $this->assertEquals('2017-01-27 11:08:21', $item['updated_at']);
            $this->assertEquals(null, $item['deleted_at']);
        }
    }

    /** @test */
    public function store_it_saves_valid_data()
    {
        $now = Carbon::parse('2015-04-13 08:12:15');
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => 'testpayment',
            'name' => 'testpayment',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 12354,
            'payment_method_id' => $payment_method->id,
        ]);

        $invoice_payment_count = InvoicePayment::count();
        $cash_flow_count = CashFlow::count();

        Carbon::setTestNow($now);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method->id,
        ])->seeStatusCode(201);

        $this->assertEquals($invoice_payment_count + 1, InvoicePayment::count());
        $this->assertEquals($cash_flow_count, CashFlow::count());

        $invoice_payment = InvoicePayment::latest('id')->first();
        $invoice_fresh = $invoice->fresh();

        // CreateInvoice payment
        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame(12354, $invoice_payment->amount);
        $this->assertSame($payment_method->id, $invoice_payment->payment_method_id);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($now->toDateTimeString(), $invoice_fresh->paid_at);
        $this->assertSame(0, $invoice_fresh->payment_left);
    }

    /** @test */
    public function store_it_adds_partial_payment()
    {
        $now = Carbon::parse('2015-04-13 08:12:15');
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => 'testpayment',
            'name' => 'testpayment',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 10000,
            'payment_method_id' => $payment_method->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 4000,
            'payment_method_id' => $payment_method->id,
            'registrar_id' => $this->user->id,
        ]);

        $invoice_payment_count = InvoicePayment::count();

        Carbon::setTestNow($now);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 20.50,
            'payment_method_id' => $payment_method->id,
        ])->seeStatusCode(201);

        $this->assertEquals($invoice_payment_count + 1, InvoicePayment::count());

        $invoice_fresh = $invoice->fresh();

        // Check effects of partial payment
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame(3950, $invoice_fresh->payment_left);
    }

    /** @test */
    public function store_if_bank_transfer_payment_method_add_invoice_payment_but_not_cash_flow()
    {
        $now = Carbon::parse('2015-04-13 08:12:15');
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();

        $payment_method_bank_transfer = factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::BANK_TRANSFER,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 12354,
            'payment_method_id' => $payment_method_bank_transfer->id,
        ]);

        $init_invoice_payment_count = InvoicePayment::count();
        $init_cash_flow_count = CashFlow::count();

        Carbon::setTestNow($now);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method_bank_transfer->id,
        ])->seeStatusCode(201);

        $this->assertEquals($init_invoice_payment_count + 1, InvoicePayment::count());
        $this->assertEquals($init_cash_flow_count, CashFlow::count());

        $invoice_payment = InvoicePayment::latest('id')->first();
        $invoice_fresh = $invoice->fresh();
        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame(12354, $invoice_payment->amount);
        $this->assertSame($payment_method_bank_transfer->id, $invoice_payment->payment_method_id);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($now->toDateTimeString(), $invoice_fresh->paid_at);
    }

    /** @test */
    public function store_it_saved_data_to_no_cash_invoice_payment()
    {
        $now = Carbon::parse('2015-04-13 08:12:15');
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_method_cash = factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::DEBIT_CARD,
        ]);
        $payment_method_other = factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::OTHER,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 12354,
            'payment_method_id' => $payment_method_other->id,
        ]);

        $init_invoice_payment_count = InvoicePayment::count();
        $init_cash_flow_count = CashFlow::count();

        Carbon::setTestNow($now);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method_cash->id,
        ])->seeStatusCode(201);

        $this->assertEquals($init_invoice_payment_count + 1, InvoicePayment::count());
        $this->assertEquals($init_cash_flow_count + 1, CashFlow::count());

        $invoice_payment = InvoicePayment::latest('id')->first();
        $cash_flow = CashFlow::latest('id')->first();
        $invoice_fresh = $invoice->fresh();
        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame(12354, $invoice_payment->amount);
        $this->assertSame($payment_method_cash->id, $invoice_payment->payment_method_id);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($now->toDateTimeString(), $invoice_fresh->paid_at);

        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($invoice->id, $cash_flow->invoice_id);
        $this->assertSame(12354, $cash_flow->amount);
        $this->assertSame(CashFlow::DIRECTION_IN, $cash_flow->direction);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flow->flow_date);
        $this->assertSame(1, $cash_flow->cashless);
    }

    /** @test */
    public function store_it_saves_valid_data_to_cash_invoice_payment()
    {
        $now = Carbon::parse('2015-04-13 08:12:15');
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::CASH,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 12354,
            'payment_method_id' => $payment_method->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 9896,
            'payment_method_id' => $payment_method->id,
            'registrar_id' => $this->user->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 2458,
            'payment_method_id' => $payment_method->id,
            'registrar_id' => $this->user->id,
        ]);

        $invoice_payment_count = InvoicePayment::count();
        $cash_flow_count = CashFlow::count();

        Carbon::setTestNow($now);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method->id,
        ])->seeStatusCode(201);

        $this->assertEquals($invoice_payment_count + 1, InvoicePayment::count());
        $this->assertEquals($cash_flow_count + 1, CashFlow::count());

        $invoice_payment = InvoicePayment::latest('id')->first();
        $cash_flow = CashFlow::latest('id')->first();
        $invoice_fresh = $invoice->fresh();

        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame(12354, $invoice_payment->amount);
        $this->assertSame($payment_method->id, $invoice_payment->payment_method_id);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($now->toDateTimeString(), $invoice_fresh->paid_at);

        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($invoice->id, $cash_flow->invoice_id);
        $this->assertSame(12354, $cash_flow->amount);
        $this->assertSame(CashFlow::DIRECTION_IN, $cash_flow->direction);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flow->flow_date);
        $this->assertSame(0, $cash_flow->cashless);
    }

    /** @test */
    public function store_it_saves_valid_data_to_card_invoice_payment()
    {
        $now = Carbon::parse('2015-04-13 08:12:15');
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::DEBIT_CARD,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 12354,
            'payment_method_id' => $payment_method->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 9896,
            'payment_method_id' => $payment_method->id,
            'registrar_id' => $this->user->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 2458,
            'payment_method_id' => $payment_method->id,
            'registrar_id' => $this->user->id,
        ]);

        $invoice_payment_count = InvoicePayment::count();
        $cash_flow_count = CashFlow::count();

        Carbon::setTestNow($now);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method->id,
        ])->seeStatusCode(201);

        $this->assertEquals($invoice_payment_count + 1, InvoicePayment::count());
        $this->assertEquals($cash_flow_count + 1, CashFlow::count());

        $invoice_payment = InvoicePayment::latest('id')->first();
        $cash_flow = CashFlow::latest('id')->first();
        $invoice_fresh = $invoice->fresh();

        $this->assertSame($invoice->id, $invoice_payment->invoice_id);
        $this->assertSame(12354, $invoice_payment->amount);
        $this->assertSame($payment_method->id, $invoice_payment->payment_method_id);
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($now->toDateTimeString(), $invoice_fresh->paid_at);

        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($invoice->id, $cash_flow->invoice_id);
        $this->assertSame(12354, $cash_flow->amount);
        $this->assertSame(CashFlow::DIRECTION_IN, $cash_flow->direction);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flow->flow_date);
        $this->assertSame(1, $cash_flow->cashless);
    }

    /** @test */
    public function store_it_billing_completed()
    {
        $now = Carbon::parse('2015-04-13 08:12:15');
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::CASH,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 12354,
            'payment_method_id' => $payment_method->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 120.54,
            'payment_method_id' => $payment_method->id,
            'registrar_id' => $this->user->id,
            'deleted_at' => Carbon::now()->subDay(1)->toDateString(),
        ]);

        Carbon::setTestNow($now);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method->id,
        ])->seeStatusCode(201);

        $invoice = $invoice->fresh();
        $this->assertNotNull($invoice->paid_at);
    }

    /** @test */
    public function store_proforma_payment_validation_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $invoice = factory(Invoice::class)->create([
            'invoice_type_id' => $invoice_type->id,
            'company_id' => $company->id,
            'price_gross' => 12354,
        ]);
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method->id,
        ]);
        $this->verifyValidationResponse(['invoice_id']);
    }

    /** @test */
    public function store_it_saves_valid_data_greater_amount_than_invoice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => 'gotowka',
            'name' => 'gotĂłwka',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 12354,
            'payment_method_id' => $payment_method->id,
        ]);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 241.58,
            'payment_method_id' => $payment_method->id,
        ])->seeStatusCode(422);
    }

    /** @test */
    public function store_it_saves_data_with_invalid_price_gross()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 1254,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 123.54,
            'payment_method_id' => $payment_method->id,
        ])->seeStatusCode(422);
    }

    /** @test */
    public function store_it_returns_validation_with_invalid_invoice_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $payment_method = factory(PaymentMethod::class)->create();

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => 0,
            'amount' => 123.45,
            'payment_method_id' => $payment_method->id,
        ]);

        $this->verifyValidationResponse(['invoice_id'], ['amount', 'payment_method_id', 'registrar_id']);
    }

    /** @test */
    public function store_it_returns_validation_with_invalid_amount()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 0,
            'payment_method_id' => $payment_method->id,
        ]);

        $this->verifyValidationResponse(['amount'], ['invoice_id', 'payment_method_id', 'registrar_id']);
    }

    /** @test */
    public function store_it_returns_validation_with_invalid_payment_method_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 1234,
        ]);

        $this->post('/invoice-payments?selected_company_id=' . $company->id, [
            'invoice_id' => $invoice->id,
            'amount' => 12.34,
            'payment_method_id' => 0,
        ]);

        $this->verifyValidationResponse(['payment_method_id'], ['invoice_id', 'amount', 'registrar_id']);
    }

    /** @test */
    public function destroy_valid_data_with_paid_invoice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 8541,
            'paid_at' => Carbon::now(),
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        $invoice_payment_first = factory(InvoicePayment::class)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 2121,
        ]);
        factory(InvoicePayment::class)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 1121,
        ]);

        $this->assertNotNull($invoice->fresh()->paid_at);

        $this->delete('/invoice-payments/' . $invoice_payment_first->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(204);

        $invoice_fresh = $invoice->fresh();

        $this->assertEquals(1, InvoicePayment::where('invoice_id', $invoice->id)->count());
        $this->assertEquals(2, InvoicePayment::where('invoice_id', $invoice->id)->withTrashed()->count());
        $this->assertEquals(7420, $invoice_fresh->payment_left);

        $this->assertNull($invoice_fresh->paid_at);
    }

    /** @test */
    public function destroy_valid_data_with_unpaid_invoice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 8541,
            'paid_at' => null,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        $invoice_payment_first = factory(InvoicePayment::class)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 2121,
        ]);
        factory(InvoicePayment::class)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 11721,
        ]);

        Carbon::setTestNow(Carbon::create(2016, 5, 21, 12, 42, 54));

        $this->assertEquals(null, $invoice->paid_at);

        $this->delete('/invoice-payments/' . $invoice_payment_first->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(204);

        $invoice_fresh = $invoice->fresh();

        $this->assertEquals(1, InvoicePayment::where('invoice_id', $invoice->id)->count());
        $this->assertEquals(2, InvoicePayment::where('invoice_id', $invoice->id)->withTrashed()->count());

        $this->assertEquals(Carbon::now()->toDateTimeString(), $invoice_fresh->paid_at);
    }

    /** @test */
    public function destroy_invalid_invoice_payment()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        InvoicePayment::whereRaw('1 = 1')->delete();

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 8541,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_payment = factory(InvoicePayment::class)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 2121,
        ]);

        $this->delete('/invoice-payments/' . ($invoice_payment->id + 100) . '?selected_company_id=' . $company->id)
            ->seeStatusCode(404);
    }

    /** @test */
    public function destroy_invalid_payment_id_from_other_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        InvoicePayment::whereRaw('1 = 1')->delete();

        $invoice = factory(Invoice::class)->create([
            'company_id' => ($company->id + 10),
            'price_gross' => 8541,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_payment_other_company = factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 2121,
        ]);

        $this->delete('/invoice-payments/' . $invoice_payment_other_company->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(404);
    }

    /** @test */
    public function destroy_cant_delete_special_partial_payment()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_gross' => 8541,
            'paid_at' => null,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        $invoice_payment_first = factory(InvoicePayment::class)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 2121,
            'special_partial_payment' => true,
        ]);
        factory(InvoicePayment::class)->create([
            'registrar_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
            'amount' => 11721,
        ]);

        Carbon::setTestNow(Carbon::create(2016, 5, 21, 12, 42, 54));

        $this->assertEquals(null, $invoice->paid_at);

        $this->delete('/invoice-payments/' . $invoice_payment_first->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(404);
    }
}
