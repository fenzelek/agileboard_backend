<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Models\Db\VatReleaseReason;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class VatReleaseReasonControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_for_owner()
    {
        $this->assertVatReleaseReasonsIndex(RoleType::OWNER);
    }

    /** @test */
    public function index_for_admin()
    {
        $this->assertVatReleaseReasonsIndex(RoleType::ADMIN);
    }

    /** @test */
    public function index_for_employee()
    {
        $this->assertVatReleaseReasonsIndex(RoleType::EMPLOYEE);
    }

    /** @test */
    public function index_for_developer()
    {
        $this->assertVatReleaseReasonsIndex(RoleType::DEVELOPER);
    }

    /** @test */
    public function index_for_tax_office()
    {
        $this->assertVatReleaseReasonsIndex(RoleType::TAX_OFFICE);
    }

    protected function assertVatReleaseReasonsIndex($role)
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole($role);

        $vat_release_reasons = VatReleaseReason::all();

        $this->get('vat-release-reasons')->assertResponseOk();

        $response = $this->response->getData()->data;

        foreach ($vat_release_reasons as $key => $unit) {
            $this->assertEquals($unit->id, $response[$key]->id);
            $this->assertEquals($unit->slug, $response[$key]->slug);
            $this->assertEquals($unit->description, $response[$key]->description);
        }
    }
}
