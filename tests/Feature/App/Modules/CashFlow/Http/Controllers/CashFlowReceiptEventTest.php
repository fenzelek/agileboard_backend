<?php

namespace Tests\Feature\App\Modules\CashFlow\Http\Controllers;

use App\Models\Db\Package;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\Receipt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\CashFlow;
use App\Models\Db\PaymentMethod;
use App\Models\Db\VatRate;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CompanyTokenCreator;

class CashFlowReceiptEventTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CompanyTokenCreator;

    /** @test */
    public function store_receipt_validation_passing_with_cash_flow()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create(['slug' => PaymentMethodType::CASH]);
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $receipts_count = Receipt::count();
        $cash_flow_count = CashFlow::count();

        $this->post('receipts', $incoming_data, ['Authorization-Api-Token' => $api_token])
            ->assertResponseStatus(201);

        $this->assertEquals($receipts_count + 1, Receipt::count());
        $this->assertEquals($cash_flow_count + 2, CashFlow::count());

        $data = $this->decodeResponseJson()['data'];
        $cash_flows = CashFlow::latest('id')->take(2)->get();

        $this->assertEquals($company->id, $cash_flows[0]->company_id);
        $this->assertEquals($this->user->id, $cash_flows[0]->user_id);
        $this->assertEquals($data['id'], $cash_flows[0]->receipt_id);
        $this->assertEquals(5334, $cash_flows[0]->amount);
        $this->assertEquals('out', $cash_flows[0]->direction);
        $this->assertSame('', $cash_flows[0]->description);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flows[0]->flow_date);

        $this->assertEquals($company->id, $cash_flows[1]->company_id);
        $this->assertEquals($this->user->id, $cash_flows[1]->user_id);
        $this->assertEquals($data['id'], $cash_flows[1]->receipt_id);
        $this->assertEquals(30000, $cash_flows[1]->amount);
        $this->assertEquals('in', $cash_flows[1]->direction);
        $this->assertSame('', $cash_flows[1]->description);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flows[1]->flow_date);

        $receipt = Receipt::latest('id')->first();
        $this->assertSame(5334, $receipt->cash_back);
    }

    /** @test */
    public function store_receipt_validation_passing_without_cash_flow()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create();
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $receipts_count = Receipt::count();
        $cash_flow_count = CashFlow::count();

        $this->post('receipts', $incoming_data, ['Authorization-Api-Token' => $api_token])
            ->assertResponseStatus(201);

        $this->assertEquals($receipts_count + 1, Receipt::count());
        $this->assertEquals($cash_flow_count, CashFlow::count());
    }

    /** @test */
    public function store_receipt_and_fire_event_for_mix_payment_cash_and_card()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create(['slug' => PaymentMethodType::CASH_CARD]);
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $receipts_count = Receipt::count();
        $cash_flow_count = CashFlow::count();

        $this->post('receipts', $incoming_data, ['Authorization-Api-Token' => $api_token])
            ->assertResponseStatus(201);

        $this->assertEquals($receipts_count + 1, Receipt::count());
        $this->assertEquals($cash_flow_count + 3, CashFlow::count());

        $data = $this->decodeResponseJson()['data'];
        $cash_flows = CashFlow::latest('id')->take(3)->get();

        $this->assertEquals($company->id, $cash_flows[0]->company_id);
        $this->assertEquals($this->user->id, $cash_flows[0]->user_id);
        $this->assertEquals($data['id'], $cash_flows[0]->receipt_id);
        $this->assertEquals(25334, $cash_flows[0]->amount);
        $this->assertEquals('out', $cash_flows[0]->direction);
        $this->assertSame('', $cash_flows[0]->description);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flows[0]->flow_date);
        $this->assertFalse((bool) $cash_flows[0]->cashless);

        $this->assertEquals($company->id, $cash_flows[1]->company_id);
        $this->assertEquals($this->user->id, $cash_flows[1]->user_id);
        $this->assertEquals($data['id'], $cash_flows[1]->receipt_id);
        $this->assertEquals(20000, $cash_flows[1]->amount);
        $this->assertEquals('in', $cash_flows[1]->direction);
        $this->assertSame('', $cash_flows[1]->description);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flows[1]->flow_date);
        $this->assertTrue((bool) $cash_flows[1]->cashless);

        $this->assertEquals($company->id, $cash_flows[2]->company_id);
        $this->assertEquals($this->user->id, $cash_flows[2]->user_id);
        $this->assertEquals($data['id'], $cash_flows[2]->receipt_id);
        $this->assertEquals(30000, $cash_flows[2]->amount);
        $this->assertEquals('in', $cash_flows[2]->direction);
        $this->assertSame('', $cash_flows[2]->description);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flows[2]->flow_date);
        $this->assertFalse((bool) $cash_flows[2]->cashless);

        $receipt = Receipt::latest('id')->first();
        $this->assertSame(25334, $receipt->cash_back);
    }

    /** @test */
    public function store_receipt_and_fire_event_for_add_payment_do_cash_flow()
    {
        list($company, $api_token) = $this->login_user_and_return_company_with_his_employee_role();
        $payment_method = factory(PaymentMethod::class)->create(['slug' => PaymentMethodType::DEBIT_CARD]);
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->store_init_incoming_data($payment_method, $vat_rate);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $receipts_count = Receipt::count();
        $cash_flow_count = CashFlow::count();

        $this->post('receipts', $incoming_data, ['Authorization-Api-Token' => $api_token])
            ->assertResponseStatus(201);

        $this->assertEquals($receipts_count + 1, Receipt::count());
        $this->assertEquals($cash_flow_count + 1, CashFlow::count());

        $data = $this->decodeResponseJson()['data'];
        $cash_flow = CashFlow::latest('id')->first();

        $this->assertEquals($company->id, $cash_flow->company_id);
        $this->assertEquals($this->user->id, $cash_flow->user_id);
        $this->assertEquals($data['id'], $cash_flow->receipt_id);
        $this->assertEquals(20000, $cash_flow->amount);
        $this->assertEquals('in', $cash_flow->direction);
        $this->assertSame('', $cash_flow->description);
        $this->assertSame(Carbon::now()->toDateString(), $cash_flow->flow_date);
        $this->assertTrue((bool) $cash_flow->cashless);

        $receipt = Receipt::latest('id')->first();
        $this->assertSame(0, $receipt->cash_back);
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_USER
        );

        return [$company, $api_token];
    }

    protected function store_init_incoming_data(
        PaymentMethod $payment_method,
        VatRate $vat_rate
    ) {
        return [
            'transaction_number' => '1234567890',
            'sale_date' => Carbon::now()->toDateTimeString(),
            'price_net' => 200.54,
            'price_gross' => 246.66,
            'vat_sum' => 46.77,
            'payment_method' => $payment_method->slug,
            'number' => '1234567890',
            'items' => [
                [
                    'name' => 'service_1',
                    'price_net' => 10.00,
                    'price_net_sum' => 100.00,
                    'price_gross' => 12.30,
                    'price_gross_sum' => 123.00,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 23.00,
                    'quantity' => 10,
                ],
                [
                    'name' => 'service_2',
                    'price_net' => 20.1,
                    'price_net_sum' => 100,
                    'price_gross' => 24.6,
                    'price_gross_sum' => 123,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 23.6,
                    'quantity' => 5,
                ],
                [
                    'name' => 'service_same',
                    'price_net' => 10.45,
                    'price_net_sum' => 500,
                    'price_gross' => 11.1,
                    'price_gross_sum' => 333.33,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 33.33,
                    'quantity' => 5,
                ],
                [
                    'name' => 'service_same',
                    'price_net' => 30.333,
                    'price_net_sum' => 300.202,
                    'price_gross' => 33.404,
                    'price_gross_sum' => 333.993,
                    'vat_rate' => $vat_rate->name,
                    'vat_sum' => 66.663,
                    'quantity' => 10,
                ],
            ],
            'payment_method_types' => [
                [
                    'type' => PaymentMethodType::CASH,
                    'amount' => 300,
                ],
                [
                    'type' => PaymentMethodType::DEBIT_CARD,
                    'amount' => 200,
                ],
            ],
        ];
    }
}
