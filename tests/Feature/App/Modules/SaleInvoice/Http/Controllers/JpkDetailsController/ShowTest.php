<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\JpkDetailsController;

use App\Helpers\ErrorCode;
use App\Models\Db\CompanyJpkDetail;
use App\Models\Db\Package;
use App\Models\Db\TaxOffice;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowTest extends TestCase
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
    public function dealer_has_no_access()
    {
        $this->verifyNoAccessForRole(RoleType::DEALER);
    }

    /** @test */
    public function developer_has_no_access()
    {
        $this->verifyNoAccessForRole(RoleType::DEVELOPER);
    }

    /** @test */
    public function client_has_no_access()
    {
        $this->verifyNoAccessForRole(RoleType::CLIENT);
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
    public function it_return_404_when_no_jpk_details_for_company()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);

        $response = $this->get('companies/jpk_details?selected_company_id=' . $company->id);

        $this->verifyResponseError($response, 404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    protected function verifyForRole($role_slug)
    {
        $now = '2017-12-08 14:42:51';

        Carbon::setTestNow($now);
        $company = $this->createUserAndCompanyAndLoginUser($role_slug);

        $tax_office_data = [
            'name' => 'This is sample name of tax-office',
            'zip_code' => '23-123',
            'city' => 'Very sample city',
            'street' => 'Street for tests',
            'number' => 412,
            'code' => '15XRt',
        ];

        $tax_office = TaxOffice::create($tax_office_data);

        $jpk_details = [
            'company_id' => $company->id,
            'regon' => '23313231',
            'state' => 'sample state',
            'county' => 'test county',
            'community' => 'some community',
            'street' => 'very sample street',
            'building_number' => '51',
            'flat_number' => '31',
            'city' => 'My new modern city',
            'zip_code' => '51-321',
            'postal' => 'Post office',
            'tax_office_id' => $tax_office->id,
        ];

        $jpk_details_record = CompanyJpkDetail::create($jpk_details);

        $response = $this->get('companies/jpk_details?selected_company_id=' . $company->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'company_id',
                'regon',
                'state',
                'county',
                'community',
                'street',
                'building_number',
                'flat_number',
                'city',
                'zip_code',
                'postal',
                'tax_office_id',
                'created_at',
                'updated_at',
                'tax_office' => [
                    'data' => [
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
            ],
            'exec_time',
        ]);

        $json = $response->json()['data'];

        $expected_response = $jpk_details + [
                'id' => $jpk_details_record->id,
                'created_at' => $now,
                'updated_at' => $now,
                'tax_office' => [
                    'data' => $tax_office_data + [
                            'id' => $tax_office->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                ],
            ];

        $this->assertEquals($expected_response, $json);
    }

    protected function verifyNoAccessForRole($role_slug)
    {
        $company = $this->createUserAndCompanyAndLoginUser($role_slug);

        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);

        return $response;
    }

    protected function createUserAndCompanyAndLoginUser($role_slug)
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage($role_slug, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        return $company;
    }
}
