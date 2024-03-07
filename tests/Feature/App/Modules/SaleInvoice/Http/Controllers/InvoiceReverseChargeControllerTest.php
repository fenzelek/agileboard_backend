<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Db\InvoiceReverseCharge;
use Tests\BrowserKitTestCase;

class InvoiceReverseChargeControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    public function test_index_data_structure()
    {
        InvoiceReverseCharge::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(InvoiceReverseCharge::class, 2)->create();

        $this->get('/invoice-reverse-charges?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [['id', 'slug', 'description']],
            ])->isJson();
    }

    /**
     * This test is for checking API response data.
     */
    public function test_index_with_correct_data()
    {
        InvoiceReverseCharge::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $slug_1 = 'reverse charge slug 1';
        $description_1 = 'reverse charge description 1';
        $invoice_invoice_reverses[] = factory(InvoiceReverseCharge::class)->create([
            'slug' => $slug_1,
            'description' => $description_1,
        ]);
        $slug_2 = 'margin slug 2';
        $description_2 = 'margin description 2';
        $invoice_invoice_reverses[] = factory(InvoiceReverseCharge::class)->create([
            'slug' => $slug_2,
            'description' => $description_2,
        ]);

        $this->get('/invoice-reverse-charges?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($data));
        $this->assertEquals($invoice_invoice_reverses[0]->slug, $data[0]['slug']);
        $this->assertEquals($invoice_invoice_reverses[0]->description, $data[0]['description']);
        $this->assertEquals($invoice_invoice_reverses[1]->slug, $data[1]['slug']);
        $this->assertEquals($invoice_invoice_reverses[1]->description, $data[1]['description']);
    }
}
