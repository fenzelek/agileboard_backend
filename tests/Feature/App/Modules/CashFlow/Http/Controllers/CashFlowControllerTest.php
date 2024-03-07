<?php

namespace Tests\Feature\App\Modules\CashFlow\Http\Controllers;

use App\Models\Db\Invoice;
use App\Models\Db\Company;
use App\Models\Db\Package;
use Carbon\Carbon;
use App\Models\Db\Receipt;
use App\Models\Db\CashFlow;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use File;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\In;
use Tests\BrowserKitTestCase;
use Tests\Helpers\StringHelper;

class CashFlowControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, StringHelper;

    /** @test */
    public function index_data_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'company_id',
                        'user_id',
                        'receipt_id',
                        'amount',
                        'direction',
                        'description',
                        'flow_date',
                        'cashless',
                        'created_at',
                        'updated_at',
                        'invoice',
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'links',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function index_data_structure_with_receipt_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $receipt = factory(Receipt::class)->create();
        factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'receipt_id' => $receipt->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'company_id',
                        'user_id',
                        'receipt_id',
                        'amount',
                        'direction',
                        'description',
                        'flow_date',
                        'cashless',
                        'created_at',
                        'updated_at',
                        'receipt' => [
                            'data' => [
                                'id',
                                'number',
                                'transaction_number',
                                'user_id',
                                'company_id',
                                'sale_date',
                                'price_net',
                                'price_gross',
                                'vat_sum',
                                'payment_method_id',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'links',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function index_data_structure_with_invoice_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $invoice = factory(Invoice::class)->create();
        factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'company_id',
                        'user_id',
                        'receipt_id',
                        'amount',
                        'direction',
                        'description',
                        'flow_date',
                        'cashless',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'invoice' => [
                            'data' => [
                                'id',
                                'number',
                            ],
                        ],
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'links',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function index_response_without_inputs()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id)
            ->seeStatusCode(422);
    }

    /** @test */
    public function index_validation_error_cashless()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/cash-flows?selected_company_id=' . $company->id
            . '&cashless=' . 'not_valid_boolean')
            ->seeStatusCode(422);

        $this->verifyValidationResponse(['cashless']);

        $this->get('/cash-flows?selected_company_id=' . $company->id
            . '&cashless=' . 10)
            ->seeStatusCode(422);

        $this->verifyValidationResponse(['cashless']);
    }

    /** @test */
    public function index_response_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        // we will get what we expect
        CashFlow::whereRaw('1 = 1')->delete();

        $cash_flows = factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 32125,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        // soft delete cash flows
        factory(CashFlow::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 32125,
            'flow_date' => Carbon::now()->toDateString(),
            'deleted_at' => Carbon::now()->toDateString(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();

        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $this->assertEquals(4, count($data));
        // with soft delete
        $this->assertEquals(6, CashFlow::withTrashed()->count());

        foreach ($data as $key => $item) {
            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEquals(321.25, $item['amount']);
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);
            $this->assertFalse($item['balanced']);
        }

        $this->assertEquals($cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_with_balanced_option_selected_without_pagination()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();

        $cash_flows = $this->getReadyToBalanceCashFlows($company);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString() . '&balanced=1')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $this->assertEquals($cash_flows->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEquals(round($cash_flows[$key]->amount / 100, 2), $item['amount'], 0.0001);
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);
            $this->assertSame($cash_flows[$key]->balanced, $item['balanced']);
        }

        $this->assertEquals($cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_with_balanced_option_selected_with_pagination()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();

        $cash_flows = $this->getReadyToBalanceCashFlows($company);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString() .
            '&balanced=1&limit=3&page=1')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $total_cash_flows = clone ($cash_flows);

        // we get only 3 items (&limit=3&page=1)
        $cash_flows = $cash_flows->slice(0, 3);

        $this->assertEquals($cash_flows->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEqualsWithDelta(
                round($cash_flows[$key]->amount / 100, 2),
                $item['amount'],
                0.0001
            );
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);
            $this->assertSame($cash_flows[$key]->balanced, $item['balanced']);
        }

        $this->assertEquals($total_cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_with_balanced_option_selected_with_pagination_2nd_page()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();

        $cash_flows = $this->getReadyToBalanceCashFlows($company);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString() .
            '&balanced=1&limit=3&page=2')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $total_cash_flows = clone ($cash_flows);

        // we get only 3 items (&limit=3&page=2)
        $cash_flows = $cash_flows->slice(3, 3)->values();

        $this->assertEquals($cash_flows->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEqualsWithDelta(
                round($cash_flows[$key]->amount / 100, 2),
                $item['amount'],
                0.0001
            );
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);
            $this->assertSame($cash_flows[$key]->balanced, $item['balanced']);
        }

        $this->assertEquals($total_cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_with_balanced_option_not_selected()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();

        $cash_flows = $this->getReadyToBalanceCashFlows($company, false);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $this->assertEquals($cash_flows->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEqualsWithDelta(
                round($cash_flows[$key]->amount / 100, 2),
                $item['amount'],
                0.0001
            );
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);
            $this->assertFalse($item['balanced']);
        }

        $this->assertEquals($cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_with_balanced_option_not_selected_with_pagination()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();

        $cash_flows = $this->getReadyToBalanceCashFlows($company, false);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString() .
            '&limit=3&page=1')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $total_cash_flows = clone ($cash_flows);

        // we get only 3 items (&limit=3&page=1)
        $cash_flows = $cash_flows->slice(0, 3);

        $this->assertEquals($cash_flows->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEqualsWithDelta(
                round($cash_flows[$key]->amount / 100, 2),
                $item['amount'],
                0.0001
            );
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);
            $this->assertFalse($item['balanced']);
        }

        $this->assertEquals($total_cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_with_balanced_option_not_selected_with_pagination_2nd_page()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();

        $cash_flows = $this->getReadyToBalanceCashFlows($company, false);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString() .
            '&limit=3&page=2')
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $total_cash_flows = clone ($cash_flows);

        // we get only 3 items (&limit=3&page=2)
        $cash_flows = $cash_flows->slice(3, 3)->values();

        $this->assertEquals($cash_flows->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEqualsWithDelta(
                round($cash_flows[$key]->amount / 100, 2),
                $item['amount'],
                0.0001
            );
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);
            $this->assertFalse($item['balanced']);
        }

        $this->assertEquals($total_cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_data_with_receipt_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        $receipt = factory(Receipt::class)->create([
            'price_net' => 1932,
            'price_gross' => 2118,
            'vat_sum' => 1086,
        ]);
        $cash_flows = factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'receipt_id' => $receipt->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $this->assertEquals($cash_flows->count(), count($data));

        foreach ($data as $key => $item) {
            $receipt_data = $item['receipt']['data'];

            $this->assertEquals($cash_flows[$key]->id, $item['id']);
            $this->assertEquals($cash_flows[$key]->company_id, $item['company_id']);
            $this->assertEquals($cash_flows[$key]->user_id, $item['user_id']);
            $this->assertEquals($cash_flows[$key]->receipt_id, $item['receipt_id']);
            $this->assertEquals(
                number_format((float) $cash_flows[$key]->amount / 100, 2),
                $item['amount']
            );
            $this->assertEquals($cash_flows[$key]->direction, $item['direction']);
            $this->assertEquals($cash_flows[$key]->description, $item['description']);
            $this->assertEquals($cash_flows[$key]->flow_date, $item['flow_date']);
            $this->assertEquals($cash_flows[$key]->created_at, $item['created_at']);
            $this->assertEquals($cash_flows[$key]->updated_at, $item['updated_at']);
            $this->assertFalse((bool) $item['cashless']);

            $this->assertEquals($receipt->id, $receipt_data['id']);
            $this->assertEquals($receipt->number, $receipt_data['number']);
            $this->assertEquals($receipt->transaction_number, $receipt_data['transaction_number']);
            $this->assertEquals($receipt->user_id, $receipt_data['user_id']);
            $this->assertEquals($receipt->company_id, $receipt_data['company_id']);
            $this->assertEquals(19.32, $receipt_data['price_net']);
            $this->assertEquals(21.18, $receipt_data['price_gross']);
            $this->assertEquals(10.86, $receipt_data['vat_sum']);
            $this->assertEquals($receipt->payment_method_id, $receipt_data['payment_method_id']);
            $this->assertEquals($receipt->created_at, $receipt_data['created_at']);
            $this->assertEquals($receipt->updated_at, $receipt_data['updated_at']);
        }

        $this->assertEquals($cash_flows->count(), $pagination['total']);
        $this->assertEquals($cash_flows->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_data_with_invoice_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        $invoice = factory(Invoice::class)->create([
            'number' => 'AA123',
        ]);
        $cash_flows = factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson()['data'];

        $this->assertEquals($invoice->id, $response[0]['invoice']['data']['id']);
        $this->assertEquals('AA123', $response[0]['invoice']['data']['number']);
    }

    /** @test */
    public function index_response_data_with_user_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $cash_flows = factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($cash_flows->count(), count($data));
    }

    /** @test */
    public function index_response_data_with_date_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->subMonths(6),
        ]);
        $cash_flows_new = factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($cash_flows_new->count(), count($data));
    }

    /** @test */
    public function index_response_data_with_user_company_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 2)->create(['company_id' => $company->id + 5]);
        $cash_flows = factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now(),
        ]);

        $this->get('/cash-flows?selected_company_id=' . $company->id . '&user_id=' .
            $this->user->id . '&cashless=0&date=' . Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($cash_flows->count(), count($data));
    }

    /** @test */
    public function index_response_data_filter_by_cashless()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 2)->create(['company_id' => $company->id + 5]);
        $cash_flows = factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now(),
        ]);

        $cash_flows[0]->cashless = 1;
        $cash_flows[0]->amount = 600;
        $cash_flows[0]->save();

        $this->get('/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id . '&cashless=' . 1 . '&date=' .
            Carbon::now()->toDateString())
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));

        $this->assertEquals($cash_flows[0]->id, $data[0]['id']);
        $this->assertEquals($cash_flows[0]->company_id, $data[0]['company_id']);
        $this->assertEquals($cash_flows[0]->user_id, $data[0]['user_id']);
        $this->assertEquals(6, $data[0]['amount']);
        $this->assertEquals('2016-02-03', $data[0]['flow_date']);
        $this->assertTrue((bool) $data[0]['cashless']);
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->post('/cash-flows?selected_company_id=' . $company->id);

        $this->verifyValidationResponse(['amount', 'direction', 'flow_date', 'cashless']);
    }

    /** @test */
    public function store_it_returns_validation_error_without_document_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $count = CashFlow::count();

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_type' => 'receipt',
            'amount' => 12.34,
            'direction' => 'in',
            'description' => 'test description',
            'flow_date' => '2016-01-01',
            'cashless' => 1,
        ])->assertResponseStatus(201);

        $this->assertEquals($count + 1, CashFlow::count());

        $cash_flow = CashFlow::latest('id')->first();

        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertNull($cash_flow->receipt_id);
        $this->assertNull($cash_flow->invoice_id);
        $this->assertSame(1234, $cash_flow->amount);
        $this->assertSame('in', $cash_flow->direction);
        $this->assertSame('test description', $cash_flow->description);
        $this->assertSame('2016-01-01', $cash_flow->flow_date);
        $this->assertTrue((bool) $cash_flow->cashless);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_document_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => 'xxx',
            'document_type' => 'receipt',
            'amount' => 12.34,
            'direction' => 'in',
            'description' => 'test description',
            'cashless' => 0,
        ]);

        $this->verifyValidationResponse(['document_id'], ['amount', 'direction', 'description']);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_document_type()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'testabc',
            'amount' => 12.34,
            'direction' => 'in',
            'description' => 'test description',
        ]);

        $this->verifyValidationResponse(
            ['document_type'],
            ['document_id', 'amount', 'direction', 'description']
        );
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_amount()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 'abc',
            'direction' => 'in',
            'description' => 'test description',
        ]);

        $this->verifyValidationResponse(['amount'], ['receipt_id', 'direction', 'description']);
    }

    /** @test */
    public function store_it_returns_validation_error_no_valid_flow_date()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 100,
            'direction' => 'in',
            'description' => 'test description',
            'flow_date' => 'no_valid_date',
        ]);

        $this->verifyValidationResponse(
            ['flow_date'],
            ['amount', 'receipt_id', 'direction', 'description']
        );
    }

    /** @test */
    public function store_it_returns_validation_error_no_valid_cashless()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 100,
            'direction' => 'in',
            'description' => 'test description',
            'cashless' => 'no_valid_boolean',
        ]);

        $this->verifyValidationResponse(
            ['cashless'],
            ['amount', 'receipt_id', 'direction', 'description']
        );
    }

    /** @test */
    public function store_it_returns_validation_error_with_too_small_amount()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 0,
            'direction' => 'in',
            'description' => 'test description',
        ]);

        $this->verifyValidationResponse(['amount'], ['receipt_id', 'direction', 'description']);
    }

    /** @test */
    public function store_it_returns_validation_error_with_too_large_amount()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 10000000,
            'direction' => 'in',
            'description' => 'test description',
        ]);

        $this->verifyValidationResponse(['amount'], ['receipt_id', 'direction', 'description']);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_direction()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 12.34,
            'direction' => 'test',
            'description' => 'test description',
        ]);

        $this->verifyValidationResponse(['direction'], ['receipt_id', 'amount', 'description']);
    }

    /** @test */
    public function store_it_returns_validation_error_with_deleted_invoice()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create(['company_id' => $company->id]);
        $invoice->delete();

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $invoice->id,
            'document_type' => 'invoice',
            'amount' => 12.34,
            'direction' => 'in',
            'description' => 'test description',
            'flow_date' => '2016-01-01',
            'cashless' => 1,
        ]);

        $this->verifyValidationResponse(['document_id'], ['direction', 'receipt_id', 'amount', 'description']);
    }

    /** @test */
    public function store_it_saves_valid_data_with_receipt_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);
        $count = CashFlow::count();

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 12.34,
            'direction' => 'in',
            'description' => 'test description',
            'flow_date' => '2016-01-01',
            'cashless' => 1,
        ]);

        $this->assertEquals($count + 1, CashFlow::count());

        $cash_flow = CashFlow::latest('id')->first();

        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($receipt->id, $cash_flow->receipt_id);
        $this->assertNull($cash_flow->invoice_id);
        $this->assertSame(1234, $cash_flow->amount);
        $this->assertSame('in', $cash_flow->direction);
        $this->assertSame('test description', $cash_flow->description);
        $this->assertSame('2016-01-01', $cash_flow->flow_date);
        $this->assertTrue((bool) $cash_flow->cashless);
    }

    /** @test */
    public function store_it_saves_valid_data_with_invoice_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $invoice = factory(Invoice::class)->create(['company_id' => $company->id]);
        $count = CashFlow::count();

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $invoice->id,
            'document_type' => 'invoice',
            'amount' => 12.34,
            'direction' => 'in',
            'description' => 'test description',
            'flow_date' => '2016-01-01',
            'cashless' => 1,
        ]);

        $this->assertEquals($count + 1, CashFlow::count());

        $cash_flow = CashFlow::latest('id')->first();

        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($invoice->id, $cash_flow->invoice_id);
        $this->assertNull($cash_flow->receipt_id);
        $this->assertSame(1234, $cash_flow->amount);
        $this->assertSame('in', $cash_flow->direction);
        $this->assertSame('test description', $cash_flow->description);
        $this->assertSame('2016-01-01', $cash_flow->flow_date);
        $this->assertTrue((bool) $cash_flow->cashless);
    }

    /** @test */
    public function store_it_saves_valid_data_without_receipt()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $count = CashFlow::count();

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'amount' => 12.34,
            'direction' => 'final',
            'description' => 'test description',
            'flow_date' => '2016-01-01',
            'cashless' => 0,
        ]);

        $this->assertEquals($count + 1, CashFlow::count());

        $cash_flow = CashFlow::latest('id')->first();

        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame(1234, $cash_flow->amount);
        $this->assertSame('final', $cash_flow->direction);
        $this->assertSame('test description', $cash_flow->description);
        $this->assertSame('2016-01-01', $cash_flow->flow_date);
        $this->assertFalse((bool) $cash_flow->cashless);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_company_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id + 10]);

        $this->post('/cash-flows?selected_company_id=' . $company->id, [
            'document_id' => $receipt->id,
            'document_type' => 'receipt',
            'amount' => 12.14,
            'direction' => 'in',
            'description' => 'test description',
        ]);

        $this->verifyValidationResponse(['document_id'], ['amount', 'direction', 'description']);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_success_table_when_cashless_0()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/cach-flow.pdf');
        $text_file = storage_path('tests/cach-flow.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $cash_flow = collect([
            factory(CashFlow::class)->create([
                'company_id' => $company->id,
                'user_id' => $this->user->id,
                'direction' => 'in',
                'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
                'description' => 'Sample description',
            ]),
        ]);

        $cash_flow = $cash_flow->push(factory(CashFlow::class)->create([
            'receipt_id' => null,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'direction' => 'in',
            'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
            'description' => 'Sample description',
        ]));

        $this->normalizeDataForPdf($company, $cash_flow);

        //report
        $cash_flow_query = CashFlow::inCompany($company)
            ->where('user_id', $this->user->id)
            ->whereDate('flow_date', '2017-01-01');
        $cash_flows_initial_sum =
            (clone $cash_flow_query)->where('direction', 'initial')->sum('amount');
        $cash_flows_in_sum = (clone $cash_flow_query)->where('direction', 'in')->sum('amount');
        $cash_flows_out_sum = (clone $cash_flow_query)->where('direction', 'out')->sum('amount');
        $cash_flows_final_sum =
            (clone $cash_flow_query)->where('direction', 'final')->sum('amount');
        $final_sum = $cash_flows_initial_sum + $cash_flows_in_sum - $cash_flows_out_sum;

        $array = [
            'Operacje kasowe - Gotówkowe',
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Statystyki:',
            'Stan początkowy:',
            denormalize_price($cash_flows_initial_sum) . 'zł',
            'Stan końcowy:',
            denormalize_price($cash_flows_final_sum) . 'zł',
            'Kasa wydała:',
            denormalize_price($cash_flows_out_sum) . 'zł',
            'Kasa przyjęła:',
            denormalize_price($cash_flows_in_sum) . 'zł',
            'Wyliczone:',
            denormalize_price($final_sum) . 'zł',
            'Parametry',
            'Data:',
            '2017-01-01',
            'Wystawiający:',
            $this->user->first_name . ' ' . $this->user->last_name,
            'Lp.',
            'Nr dokumentu',
            'Nr transakcji',
            'Wartość',
            'Typ',
            'Opis',
            'Utworzono',
            //row 1
            1,
            $cash_flow[0]->receipt->number,
            $cash_flow[0]->receipt->transaction_number,
            denormalize_price($cash_flow[0]->amount),
            'Kasa przyjęła',
            $cash_flow[0]->description,
            $cash_flow[0]->flow_date,
            //row2
            2,
            denormalize_price($cash_flow[1]->amount),
            'Kasa przyjęła',
            $cash_flow[1]->description,
            $cash_flow[1]->flow_date,
        ];

        ob_start();
        $this->get('/cash-flows/pdf?selected_company_id=' . $company->id .
            '&date=2017-01-01&cashless=0&user_id=' . $this->user->id)
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $txt_content = file_get_contents($text_file);
            $this->assertContainsOrdered($array, $txt_content);
            // make sure no balanced info is here
            $this->assertStringNotContainsString('wydruk zbilansowany', $txt_content);
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_success_table_when_cashless_1()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/cach-flow.pdf');
        $text_file = storage_path('tests/cach-flow.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $cash_flow = collect([
            factory(CashFlow::class)->create([
                'company_id' => $company->id,
                'user_id' => $this->user->id,
                'direction' => 'in',
                'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
                'description' => 'Sample description',
                'cashless' => 1,
            ]),
        ]);

        $cash_flow = $cash_flow->push(factory(CashFlow::class)->create([
            'receipt_id' => null,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'direction' => 'in',
            'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
            'description' => 'Sample description',
            'cashless' => 1,
        ]));

        $this->normalizeDataForPdf($company, $cash_flow);

        //report
        $cash_flow_query = CashFlow::inCompany($company)
            ->where('user_id', $this->user->id)
            ->whereDate('flow_date', '2017-01-01');
        $cash_flows_initial_sum =
            (clone $cash_flow_query)->where('direction', 'initial')->sum('amount');
        $cash_flows_in_sum = (clone $cash_flow_query)->where('direction', 'in')->sum('amount');
        $cash_flows_out_sum = (clone $cash_flow_query)->where('direction', 'out')->sum('amount');
        $cash_flows_final_sum =
            (clone $cash_flow_query)->where('direction', 'final')->sum('amount');
        $final_sum = $cash_flows_initial_sum + $cash_flows_in_sum - $cash_flows_out_sum;

        $array = [
            'Operacje kasowe - Bezgotówkowe',
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Statystyki:',
            'Stan początkowy:',
            denormalize_price($cash_flows_initial_sum) . 'zł',
            'Stan końcowy:',
            denormalize_price($cash_flows_final_sum) . 'zł',
            'Kasa wydała:',
            denormalize_price($cash_flows_out_sum) . 'zł',
            'Kasa przyjęła:',
            denormalize_price($cash_flows_in_sum) . 'zł',
            'Wyliczone:',
            denormalize_price($final_sum) . 'zł',
            'Parametry',
            'Data:',
            '2017-01-01',
            'Wystawiający:',
            $this->user->first_name . ' ' . $this->user->last_name,
            'Lp.',
            'Nr dokumentu',
            'Nr transakcji',
            'Wartość',
            'Typ',
            'Opis',
            'Utworzono',
            //row 1
            1,
            $cash_flow[0]->receipt->number,
            $cash_flow[0]->receipt->transaction_number,
            denormalize_price($cash_flow[0]->amount),
            'Kasa przyjęła',
            $cash_flow[0]->description,
            $cash_flow[0]->flow_date,
            //row2
            2,
            denormalize_price($cash_flow[1]->amount),
            'Kasa przyjęła',
            $cash_flow[1]->description,
            $cash_flow[1]->flow_date,
        ];

        ob_start();
        $this->get('/cash-flows/pdf?selected_company_id=' . $company->id .
            '&date=2017-01-01&cashless=1&user_id=' . $this->user->id)
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $txt_content = file_get_contents($text_file);
            $this->assertContainsOrdered($array, $txt_content);
            // make sure no balanced info is here
            $this->assertStringNotContainsString('wydruk zbilansowany', $txt_content);
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_success_table_when_cashless_0_when_balanced()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/cach-flow.pdf');
        $text_file = storage_path('tests/cach-flow.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $cash_flow = $this->getReadyToBalanceCashFlows($company, true);

        $this->normalizeDataForPdf($company, $cash_flow);

        //report
        $cash_flow_query = CashFlow::inCompany($company)
            ->where('user_id', $this->user->id)
            ->whereDate('flow_date', Carbon::now()->toDateString());
        $cash_flows_initial_sum =
            (clone $cash_flow_query)->where('direction', 'initial')->sum('amount');
        $cash_flows_in_sum = (clone $cash_flow_query)->where('direction', 'in')->sum('amount');
        $cash_flows_out_sum = (clone $cash_flow_query)->where('direction', 'out')->sum('amount');
        $cash_flows_final_sum =
            (clone $cash_flow_query)->where('direction', 'final')->sum('amount');
        $final_sum = $cash_flows_initial_sum + $cash_flows_in_sum - $cash_flows_out_sum;

        $array = [
            'Operacje kasowe - Gotówkowe',
            'wydruk zbilansowany',
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Statystyki:',
            'Stan początkowy:',
            denormalize_price($cash_flows_initial_sum) . 'zł',
            'Stan końcowy:',
            denormalize_price($cash_flows_final_sum) . 'zł',
            'Kasa wydała:',
            denormalize_price($cash_flows_out_sum) . 'zł',
            'Kasa przyjęła:',
            denormalize_price($cash_flows_in_sum) . 'zł',
            'Wyliczone:',
            denormalize_price($final_sum) . 'zł',
            'Parametry',
            'Data:',
            Carbon::now()->toDateString(),
            'Wystawiający:',
            $this->user->first_name . ' ' . $this->user->last_name,
            'Lp.',
            'Nr dokumentu',
            'Nr transakcji',
            'Wartość',
            'Typ',
            'Opis',
            'Utworzono',
        ];

        $array = $this->addCashFlowsToExpectedTable($array, $cash_flow);

        ob_start();
        $this->get('/cash-flows/pdf?selected_company_id=' . $company->id .
            '&date=' . Carbon::now()->toDateString() . '&cashless=0&user_id=' . $this->user->id .
            '&balanced=1')
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $this->assertContainsOrdered($array, file_get_contents($text_file));
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_success_table_when_cashless_0_when_non_balanced()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/cach-flow.pdf');
        $text_file = storage_path('tests/cach-flow.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $cash_flow = $this->getReadyToBalanceCashFlows($company, false);

        $this->normalizeDataForPdf($company, $cash_flow);

        //report
        $cash_flow_query = CashFlow::inCompany($company)
            ->where('user_id', $this->user->id)
            ->whereDate('flow_date', Carbon::now()->toDateString());
        $cash_flows_initial_sum =
            (clone $cash_flow_query)->where('direction', 'initial')->sum('amount');
        $cash_flows_in_sum = (clone $cash_flow_query)->where('direction', 'in')->sum('amount');
        $cash_flows_out_sum = (clone $cash_flow_query)->where('direction', 'out')->sum('amount');
        $cash_flows_final_sum =
            (clone $cash_flow_query)->where('direction', 'final')->sum('amount');
        $final_sum = $cash_flows_initial_sum + $cash_flows_in_sum - $cash_flows_out_sum;

        $array = [
            'Operacje kasowe - Gotówkowe',
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Statystyki:',
            'Stan początkowy:',
            denormalize_price($cash_flows_initial_sum) . 'zł',
            'Stan końcowy:',
            denormalize_price($cash_flows_final_sum) . 'zł',
            'Kasa wydała:',
            denormalize_price($cash_flows_out_sum) . 'zł',
            'Kasa przyjęła:',
            denormalize_price($cash_flows_in_sum) . 'zł',
            'Wyliczone:',
            denormalize_price($final_sum) . 'zł',
            'Parametry',
            'Data:',
            Carbon::now()->toDateString(),
            'Wystawiający:',
            $this->user->first_name . ' ' . $this->user->last_name,
            'Lp.',
            'Nr dokumentu',
            'Nr transakcji',
            'Wartość',
            'Typ',
            'Opis',
            'Utworzono',
        ];

        $array = $this->addCashFlowsToExpectedTable($array, $cash_flow);

        ob_start();
        $this->get('/cash-flows/pdf?selected_company_id=' . $company->id .
            '&date=' . Carbon::now()->toDateString() . '&cashless=0&user_id=' . $this->user->id .
            '&balanced=0')
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $txt_content = file_get_contents($text_file);
            $this->assertContainsOrdered($array, $txt_content);
            // make sure no balanced info is here
            $this->assertStringNotContainsString('wydruk zbilansowany', $txt_content);
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_item_success_with_receipt_table()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/cash-flow-item.pdf');
        $text_file = storage_path('tests/cash-flow-item.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $cash_flow = factory(CashFlow::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'direction' => 'in',
            'description' => 'Sample description',
            'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
        ]);

        $this->normalizeDataForPdf($company, $cash_flow);

        $array = [
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Parametry',
            'Data:',
            $cash_flow[0]->flow_date,
            'Wystawiający:',
            $this->user->first_name . ' ' . $this->user->last_name,
            'Nr dokumentu',
            'Nr transakcji',
            'Wartość',
            'Typ',
            'Opis',
            'Utworzono',
            $cash_flow[0]->receipt->number,
            $cash_flow[0]->receipt->transaction_number,
            denormalize_price($cash_flow[0]->amount),
            'Kasa przyjęła',
            $cash_flow[0]->description,
            $cash_flow[0]->flow_date,
        ];

        ob_start();
        $this->get('/cash-flows/' . $cash_flow[0]->id . '/pdf?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $this->assertContainsOrdered($array, file_get_contents($text_file));
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_item_success_without_receipt_table()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/cash-flow-item.pdf');
        $text_file = storage_path('tests/cash-flow-item.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $cash_flow = factory(CashFlow::class, 2)->create([
            'receipt_id' => null,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'direction' => 'in',
            'description' => 'Sample description',
            'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
        ]);

        $this->normalizeDataForPdf($company, $cash_flow);

        $array = [
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Parametry',
            'Data:',
            '2017-01-01',
            'Wystawiający:',
            $this->user->first_name . ' ' . $this->user->last_name,
            'Nr dokumentu',
            'Nr transakcji',
            'Wartość',
            'Typ',
            'Opis',
            'Utworzono',
            denormalize_price($cash_flow[0]->amount),
            'Kasa przyjęła',
            $cash_flow[0]->description,
            $cash_flow[0]->flow_date,
        ];

        ob_start();
        $this->get('/cash-flows/' . $cash_flow[0]->id . '/pdf?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $this->assertContainsOrdered($array, file_get_contents($text_file));
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_success_table_with_invoice()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/cach-flow.pdf');
        $text_file = storage_path('tests/cach-flow.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $cash_flow = collect([
            factory(CashFlow::class)->create([
                'company_id' => $company->id,
                'user_id' => $this->user->id,
                'direction' => 'in',
                'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
                'description' => 'Sample description',
                'invoice_id' => factory(Invoice::class)->create([
                    'company_id' => $company->id,
                ])->id,
            ]),
        ]);

        $cash_flow = $cash_flow->push(factory(CashFlow::class)->create([
            'receipt_id' => null,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'direction' => 'in',
            'flow_date' => Carbon::create(2017, 1, 1)->toDateString(),
            'description' => 'Sample description',
        ]));

        $this->normalizeDataForPdf($company, $cash_flow);

        //report
        $cash_flow_query = CashFlow::inCompany($company)
            ->where('user_id', $this->user->id)
            ->whereDate('flow_date', '2017-01-01');
        $cash_flows_initial_sum =
            (clone $cash_flow_query)->where('direction', 'initial')->sum('amount');
        $cash_flows_in_sum = (clone $cash_flow_query)->where('direction', 'in')->sum('amount');
        $cash_flows_out_sum = (clone $cash_flow_query)->where('direction', 'out')->sum('amount');
        $cash_flows_final_sum =
            (clone $cash_flow_query)->where('direction', 'final')->sum('amount');
        $final_sum = $cash_flows_initial_sum + $cash_flows_in_sum - $cash_flows_out_sum;

        $array = [
            'Firma:',
            'Nr dokumentu',
            $cash_flow[0]->receipt->number,
            $cash_flow[0]->invoice->number,
        ];

        ob_start();
        $this->get('/cash-flows/pdf?selected_company_id=' . $company->id .
            '&date=2017-01-01&cashless=0&user_id=' . $this->user->id)
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $this->assertContainsOrdered($array, file_get_contents($text_file));
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /** @test */
    public function update_valid_data_with_true_cashless()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $cash_flow = factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'cashless' => 0,
            'amount' => 1234,
            'direction' => CashFlow::DIRECTION_IN,
        ]);

        $this->put('cash-flows/' . $cash_flow->id . '?selected_company_id=' . $company->id, [
            'cashless' => 1,
        ])->seeStatusCode(200)
            ->isJson();

        $cash_flow_fresh = $cash_flow->fresh();

        $this->assertSame($cash_flow->id, $cash_flow_fresh->id);
        $this->assertSame($cash_flow->company_id, $cash_flow_fresh->company_id);
        $this->assertSame($cash_flow->user_id, $cash_flow_fresh->user_id);
        $this->assertSame($cash_flow->receipt_id, $cash_flow_fresh->receipt_id);
        $this->assertSame(1234, $cash_flow_fresh->amount);
        $this->assertSame($cash_flow->direction, $cash_flow_fresh->direction);
        $this->assertSame($cash_flow->description, $cash_flow_fresh->description);
        $this->assertSame($cash_flow->flow_date, $cash_flow_fresh->flow_date);
        $this->assertSame(1, $cash_flow_fresh->cashless);
        $this->assertSame($cash_flow->flow_date, $cash_flow_fresh->flow_date);
    }

    /** @test */
    public function update_valid_data_with_false_cashless()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $cash_flow = factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'cashless' => 1,
            'amount' => 1234,
            'direction' => CashFlow::DIRECTION_IN,
        ]);

        $this->put('cash-flows/' . $cash_flow->id . '?selected_company_id=' . $company->id, [
            'cashless' => 0,
        ])->seeStatusCode(200)
            ->isJson();

        $cash_flow_fresh = $cash_flow->fresh();

        $this->assertSame($cash_flow->id, $cash_flow_fresh->id);
        $this->assertSame($cash_flow->company_id, $cash_flow_fresh->company_id);
        $this->assertSame($cash_flow->user_id, $cash_flow_fresh->user_id);
        $this->assertSame($cash_flow->receipt_id, $cash_flow_fresh->receipt_id);
        $this->assertSame(1234, $cash_flow_fresh->amount);
        $this->assertSame($cash_flow->direction, $cash_flow_fresh->direction);
        $this->assertSame($cash_flow->description, $cash_flow_fresh->description);
        $this->assertSame($cash_flow->flow_date, $cash_flow_fresh->flow_date);
        $this->assertSame(0, $cash_flow_fresh->cashless);
        $this->assertSame($cash_flow->flow_date, $cash_flow_fresh->flow_date);
    }

    /** @test */
    public function update_with_invalid_cashless()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $cash_flow = factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'cashless' => 1,
            'amount' => 1234,
            'direction' => CashFlow::DIRECTION_IN,
        ]);

        $this->put('cash-flows/' . $cash_flow->id . '?selected_company_id=' . $company->id, [
            'cashless' => 4,
        ])->seeStatusCode(422)
            ->isJson();
    }

    /** @test */
    public function update_other_user_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $cash_flow = factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'user_id' => ($this->user->id + 100),
            'cashless' => 1,
            'amount' => 1234,
            'direction' => CashFlow::DIRECTION_IN,
        ]);

        $this->put('cash-flows/' . $cash_flow->id . '?selected_company_id=' . $company->id, [
            'cashless' => 1,
        ])->seeStatusCode(404);
    }

    /** @test */
    public function update_data_in_other_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $cash_flow = factory(CashFlow::class)->create([
            'company_id' => ($company->id + 100),
            'user_id' => $this->user->id,
            'cashless' => 1,
            'amount' => 1234,
            'direction' => CashFlow::DIRECTION_IN,
        ]);

        $this->put('cash-flows/' . $cash_flow->id . '?selected_company_id=' . $company->id, [
            'cashless' => 1,
        ])->seeStatusCode(404)
            ->isJson();
    }

    protected function getReadyToBalanceCashFlows(Company $company, $get_balanced = true)
    {
        $receipt = factory(Receipt::class)->create();
        $invoices = factory(Invoice::class, 3)->create();

        $cash_flow_initial = factory(CashFlow::class)->create([
            'receipt_id' => $receipt->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 159,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_INITIAL,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 1',
        ]);

        $cash_flow_1 = factory(CashFlow::class)->create([
            'receipt_id' => $receipt->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 111,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_IN,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 2',
        ]);

        $cash_flow_2 = factory(CashFlow::class)->create([
            'receipt_id' => $receipt->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 222,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_IN,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 3',
        ]);

        $cash_flow_3 = factory(CashFlow::class)->create([
            'receipt_id' => $receipt->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 55,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_OUT,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 4',
        ]);

        $cash_flow_final = factory(CashFlow::class)->create([
            'receipt_id' => $receipt->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 37,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_FINAL,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 5',
        ]);

        $cash_flow_4 = factory(CashFlow::class)->create([
            'receipt_id' => $receipt->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 57,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_OUT,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 6',
        ]);

        $cash_flow_5 = factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 712,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_OUT,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 7',
        ]);

        $cash_flow_6 = factory(CashFlow::class)->create([
            'receipt_id' => null,
            'invoice_id' => $invoices[1]->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 217,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_IN,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 8',
        ]);

        $cash_flow_7 = factory(CashFlow::class)->create([
            'receipt_id' => null,
            'invoice_id' => $invoices[0]->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 345,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_IN,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 9',
        ]);

        $cash_flow_8 = factory(CashFlow::class)->create([
            'receipt_id' => null,
            'invoice_id' => $invoices[1]->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 513,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_OUT,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 10',
        ]);

        $cash_flow_9 = factory(CashFlow::class)->create([
            'receipt_id' => null,
            'invoice_id' => $invoices[2]->id,
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 1923,
            'flow_date' => Carbon::now()->toDateString(),
            'direction' => CashFlow::DIRECTION_IN,
            'cashless' => 0,
            'description' => 'Sample cash FLOW desc 11',
        ]);

        if (! $get_balanced) {
            return collect([
                $cash_flow_initial,
                $cash_flow_1,
                $cash_flow_2,
                $cash_flow_3,
                $cash_flow_final,
                $cash_flow_4,
                $cash_flow_5,
                $cash_flow_6,
                $cash_flow_7,
                $cash_flow_8,
                $cash_flow_9,
            ]);
        }

        $cash_flow_balanced = $cash_flow_1;
        $cash_flow_balanced->amount =
            $cash_flow_1->amount + $cash_flow_2->amount - $cash_flow_3->amount -
            $cash_flow_4->amount;
        $cash_flow_balanced->direction = CashFlow::DIRECTION_IN;

        $cash_flow_balanced_2 = $cash_flow_6;
        $cash_flow_balanced_2->amount = abs($cash_flow_8->amount - $cash_flow_6->amount);
        $cash_flow_balanced_2->direction = CashFlow::DIRECTION_OUT;

        // set balanced to valid values
        $cash_flow_initial->balanced = false;
        $cash_flow_balanced->balanced = true;
        $cash_flow_final->balanced = false;
        $cash_flow_5->balanced = false;
        $cash_flow_balanced_2->balanced = true;
        $cash_flow_7->balanced = false;
        $cash_flow_9->balanced = false;

        return (collect(
            [
                $cash_flow_initial,
                $cash_flow_balanced,
                $cash_flow_final,
                $cash_flow_5,
                $cash_flow_balanced_2,
                $cash_flow_7,
                $cash_flow_9,
            ]
        ));
    }

    protected function addCashFlowsToExpectedTable(array $table, Collection $cash_flows)
    {
        // now in loop we add rows for each cash flow
        foreach ($cash_flows as $key => $single_cash_flow) {

            // we reload relationships after normalization in normalizeDataForPdf
            $single_cash_flow = $single_cash_flow->load('receipt', 'invoice');

            $table[] = $key + 1;
            if ($single_cash_flow->receipt) {
                $table[] = $single_cash_flow->receipt->number;
            } elseif ($single_cash_flow->invoice) {
                $table[] = $single_cash_flow->invoice->number;
            }
            if ($single_cash_flow->receipt) {
                $table[] = $single_cash_flow->receipt->transaction_number;
            }

            $table[] = round($single_cash_flow->amount / 100, 2) . 'zł';

            if ($single_cash_flow->direction == CashFlow::DIRECTION_INITIAL) {
                $table[] = 'Stan początkowy';
            } elseif ($single_cash_flow->direction == CashFlow::DIRECTION_IN) {
                $table[] = 'Kasa przyjęła';
            } elseif ($single_cash_flow->direction == CashFlow::DIRECTION_OUT) {
                $table[] = 'Kasa wydała';
            } else {
                $table[] = 'Stan końcowy';
            }
            $table[] = $single_cash_flow->description;
            $table[] = $single_cash_flow->flow_date;
        }

        return $table;
    }

    /**
     * Normalize data. This is done only for pdftotext to resolve problem with some characters be
     * converted into ligatures (for example ff).
     *
     * @param Company $company
     * @param Collection $cash_flows
     */
    protected function normalizeDataForPdf(Company $company, Collection $cash_flows)
    {
        $this->user->first_name = 'Marcin';
        $this->user->last_name = 'Iksinski';
        $this->user->save();

        $company->name = 'Sample company name';
        $company->save();

        foreach ($cash_flows as $k => $v) {
            if ($cash_flows[$k]->receipt) {
                $cash_flows[$k]->receipt->number = mt_rand(2, 300) . ' Number for ' . $k;
                $cash_flows[$k]->receipt->transaction_number =
                    mt_rand(2, 300) . ' trans number for ' . $k;
                $cash_flows[$k]->receipt->save();
            }

            if ($cash_flows[$k]->invoice) {
                $cash_flows[$k]->invoice->number = mt_rand(2, 300) . ' Invno for ' . $k;
                $cash_flows[$k]->invoice->save();
            }
        }
    }
}
