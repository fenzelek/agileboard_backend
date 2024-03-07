<?php

namespace Tests\Feature\App\Console\Commands;

use App\Models\Db\Company as ModelCompany;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImportContractorsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function import_throw_no_required_parameter_exception()
    {
        try {
            Artisan::call('import:contractors', []);
        } catch (\Exception $e) {
            $resultAsText = $e->getMessage();
        }
        $this->assertStringContainsString('company_id', $resultAsText);
    }

    /** @test */
    public function import_throw_no_found_model_exception()
    {
        $company = factory(ModelCompany::class)->create();
        $company_id = $company->id;
        $company->delete();

        try {
            Artisan::call('import:contractors', [
                'company_id' => $company_id,
            ]);
        } catch (ModelNotFoundException $e) {
            $resultAsText = get_class($e);
            $this->assertSame(ModelNotFoundException::class, $resultAsText);
        }
    }
}
