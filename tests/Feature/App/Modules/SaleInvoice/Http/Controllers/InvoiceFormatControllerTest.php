<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Models\Db\InvoiceFormat;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class InvoiceFormatControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * This test is for checking API response structure.
     */
    public function test_index_data_structure()
    {
        InvoiceFormat::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(InvoiceFormat::class, 2)->create();

        $this->get('/invoice-formats?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [['id', 'name', 'format', 'example']],
            ])->isJson();
    }

    /**
     * This test is for checking API response data.
     */
    public function test_index_with_correct_data()
    {
        InvoiceFormat::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_formats = factory(InvoiceFormat::class, 2)->create();

        $this->get('/invoice-formats?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($invoice_formats->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($invoice_formats[$key]->name, $item['name']);
            $this->assertEquals($invoice_formats[$key]->format, $item['format']);
            $this->assertEquals($invoice_formats[$key]->example, $item['example']);
        }
    }
}
