<?php

namespace Tests\Feature\App\Modules\Sale\Http\Controllers;

use App\Models\Db\PaymentMethod;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class PaymentMethodsControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * This test is for checking API response structure.
     */
    public function testIndex()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(PaymentMethod::class, 3)->create();

        $this->get('/payment-methods?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [['id', 'slug', 'name', 'invoice_restrict', 'created_at', 'updated_at']],
            ])->isJson();
    }

    /**
     * This test is for checking API response data.
     */
    public function testIndexWithData()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();
        $payment_methods = factory(PaymentMethod::class, 3)->create();

        $this->get('/payment-methods?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($payment_methods->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($payment_methods[$key]->id, $item['id']);
            $this->assertEquals($payment_methods[$key]->slug, $item['slug']);
            $this->assertEquals($payment_methods[$key]->name, $item['name']);
            $this->assertFalse((bool) $item['invoice_restrict']);
        }
    }

    /** @test */
    public function index_validation_error_invoice_restrict()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/payment-methods?selected_company_id=' . $company->id
            . '&invoice_restrict=' . 'not_valid_boolean')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_restrict',
        ]);

        $this->get('/payment-methods?selected_company_id=' . $company->id
            . '&invoice_restrict=' . 100)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_restrict',
        ]);
    }

    /** @test */
    public function index_filter_by_invoice_restrict_parameter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();

        $payment_methods = factory(PaymentMethod::class, 3)->create();

        $payment_methods[0]->invoice_restrict = true;
        $payment_methods[0]->save();

        $this->get('/payment-methods?selected_company_id=' . $company->id
            . '&invoice_restrict=' . 1)
            ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(2, count($data));

        $this->assertSame($payment_methods[1]->id, $data[0]['id']);
        $this->assertSame($payment_methods[1]->slug, $data[0]['slug']);
        $this->assertSame($payment_methods[1]->name, $data[0]['name']);
        $this->assertFalse((bool) $data[0]['invoice_restrict']);

        $this->assertSame($payment_methods[2]->id, $data[1]['id']);
        $this->assertSame($payment_methods[2]->slug, $data[1]['slug']);
        $this->assertSame($payment_methods[2]->name, $data[1]['name']);
        $this->assertFalse((bool) $data[1]['invoice_restrict']);
    }

    /** @test */
    public function index_return_all_if_invoice_restrict_parameter_set_0()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        PaymentMethod::whereRaw('1 = 1')->delete();

        $payment_methods = factory(PaymentMethod::class, 3)->create();

        $payment_methods[0]->invoice_restrict = true;
        $payment_methods[0]->save();

        $this->get('/payment-methods?selected_company_id=' . $company->id
            . '&invoice_restrict=' . 0)
            ->assertResponseOk();

        $data = $this->decodeResponseJson()['data'];

        $this->assertSame(3, count($data));

        $this->assertSame($payment_methods[0]->id, $data[0]['id']);
        $this->assertSame($payment_methods[0]->slug, $data[0]['slug']);
        $this->assertSame($payment_methods[0]->name, $data[0]['name']);
        $this->assertTrue((bool) $data[0]['invoice_restrict']);

        $this->assertSame($payment_methods[1]->id, $data[1]['id']);
        $this->assertSame($payment_methods[1]->slug, $data[1]['slug']);
        $this->assertSame($payment_methods[1]->name, $data[1]['name']);
        $this->assertFalse((bool) $data[1]['invoice_restrict']);

        $this->assertSame($payment_methods[2]->id, $data[2]['id']);
        $this->assertSame($payment_methods[2]->slug, $data[2]['slug']);
        $this->assertSame($payment_methods[2]->name, $data[2]['name']);
        $this->assertFalse((bool) $data[2]['invoice_restrict']);
    }
}
