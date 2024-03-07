<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers;

use Carbon\Carbon;
use App\Models\Db\CashFlow;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class CashFlowReportControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_data_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 4)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    'cash_initial_sum',
                    'cash_in_sum',
                    'cash_out_sum',
                    'cash_final_sum',
                    'calc_final_sum',
                    'equals_final_sum',
                ],
            ])->isJson();
    }

    /** @test */
    public function index_response_all_correct_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 2)->create([
            'company_id' => $company->id + 10,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 20045,
            'direction' => 'initial',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 100022,
            'direction' => 'in',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 98031,
            'direction' => 'out',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 22036,
            'direction' => 'final',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $cash_flows_count = CashFlow::inCompany($company)->count();
        $this->assertEquals(4, $cash_flows_count);

        $this->assertEquals(200.45, $data['cash_initial_sum']);
        $this->assertEquals(1000.22, $data['cash_in_sum']);
        $this->assertEquals(980.31, $data['cash_out_sum']);
        $this->assertEquals(220.36, $data['cash_final_sum']);
        $this->assertEquals(220.36, $data['calc_final_sum']);
        $this->assertEquals(true, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_without_initial_amount()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class)->create([
            'amount' => 100024,
            'direction' => 'in',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 98011,
            'direction' => 'out',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 22075,
            'direction' => 'final',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(0, $data['cash_initial_sum']);
        $this->assertEquals(1000.24, $data['cash_in_sum']);
        $this->assertEquals(980.11, $data['cash_out_sum']);
        $this->assertEquals(220.75, $data['cash_final_sum']);
        $this->assertEquals(20.13, $data['calc_final_sum']);
        $this->assertEquals(false, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_without_final_sum()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class)->create([
            'amount' => 20015,
            'direction' => 'initial',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 100016,
            'direction' => 'in',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 98017,
            'direction' => 'out',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(200.15, $data['cash_initial_sum']);
        $this->assertEquals(1000.16, $data['cash_in_sum']);
        $this->assertEquals(980.17, $data['cash_out_sum']);
        $this->assertEquals(0, $data['cash_final_sum']);
        $this->assertEquals(220.14, $data['calc_final_sum']);
        $this->assertEquals(false, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_without_amount_in()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class)->create([
            'amount' => 80012,
            'direction' => 'initial',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 75014,
            'direction' => 'out',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 22025,
            'direction' => 'final',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(800.12, $data['cash_initial_sum']);
        $this->assertEquals(0, $data['cash_in_sum']);
        $this->assertEquals(750.14, $data['cash_out_sum']);
        $this->assertEquals(220.25, $data['cash_final_sum']);
        $this->assertEquals(49.98, $data['calc_final_sum']);
        $this->assertEquals(false, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_without_amount_out()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class)->create([
            'amount' => 90054,
            'direction' => 'initial',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 88041,
            'direction' => 'in',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 22095,
            'direction' => 'final',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(900.54, $data['cash_initial_sum']);
        $this->assertEquals(880.41, $data['cash_in_sum']);
        $this->assertEquals(0, $data['cash_out_sum']);
        $this->assertEquals(220.95, $data['cash_final_sum']);
        $this->assertEquals(1780.95, $data['calc_final_sum']);
        $this->assertEquals(false, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_without_amount_in_and_out()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class)->create([
            'amount' => 20054,
            'direction' => 'initial',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 20054,
            'direction' => 'final',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(200.54, $data['cash_initial_sum']);
        $this->assertEquals(0, $data['cash_in_sum']);
        $this->assertEquals(0, $data['cash_out_sum']);
        $this->assertEquals(200.54, $data['cash_final_sum']);
        $this->assertEquals(200.54, $data['calc_final_sum']);
        $this->assertEquals(true, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_without_initial_and_final_amount()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class)->create([
            'amount' => 100052,
            'direction' => 'in',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'amount' => 98087,
            'direction' => 'out',
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(0, $data['cash_initial_sum']);
        $this->assertEquals(1000.52, $data['cash_in_sum']);
        $this->assertEquals(980.87, $data['cash_out_sum']);
        $this->assertEquals(0, $data['cash_final_sum']);
        $this->assertEquals(19.65, $data['calc_final_sum']);
        $this->assertEquals(false, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_with_date_input()
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
            'flow_date' => Carbon::now()->subMonth()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 10059,
            'direction' => 'initial',
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(100.59, $data['cash_initial_sum']);
        $this->assertEquals(0, $data['cash_in_sum']);
        $this->assertEquals(0, $data['cash_out_sum']);
        $this->assertEquals(0, $data['cash_final_sum']);
        $this->assertEquals(100.59, $data['calc_final_sum']);
        $this->assertEquals(false, $data['equals_final_sum']);
    }

    /** @test */
    public function index_response_with_user_id_input()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        CashFlow::whereRaw('1 = 1')->delete();
        factory(CashFlow::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id + 10,
            'flow_date' => Carbon::now()->toDateString(),
        ]);
        factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'amount' => 10084,
            'direction' => 'initial',
            'flow_date' => Carbon::now()->toDateString(),
        ]);

        $this->get(
            '/reports/cash-flows?selected_company_id=' . $company->id
            . '&user_id=' . $this->user->id
            . '&date=' . Carbon::now()->toDateString()
            . '&cashless=' . 0
        )
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(100.84, $data['cash_initial_sum']);
        $this->assertEquals(0, $data['cash_in_sum']);
        $this->assertEquals(0, $data['cash_out_sum']);
        $this->assertEquals(0, $data['cash_final_sum']);
        $this->assertEquals(100.84, $data['calc_final_sum']);
        $this->assertEquals(false, $data['equals_final_sum']);
    }
}
