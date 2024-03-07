<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Models\Db\Receipt;
use App\Models\Db\Company as ModelCompany;
use App\Models\Db\User;
use Carbon\Carbon;
use Tests\Feature\App\Modules\SaleOther\Http\Controllers\ReceiptController\ReceiptController;

class ReceiptsTest extends ReceiptController
{
    /** @test */
    public function report_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->get('/reports/receipts?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function report_response_structure_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $this->get('reports/receipts?selected_company_id=' . $company->id)
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

        $this->get('reports/receipts?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function report_data_was_only_for_indicate_company()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->storeSamplesInDataBase($company);

        $receipt_other_company = factory(Receipt::class)->create();

        $this->get('/reports/receipts?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function receipts_data_was_filter_by_user()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $receipts = $this->storeSamplesInDataBase($company);

        $other_user = factory(User::class)->create();
        $receipts[0]->user_id = $other_user->id;
        $receipts[0]->save();
        $receipts[1]->user_id = $other_user->id;
        $receipts[1]->save();
        $filter_user = $other_user->id;

        $this->get('reports/receipts?selected_company_id=' . $company->id . '&user_id=' .
            $filter_user)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];

        $this->assertSame(11.11, $json_data['price_net_report']);
        $this->assertSame(22.22, $json_data['price_gross_report']);
        $this->assertSame(33.33, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_validate_error_user_id_not_integer()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('/reports/receipts?selected_company_id=' . $company->id . '&user_id=' .
            'not_number')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'user_id',
        ]);
    }

    /** @test */
    public function receipts_validate_error_filter_date_not_date_format()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $filter_date_start = 'not_date';
        $filter_date_end = '2017-01-01';

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
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
    public function index_retrieve_receipts_filter_by_year_and_month()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $this->get('/reports/receipts?selected_company_id=' . $company->id
            . '&year=' . 2016 . '&month=' . 1)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1.01, $json_data['price_net_report']);
        $this->assertSame(2.02, $json_data['price_gross_report']);
        $this->assertSame(3.03, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_date_long_period()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-01'),
                'date_end' => Carbon::parse('2016-05-01'),
            ];

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];

        $this->reportExpectData();
    }

    /** @test */
    public function receipts_data_was_filter_by_boundary_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-10 00:00:00'),
                'date_end' => Carbon::parse('2016-03-11 23:59:59'),
            ];

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function receipts_data_was_filter_by_date_in_time_short_period_after_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-01'),
                'date_end' => Carbon::parse('2016-02-28'),
            ];

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(11.11, $json_data['price_net_report']);
        $this->assertSame(22.22, $json_data['price_gross_report']);
        $this->assertSame(33.33, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_date_in_time_short_period_before_limit()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-02-01'),
                'date_end' => Carbon::parse('2016-03-31'),
            ];

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_cause_only_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-02-01'),
            ];

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_cause_like_start_date_after_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-02-20'),
                'date_end' => Carbon::parse('2016-02-12'),
            ];

        $date_start = isset($expect['date_start']) ? $expect['date_start'] : '';
        $date_end = isset($expect['date_end']) ? $expect['date_end'] : '';
        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0, $json_data['price_net_report']);
        $this->assertSame(0, $json_data['price_gross_report']);
        $this->assertSame(0, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_only_end_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_end' => Carbon::parse('2016-03-31'),
            ];

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $this->reportExpectData();
    }

    /** @test */
    public function receipts_data_was_filter_by_end_date_before_start_date()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->sale_date = Carbon::parse('2016-01-10');
        $receipts[0]->save();
        $receipts[1]->sale_date = Carbon::parse('2016-02-11');
        $receipts[1]->save();
        $receipts[2]->sale_date = Carbon::parse('2016-03-11');
        $receipts[2]->save();

        $incoming_data =
            [
                'date_start' => Carbon::parse('2016-01-11'),
                'date_end' => Carbon::parse('2016-01-01'),
            ];

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&date_start=' . $incoming_data['date_start']
            . '&date_end=' . $incoming_data['date_end']
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0, $json_data['price_net_report']);
        $this->assertSame(0, $json_data['price_gross_report']);
        $this->assertSame(0, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_transaction_number_lower_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->transaction_number = '123456789';
        $receipts[0]->save();
        $receipts[1]->transaction_number = '1234567ABC';
        $receipts[1]->save();
        $receipts[2]->transaction_number = 'ABCDEFGH';
        $receipts[2]->save();

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&transaction_number=ABC'
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_transaction_number_upper_letters()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->transaction_number = '123456789';
        $receipts[0]->save();
        $receipts[1]->transaction_number = 'AbCdZZZZZZ';
        $receipts[1]->save();
        $receipts[2]->transaction_number = 'ZZZZAbCdZZZZ';
        $receipts[2]->save();

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&transaction_number=AbCd'
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110.1, $json_data['price_net_report']);
        $this->assertSame(220.2, $json_data['price_gross_report']);
        $this->assertSame(330.3, $json_data['vat_sum_report']);
    }

    /** @test */
    public function receipts_data_was_filter_by_number_as_numeric()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipts = $this->storeSamplesInDataBase($company);

        $receipts[0]->number = '123456789';
        $receipts[0]->save();
        $receipts[1]->number = 'ZZZZZZ1234';
        $receipts[1]->save();
        $receipts[2]->number = 'ZZZZAbCdZZZZ';
        $receipts[2]->save();

        $this->get(
            '/reports/receipts?selected_company_id=' . $company->id
            . '&number=123'
        )->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(11.11, $json_data['price_net_report']);
        $this->assertSame(22.22, $json_data['price_gross_report']);
        $this->assertSame(33.33, $json_data['vat_sum_report']);
    }

    protected function storeSamplesInDataBase(ModelCompany $company)
    {
        $receipts = factory(Receipt::class, 3)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $receipts[0]->price_net = 101;
        $receipts[0]->price_gross = 202;
        $receipts[0]->vat_sum = 303;
        $receipts[0]->save();
        $receipts[1]->price_net = 1010;
        $receipts[1]->price_gross = 2020;
        $receipts[1]->vat_sum = 3030;
        $receipts[1]->save();
        $receipts[2]->price_net = 10000;
        $receipts[2]->price_gross = 20000;
        $receipts[2]->vat_sum = 30000;
        $receipts[2]->save();

        return $receipts;
    }

    protected function reportExpectData()
    {
        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(111.11, $json_data['price_net_report']);
        $this->assertSame(222.22, $json_data['price_gross_report']);
        $this->assertSame(333.33, $json_data['vat_sum_report']);
    }
}
