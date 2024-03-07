<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Models\Other\RoleType;
use App\Models\Other\SaleInvoice\FilterOption;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class InvoiceFilterControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_errorWithoutPermissions()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $this->get('/invoice-filters?selected_company_id=' . $company->id)
            ->seeStatusCode(401);
    }

    /** @test */
    public function index_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/invoice-filters?selected_company_id=' . $company->id)
            ->seeStatusCode(200)->isJson();

        $data = $this->decodeResponseJson()['data'];

        foreach (FilterOption::all() as $index => $filter) {
            $this->assertSame($filter, $data[$index]['slug']);
            $this->assertSame(FilterOption::translate($filter), $data[$index]['description']);
        }
    }
}
