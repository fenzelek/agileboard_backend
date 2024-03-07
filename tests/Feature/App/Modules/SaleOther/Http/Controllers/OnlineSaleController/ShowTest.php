<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\OnlineSaleController;

use App\Models\Db\OnlineSaleItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\OnlineSale;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Db\Company;
use App\Models\Db\Invoice;
use Tests\BrowserKitTestCase;

class ShowTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function show_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sales = factory(OnlineSale::class, 2)->create();
        $this->assignOnlineSalesToCompany($online_sales, $company);
        $this->get('online-sales/' . $online_sales[0]->id . '?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function show_online_sale_out_of_company()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $online_sale = factory(OnlineSale::class)->create();
        $this->get('online-sales/' . $online_sale->id . '?selected_company_id=' . $company->id)
            ->assertResponseStatus(404);
    }

    /** @test */
    public function show_response_structure_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $online_sales = factory(OnlineSale::class, 2)->create();
        $online_sale_item = factory(OnlineSaleItem::class)->create();
        $online_sale_item->online_sale_id = $online_sales[0]->id;
        $online_sale_item->save();
        $this->assignOnlineSalesToCompany($online_sales, $company);
        $this->get('online-sales/' . $online_sales[0]->id . '?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'transaction_number',
                    'email',
                    'company_id',
                    'sale_date',
                    'price_net',
                    'price_gross',
                    'vat_sum',
                    'payment_method_id',
                    'created_at',
                    'updated_at',
                    'items' => [
                        'data' => [
                            [
                                'id',
                                'online_sale_id',
                                'company_service_id',
                                'name',
                                'price_net',
                                'price_net_sum',
                                'price_gross',
                                'price_gross_sum',
                                'vat_rate',
                                'vat_rate_id',
                                'vat_sum',
                                'quantity',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function show_response_structure_with_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $online_sale = factory(OnlineSale::class)->create();
        $online_sale->company_id = $company->id;
        $online_sale->save();
        $invoice = factory(Invoice::class)->create();
        $online_sale->invoices()->attach($invoice->id);
        $this->get('/online-sales/' . $online_sale->id . '?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    'invoices' => [
                        'data' => [
                            [
                                'id',
                                'number',
                            ],

                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function show_response_has_correct_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $online_sale = factory(OnlineSale::class)->create();
        $online_sale->company_id = $company->id;
        $online_sale->price_net = 10022;
        $online_sale->price_gross = 20022;
        $online_sale->vat_sum = 2322;
        $online_sale->save();

        $online_sale_item_amount = 2;

        $store_data = [
            [
                'price_net' => 1040,
                'price_net_sum' => 50000,
                'price_gross' => 1110,
                'price_gross_sum' => 33333,
                'vat_sum' => 3333,
            ],
            [
                'price_net' => 3033,
                'price_net_sum' => 30020,
                'price_gross' => 3340,
                'price_gross_sum' => 33399,
                'vat_sum' => 6666,
            ],
        ];

        $online_sale_items = factory(OnlineSaleItem::class, $online_sale_item_amount)->create();
        $iterator = 0;
        foreach ($online_sale_items as $item) {
            $item->update([
                'online_sale_id' => $online_sale->id,
                'price_net' => $store_data[$iterator]['price_net'],
                'price_net_sum' => $store_data[$iterator]['price_net_sum'],
                'price_gross' => $store_data[$iterator]['price_gross'],
                'price_gross_sum' => $store_data[$iterator]['price_gross_sum'],
                'vat_sum' => $store_data[$iterator++]['vat_sum'],
            ]);
        }

        $invoice = factory(Invoice::class)->create();
        $online_sale->invoices()->attach($invoice->id);

        $this->get('online-sales/' . $online_sale->id . '?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];

        $online_sale = $online_sale->fresh();
        $this->assertSame($online_sale->id, $json_data['id']);
        $this->assertSame($online_sale->number, $json_data['number']);
        $this->assertSame($online_sale->transaction_number, $json_data['transaction_number']);
        $this->assertSame($online_sale->email, $json_data['email']);
        $this->assertSame($online_sale->company_id, $json_data['company_id']);
        $this->assertSame($online_sale->sale_date, $json_data['sale_date']);
        $this->assertSame(100.22, $json_data['price_net']);
        $this->assertSame(200.22, $json_data['price_gross']);
        $this->assertSame(23.22, $json_data['vat_sum']);
        $this->assertSame($online_sale->payment_method_id, $json_data['payment_method_id']);
        $this->assertSame($online_sale->created_at->toDateTimeString(), $json_data['created_at']);
        $this->assertSame($online_sale->updated_at->toDateTimeString(), $json_data['updated_at']);

        $invoice_data = $json_data['invoices']['data'];
        $this->assertSame(1, count($invoice_data));
        $this->assertSame($invoice->id, $invoice_data[0]['id']);
        $this->assertEquals($invoice->number, $invoice_data[0]['number']);

        $json_items = $json_data['items']['data'];

        $expectData = [
            [
                'price_net' => 10.40,
                'price_net_sum' => 500,
                'price_gross' => 11.1,
                'price_gross_sum' => 333.33,
                'vat_sum' => 33.33,
            ],
            [
                'price_net' => 30.33,
                'price_net_sum' => 300.20,
                'price_gross' => 33.40,
                'price_gross_sum' => 333.99,
                'vat_sum' => 66.66,
            ],
        ];

        $this->assertSame($online_sale_item_amount, count($json_items));

        for ($i = 0; $i < $online_sale_item_amount; $i++) {
            $this->assertSame($online_sale_items[$i]->id, $json_items[$i]['id']);
            $this->assertSame($online_sale_items[$i]->name, $json_items[$i]['name']);
            $this->assertSame($online_sale->id, $json_items[$i]['online_sale_id']);
            $this->assertSame($online_sale_items[$i]->company_service_id, $json_items[$i]['company_service_id']);
            $this->assertSame($expectData[$i]['price_net'], $json_items[$i]['price_net']);
            $this->assertSame($expectData[$i]['price_net_sum'], $json_items[$i]['price_net_sum']);
            $this->assertSame($expectData[$i]['price_gross'], $json_items[$i]['price_gross']);
            $this->assertSame($expectData[$i]['price_gross_sum'], $json_items[$i]['price_gross_sum']);
            $this->assertSame($online_sale_items[$i]['vat_rate'], $json_items[$i]['vat_rate']);
            $this->assertSame($online_sale_items[$i]['vat_rate_id'], $json_items[$i]['vat_rate_id']);
            $this->assertSame($expectData[$i]['vat_sum'], $json_items[$i]['vat_sum']);
            $this->assertSame($online_sale_items[$i]['quantity'], $json_items[$i]['quantity']);
            $this->assertEquals($online_sale_items[$i]['created_at'], $json_items[$i]['created_at']);
            $this->assertEquals($online_sale_items[$i]['updated_at'], $json_items[$i]['updated_at']);
        }
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $company = $this->createCompanyWithRole(RoleType::OWNER);

        return $company;
    }

    protected function assignOnlineSalesToCompany(Collection $online_sales, Company $company)
    {
        $online_sales->each(function ($online_sale) use ($company) {
            $online_sale->company_id = $company->id;
            $online_sale->save();
        });
    }
}
