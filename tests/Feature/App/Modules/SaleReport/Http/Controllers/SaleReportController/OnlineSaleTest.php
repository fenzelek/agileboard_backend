<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Models\Db\OnlineSale;
use App\Models\Db\Company as ModelCompany;
use App\Models\Db\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Database\Eloquent\Collection;
use Tests\BrowserKitTestCase;

class OnlineSaleTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function report_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->get('/reports/online-sales?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function report_response_structure_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);
        $this->get('/reports/online-sales?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    'price_net_report',
                    'price_gross_report',
                    'vat_sum_report',
                ],
            ]);
    }

    /** @test */
    public function report_response_has_correct_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->storeSamplesInDataBase($company);

        $this->get('/reports/online-sales?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function report_data_was_only_for_indicate_company()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->storeSamplesInDataBase($company);

        $online_sale_other_company = factory(OnlineSale::class)->create();

        $this->get('/reports/online-sales?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function online_sales_data_was_filter_by_email()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $online_sales = $this->storeSamplesInDataBase($company);

        $other_user = factory(User::class)->create();
        $online_sales[0]->email = $other_user->email;
        $online_sales[0]->save();
        $online_sales[1]->email = $other_user->email;
        $online_sales[1]->save();
        $filter_user = $other_user->email;

        $this->get('/reports/online-sales?selected_company_id=' . $company->id . '&email=' .
            $filter_user)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];

        $this->assertSame(11.11, $json_data['price_net_report']);
        $this->assertSame(22.22, $json_data['price_gross_report']);
        $this->assertSame(33.33, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_validate_error_email_()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('/reports/online-sales?selected_company_id=' . $company->id . '&email=' .
            'not_valid_email')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'email',
        ]);
    }

    /** @test */
    public function online_sales_validate_error_filter_date_not_date_format()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $filter_date_start = 'not_date';
        $filter_date_end = '2017-01-01';

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $filter_date_start
            . '&date_end=' . $filter_date_end
        )
            ->assertResponseStatus(422);

        $this->verifyValidationResponse(
            [
                'date_start',
            ],
            [
                'date_end',
            ]
        );
    }

    /** @test */
    public function online_sales_data_was_filter_by_date_long_period()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-01'),
                'date_end' => Carbon::parse('2016-05-01'),
            ];

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];

        $this->reportExpectData();
    }

    /** @test */
    public function online_sales_data_was_filter_by_boundary_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-10 00:00:00'),
                'date_end' => Carbon::parse('2016-03-11 23:59:59'),
            ];

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function online_sales_data_was_filter_by_date_in_time_short_period_after_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-01'),
                'date_end' => Carbon::parse('2016-02-28'),
            ];

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(11.11, $json_data['price_net_report']);
        $this->assertSame(22.22, $json_data['price_gross_report']);
        $this->assertSame(33.33, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_data_was_filter_by_date_in_time_short_period_before_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-02-01'),
                'date_end' => Carbon::parse('2016-03-31'),
            ];

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_data_was_filter_by_cause_only_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-02-01'),
            ];

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_data_was_filter_by_cause_like_start_date_after_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-02-20'),
                'date_end' => Carbon::parse('2016-02-12'),
            ];

        $date_start = isset($expect['date_start']) ? $expect['date_start'] : '';
        $date_end = isset($expect['date_end']) ? $expect['date_end'] : '';
        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0, $json_data['price_net_report']);
        $this->assertSame(0, $json_data['price_gross_report']);
        $this->assertSame(0, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_data_was_filter_by_only_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_end' => Carbon::parse('2016-03-31'),
            ];

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function online_sales_data_was_filter_by_end_date_before_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-11'),
                'date_end' => Carbon::parse('2016-01-01'),
            ];

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0, $json_data['price_net_report']);
        $this->assertSame(0, $json_data['price_gross_report']);
        $this->assertSame(0, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_data_was_filter_by_transaction_number_lower_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->transaction_number = '123456789';
        $online_sales[0]->save();
        $online_sales[1]->transaction_number = '1234567ABC';
        $online_sales[1]->save();
        $online_sales[2]->transaction_number = 'ABCDEFGH';
        $online_sales[2]->save();

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&transaction_number=ABC'
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_data_was_filter_by_transaction_number_upper_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->transaction_number = '123456789';
        $online_sales[0]->save();
        $online_sales[1]->transaction_number = 'AbCdZZZZZZ';
        $online_sales[1]->save();
        $online_sales[2]->transaction_number = 'ZZZZAbCdZZZZ';
        $online_sales[2]->save();

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&transaction_number=AbCd'
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function online_sales_data_was_filter_by_number_as_numeric()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->number = '123456789';
        $online_sales[0]->save();
        $online_sales[1]->number = 'ZZZZZZ1234';
        $online_sales[1]->save();
        $online_sales[2]->number = 'ZZZZAbCdZZZZ';
        $online_sales[2]->save();

        $this->get(
            '/reports/online-sales?selected_company_id=' . $company->id
            . '&number=123'
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(11.11, $json_data['price_net_report']);
        $this->assertSame(22.22, $json_data['price_gross_report']);
        $this->assertSame(33.33, $json_data['vat_sum_report']);
    }

    /** @test */
    public function index_retrieve_sale_filter_by_year_and_month()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2016-03-11');
        $online_sales[2]->save();

        $this->get('/reports/online-sales?selected_company_id=' . $company->id
            . '&year=' . 2016 . '&month=' . 1)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1.01, $json_data['price_net_report']);
        $this->assertSame(2.02, $json_data['price_gross_report']);
        $this->assertSame(3.03, $json_data['vat_sum_report']);
    }

    /** @test */
    public function index_retrieve_sale_filter_by_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2017-03-11');
        $online_sales[2]->save();

        $this->get('/reports/online-sales?selected_company_id=' . $company->id
            . '&year=' . 2016)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(11.11, $json_data['price_net_report']);
        $this->assertSame(22.22, $json_data['price_gross_report']);
        $this->assertSame(33.33, $json_data['vat_sum_report']);
    }

    /** @test */
    public function index_retrieve_sale_filter_by_year_and_data_start()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = $this->storeSamplesInDataBase($company);

        $online_sales[0]->sale_date = Carbon::parse('2016-01-10');
        $online_sales[0]->save();
        $online_sales[1]->sale_date = Carbon::parse('2016-02-11');
        $online_sales[1]->save();
        $online_sales[2]->sale_date = Carbon::parse('2017-03-11');
        $online_sales[2]->save();

        $this->get('/reports/online-sales?selected_company_id=' . $company->id
            . '&year=' . 2016
            . '&date_start=' . '2016-02-01')
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(10.1, $json_data['price_net_report']);
        $this->assertSame(20.2, $json_data['price_gross_report']);
        $this->assertSame(30.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function index_validation_error_invalid_month_and_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('/reports/online-sales?selected_company_id=' . $company->id
            . '&month=' . 'no_integer' . '&year=' . 'no_integer')
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('/reports/online-sales?selected_company_id=' . $company->id
            . '&month=' . 13 . '&year=' . 2051)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('/reports/online-sales?selected_company_id=' . $company->id
            . '&month=' . 0 . '&year=' . 2000)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('/reports/online-sales?selected_company_id=' . $company->id
            . '&month=' . 12)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse(['year']);
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);

        return $company;
    }

    protected function assignOnlineSalesToCompany(Collection $online_sales, ModelCompany $company)
    {
        $online_sales->each(function ($online_sale) use ($company) {
            $online_sale->company_id = $company->id;
            $online_sale->save();
        });
    }

    protected function storeSamplesInDataBase(ModelCompany $company)
    {
        $online_sales = factory(OnlineSale::class, 3)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);
        $online_sales[0]->price_net = 101;
        $online_sales[0]->price_gross = 202;
        $online_sales[0]->vat_sum = 303;
        $online_sales[0]->save();
        $online_sales[1]->price_net = 1010;
        $online_sales[1]->price_gross = 2020;
        $online_sales[1]->vat_sum = 3030;
        $online_sales[1]->save();
        $online_sales[2]->price_net = 10000;
        $online_sales[2]->price_gross = 20000;
        $online_sales[2]->vat_sum = 30000;
        $online_sales[2]->save();

        return $online_sales;
    }

    protected function reportExpectData()
    {
        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(111.11, $json_data['price_net_report']);
        $this->assertSame(222.22, $json_data['price_gross_report']);
        $this->assertSame(333.33, $json_data['vat_sum_report']);
    }
}
