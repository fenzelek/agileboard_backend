<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Models\Db\InvoiceType;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class InvoiceTypeControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_user_has_permission()
    {
        InvoiceType::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoice-types?selected_company_id=' . $company->id)
            ->seeStatusCode(200);
    }

    /** @test */
    public function index_data_structure()
    {
        InvoiceType::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(InvoiceType::class, 2)->create();

        $this->get('/invoice-types?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'slug',
                        'description',
                        'no_vat_description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function index_with_correct_data()
    {
        InvoiceType::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_types = factory(InvoiceType::class, 2)->create();

        $this->get('/invoice-types?selected_company_id=' . $company->id)
            ->seeStatusCode(200)->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($invoice_types->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertSame($invoice_types[$key]->slug, $item['slug']);
            $this->assertSame($invoice_types[$key]->description, $item['description']);
            $this->assertSame($invoice_types[$key]->no_vat_description, $item['no_vat_description']);
            $this->assertEquals($invoice_types[$key]->created_at, $item['created_at']);
            $this->assertEquals($invoice_types[$key]->updated_at, $item['updated_at']);
        }
    }

    /** @test */
    public function index_it_checks_if_proforma_type_is_banned_in_register()
    {
        InvoiceType::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_types = factory(InvoiceType::class, 2)->create();
        $banned_type = factory(InvoiceType::class)->create([
            'slug' => 'proforma',
        ]);

        $this->get(
            '/invoice-types?selected_company_id=' . $company->id
            . '&register=1'
        )->seeStatusCode(200)->isJson();

        $this->assertCount(3, InvoiceType::all());

        $data = $this->response->getData()->data;
        $this->assertNotContains('proforma', collect($data)->pluck('slug'));
        $this->assertNotContains($banned_type->id, collect($data)->pluck('id'));
    }

    /** @test */
    public function index_get_description_without_vat_postfix_for_no_vat_payer()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $company->update([
            'vat_payer' => false,
        ]);
        $invoice_types = InvoiceType::all();

        $this->get('/invoice-types?selected_company_id=' . $company->id)
            ->seeStatusCode(200)->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($invoice_types->count(), count($data));
        foreach ($data as $key => $item) {
            $this->assertFalse(str_contains($item['no_vat_description'], 'VAT'));
        }
    }
}
