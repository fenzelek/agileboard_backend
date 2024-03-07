<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Clipboard;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers\CreateInvoice;

class ClipboardControllerTest extends TestCase
{
    use DatabaseTransactions, CreateInvoice;

    /** @test */
    public function test_index_data_structure()
    {
        Clipboard::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Clipboard::class, 2)->create([
            'company_id' => $company->id,
        ]);

        $response = $this->get('/clipboard?selected_company_id=' . $company->id);
        $response->isOk();
        $response->assertJsonStructure([
                'data' => [['id', 'file_name']],
            ]);
    }

    /**
     * @test .
     */
    public function index_with_correct_data()
    {
        Clipboard::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $clipboard[] = factory(Clipboard::class)->create([
            'file_name' => 'file_name_1',
            'company_id' => $company->id,
        ]);
        $clipboard[] = factory(Clipboard::class)->create([
            'file_name' => 'file_name_2',
            'company_id' => $company->id,
        ]);
        factory(Clipboard::class, 2)->create();

        $response = $this->get('/clipboard?selected_company_id=' . $company->id);
        $response->isOk();

        $this->assertEquals(4, Clipboard::count());

        $data = $response->json()['data'];
        $this->assertEquals(2, count($data));
        $this->assertEquals('file_name_1', $data[0]['file_name']);
        $this->assertEquals('file_name_2', $data[1]['file_name']);
    }

    /** @test */
    public function download_file_if_exists()
    {
        $this->createUser();
        $this->company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $file_name = 'sample_name.pdf';
        $filepath = $this->getStorageDiskFilepath($file_name);
        $this->app['filesystem']->put($filepath, 'sample');

        $clipboard = factory(Clipboard::class)->create([
            'company_id' => $this->company->id,
            'file_name' => $file_name,
        ]);

        $response = $this->get('/clipboard/' . $clipboard->id . '?selected_company_id=' . $this->company->id);
        $response->assertStatus(200);
    }

    /** @test */
    public function download_file_if_not_exists()
    {
        $this->createUser();
        $this->company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $file_name = 'sample_name.pdf';

        $clipboard = factory(Clipboard::class)->create([
            'company_id' => $this->company->id,
            'file_name' => $file_name,
        ]);

        $response = $this->get('/clipboard/' . $clipboard->id . '?selected_company_id=' . $this->company->id);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertSeeText(ErrorCode::CLIPBOARD_NOT_FOUND_FILE);
    }

    /** @test */
    public function download_company_no_file_owner()
    {
        $this->createUser();
        $this->company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $clipboard = factory(Clipboard::class)->create();

        $response = $this->get('/clipboard/' . $clipboard->id . '?selected_company_id=' . $this->company->id);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertSeeText(ErrorCode::RESOURCE_NOT_FOUND);
    }
}
