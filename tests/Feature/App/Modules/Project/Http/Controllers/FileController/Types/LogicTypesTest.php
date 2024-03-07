<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Types;

use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class LogicTypesTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function files_types_success()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $response = $this->get('/projects/files/types?selected_company_id=' . $company->id)
            ->seeStatusCode(200)->decodeResponseJson()['data'];

        $this->assertEquals([
            'images' => ['jpg', 'jpeg', 'gif', 'png', 'bmp'],
            'pdf' => ['pdf'],
            'documents' => ['doc', 'docx', 'odt', 'txt', 'rtf'],
            'spreadsheets' => ['xls', 'xlsx', 'ods', 'csv'],
        ], $response);
    }
}
