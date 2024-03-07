<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\ReceiptController;

use App\Models\Db\ReceiptItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Receipt;
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
        $receipts = factory(Receipt::class, 2)->create();
        $this->assignReceiptsToCompany($receipts, $company);
        $this->get('receipts/' . $receipts[0]->id . '?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function show_receipt_out_of_company()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $receipt = factory(Receipt::class)->create();
        $this->get('receipts/' . $receipt->id . '?selected_company_id=' . $company->id)
            ->assertResponseStatus(404);
    }

    /** @test */
    public function show_response_structure_without_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $receipts = factory(Receipt::class, 2)->create();
        $receipt_item = factory(ReceiptItem::class)->create();
        $receipt_item->receipt_id = $receipts[0]->id;
        $receipt_item->save();
        $this->assignReceiptsToCompany($receipts, $company);
        $this->get('receipts/' . $receipts[0]->id . '?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
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
                    'cash_back',
                    'created_at',
                    'updated_at',
                    'items' => [
                        'data' => [
                            [
                                'id',
                                'receipt_id',
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
                                'creator_id',
                                'editor_id',
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

        $receipt = factory(Receipt::class)->create();
        $receipt->company_id = $company->id;
        $receipt->save();
        $invoice = factory(Invoice::class)->create();
        $receipt->invoices()->attach($invoice->id);
        $this->get('/receipts/' . $receipt->id . '?selected_company_id=' . $company->id)
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

        $receipt = factory(Receipt::class)->create();
        $receipt->company_id = $company->id;
        $receipt->price_net = 10022;
        $receipt->price_gross = 20022;
        $receipt->vat_sum = 2322;
        $receipt->cash_back = 4444;
        $receipt->save();

        $receipt_item_amount = 2;

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

        $receipt_items = factory(ReceiptItem::class, $receipt_item_amount)->create();
        $iterator = 0;
        foreach ($receipt_items as $item) {
            $item->update([
                'receipt_id' => $receipt->id,
                'price_net' => $store_data[$iterator]['price_net'],
                'price_net_sum' => $store_data[$iterator]['price_net_sum'],
                'price_gross' => $store_data[$iterator]['price_gross'],
                'price_gross_sum' => $store_data[$iterator]['price_gross_sum'],
                'vat_sum' => $store_data[$iterator++]['vat_sum'],
            ]);
        }

        $invoice = factory(Invoice::class)->create();
        $receipt->invoices()->attach($invoice->id);

        $this->get('receipts/' . $receipt->id . '?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];

        $receipt = $receipt->fresh();
        $this->assertSame($receipt->id, $json_data['id']);
        $this->assertSame($receipt->number, $json_data['number']);
        $this->assertSame($receipt->transaction_number, $json_data['transaction_number']);
        $this->assertSame($receipt->user_id, $json_data['user_id']);
        $this->assertSame($receipt->company_id, $json_data['company_id']);
        $this->assertSame($receipt->sale_date, $json_data['sale_date']);
        $this->assertSame(44.44, $json_data['cash_back']);
        $this->assertSame(100.22, $json_data['price_net']);
        $this->assertSame(200.22, $json_data['price_gross']);
        $this->assertSame(23.22, $json_data['vat_sum']);
        $this->assertSame($receipt->payment_method_id, $json_data['payment_method_id']);
        $this->assertSame($receipt->created_at->toDateTimeString(), $json_data['created_at']);
        $this->assertSame($receipt->updated_at->toDateTimeString(), $json_data['updated_at']);

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

        $this->assertSame($receipt_item_amount, count($json_items));

        for ($i = 0; $i < $receipt_item_amount; $i++) {
            $this->assertSame($receipt_items[$i]->id, $json_items[$i]['id']);
            $this->assertSame($receipt_items[$i]->name, $json_items[$i]['name']);
            $this->assertSame($receipt->id, $json_items[$i]['receipt_id']);
            $this->assertSame($receipt_items[$i]->company_service_id, $json_items[$i]['company_service_id']);
            $this->assertSame($expectData[$i]['price_net'], $json_items[$i]['price_net']);
            $this->assertSame($expectData[$i]['price_net_sum'], $json_items[$i]['price_net_sum']);
            $this->assertSame($expectData[$i]['price_gross'], $json_items[$i]['price_gross']);
            $this->assertSame($expectData[$i]['price_gross_sum'], $json_items[$i]['price_gross_sum']);
            $this->assertSame($receipt_items[$i]['vat_rate'], $json_items[$i]['vat_rate']);
            $this->assertSame($receipt_items[$i]['vat_rate_id'], $json_items[$i]['vat_rate_id']);
            $this->assertSame($expectData[$i]['vat_sum'], $json_items[$i]['vat_sum']);
            $this->assertSame($receipt_items[$i]['quantity'], $json_items[$i]['quantity']);
            $this->assertSame(0, $json_items[$i]['creator_id']);
            $this->assertSame(0, $json_items[$i]['editor_id']);
            $this->assertEquals($receipt_items[$i]['created_at'], $json_items[$i]['created_at']);
            $this->assertEquals($receipt_items[$i]['updated_at'], $json_items[$i]['updated_at']);
        }
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $now = Carbon::now();
        Carbon::setTestNow($now);

        return $company;
    }

    protected function assignReceiptsToCompany(Collection $receipts, Company $company)
    {
        $receipts->each(function ($receipt) use ($company) {
            $receipt->company_id = $company->id;
            $receipt->save();
        });
    }
}
