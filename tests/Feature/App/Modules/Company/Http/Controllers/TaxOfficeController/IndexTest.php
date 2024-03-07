<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers\TaxOfficeController;

use App\Models\Db\Package;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function owner_has_access()
    {
        $this->verifyForRole(RoleType::OWNER);
    }

    /** @test */
    public function admin_has_access()
    {
        $this->verifyForRole(RoleType::ADMIN);
    }

    /** @test */
    public function dealer_has_access()
    {
        $this->verifyForRole(RoleType::DEALER);
    }

    /** @test */
    public function developer_has_access()
    {
        $this->verifyForRole(RoleType::DEVELOPER);
    }

    /** @test */
    public function client_has_access()
    {
        $this->verifyForRole(RoleType::CLIENT);
    }

    /** @test */
    public function employee_has_access()
    {
        $this->verifyForRole(RoleType::EMPLOYEE);
    }

    /** @test */
    public function tax_office_has_access()
    {
        $this->verifyForRole(RoleType::TAX_OFFICE);
    }

    /** @test */
    public function it_gets_valid_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('tax-offices?selected_company_id=' . $company->id);

        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'zip_code',
                    'city',
                    'street',
                    'number',
                    'code',
                    'created_at',
                    'updated_at',
                ],
            ],
            'exec_time',
        ]);

        $this->assertCount(380, $response->json()['data']);
    }

    protected function verifyForRole($role_slug)
    {
        $this->createUser();
        $company = $this->createCompanyWithRole($role_slug);
        auth()->loginUsingId($this->user->id);

        $response = $this->get('tax-offices?selected_company_id=' . $company->id);
        $response->assertStatus(200);

        return $response;
    }
}
