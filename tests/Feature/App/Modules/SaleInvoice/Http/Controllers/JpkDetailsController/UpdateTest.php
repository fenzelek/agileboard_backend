<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\JpkDetailsController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\CompanyJpkDetail;
use App\Models\Db\Package;
use App\Models\Db\TaxOffice;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UpdateTest extends TestCase
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
        $this->verifyNoAccessForRole(RoleType::EMPLOYEE);
    }

    /** @test */
    public function tax_office_has_access()
    {
        $this->verifyNoAccessForRole(RoleType::TAX_OFFICE);
    }

    /** @test */
    public function it_will_create_company_jpk_detail_if_it_doesnt_exist_yet()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);

        $now = '2017-12-08 14:42:51';

        Carbon::setTestNow($now);

        $tax_office_data = $this->getValidTaxOfficeData();

        $tax_office = TaxOffice::create($tax_office_data);

        $jpk_details = $this->getValidJpkDetails($company, $tax_office);

        $response = $this->put(
            'companies/jpk_details?selected_company_id=' . $company->id,
            $jpk_details
        );

        // verify response
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

        $this->assertEquals($expected_response, array_except($json, 'id'));

        // verify data
        $this->assertSame(1, CompanyJpkDetail::where('company_id', $company->id)->count());
        $this->assertSame(['id' => $json['id']] + $jpk_details + [
                'created_at' => $now,
                'updated_at' => $now,
            ], $company->jpkDetail->toArray());
    }

    /** @test */
    public function it_gets_validation_error_when_no_data_sent()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);

        // verify response
        $response = $this->put(
            'companies/jpk_details?selected_company_id=' . $company->id,
            []
        );

        $this->verifyResponseValidation($response, [
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
        ]);
    }

    /** @test */
    public function it_allow_to_send_empty_regon_building_number_and_flat_number()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);

        $tax_office_data = $this->getValidTaxOfficeData();
        $tax_office = TaxOffice::create($tax_office_data);
        $jpk_details = $this->getValidJpkDetails($company, $tax_office);

        $jpk_details['regon'] = null;
        $jpk_details['building_number'] = null;
        $jpk_details['flat_number'] = null;

        // verify response
        $response = $this->put(
            'companies/jpk_details?selected_company_id=' . $company->id,
            $jpk_details
        );

        $response->assertStatus(200);

        $this->assertNull($company->jpkDetail->regon);
        $this->assertNull($company->jpkDetail->building_number);
        $this->assertNull($company->jpkDetail->flat_number);
    }

    /** @test */
    public function it_allows_to_fill_9_digits_regon()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);

        $tax_office_data = $this->getValidTaxOfficeData();
        $tax_office = TaxOffice::create($tax_office_data);
        $jpk_details = $this->getValidJpkDetails($company, $tax_office);

        $jpk_details['regon'] = str_repeat(1, 9);

        $response = $this->put(
            'companies/jpk_details?selected_company_id=' . $company->id,
            $jpk_details
        );

        $response->assertStatus(200);
    }

    /** @test */
    public function it_allows_to_fill_14_digits_regon()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);

        $tax_office_data = $this->getValidTaxOfficeData();
        $tax_office = TaxOffice::create($tax_office_data);
        $jpk_details = $this->getValidJpkDetails($company, $tax_office);

        $jpk_details['regon'] = str_repeat(1, 14);

        $response = $this->put(
            'companies/jpk_details?selected_company_id=' . $company->id,
            $jpk_details
        );

        $response->assertStatus(200);
    }

    /** @test */
    public function it_gets_validation_error_for_other_length_regon()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);

        $tax_office_data = $this->getValidTaxOfficeData();
        $tax_office = TaxOffice::create($tax_office_data);
        $jpk_details = $this->getValidJpkDetails($company, $tax_office);

        $jpk_details['regon'] = str_repeat(1, 10);

        $response = $this->put(
            'companies/jpk_details?selected_company_id=' . $company->id,
            $jpk_details
        );

        $this->verifyResponseValidation($response, ['regon']);
    }

    protected function verifyForRole($role_slug)
    {
        $now = '2017-12-08 14:42:51';

        Carbon::setTestNow($now);
        $company = $this->createUserAndCompanyAndLoginUser($role_slug);

        $tax_office_data = $this->getValidTaxOfficeData();

        $tax_office = TaxOffice::create($tax_office_data);

        $tax_office_data2 = [
            'name' => 'New tax-office',
            'zip_code' => '70-231',
            'city' => 'City, after update',
            'street' => 'Street after update',
            'number' => '482A',
            'code' => '09423',
        ];

        $tax_office2 = TaxOffice::create($tax_office_data2);

        $jpk_details = $this->getValidJpkDetails($company, $tax_office);

        $new_jpk_details = [
            'company_id' => $company->id,
            'regon' => '239283941',
            'state' => 'modified state',
            'county' => 'new county',
            'community' => 'updated community',
            'street' => 'modern street',
            'building_number' => '23',
            'flat_number' => '91',
            'city' => 'This is updated city',
            'zip_code' => '77-813',
            'postal' => 'New post office',
            'tax_office_id' => $tax_office2->id,
        ];

        $jpk_details_record = CompanyJpkDetail::create($jpk_details);

        $now_update = '2017-12-11 14:42:51';

        Carbon::setTestNow($now_update);

        // verify response
        $response = $this->put(
            'companies/jpk_details?selected_company_id=' . $company->id,
            $new_jpk_details
        );

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

        $expected_response = $new_jpk_details + [
                'id' => $jpk_details_record->id,
                'created_at' => $now,
                'updated_at' => $now_update,
                'tax_office' => [
                    'data' => $tax_office_data2 + [
                            'id' => $tax_office2->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                ],
            ];

        $this->assertEquals($expected_response, $json);

        // verify data

        $this->assertSame(1, CompanyJpkDetail::where('company_id', $company->id)->count());
        $this->assertSame(['id' => $jpk_details_record->id] + $new_jpk_details + [
                'created_at' => $now,
                'updated_at' => $now_update,
            ], $company->jpkDetail->toArray());
    }

    protected function verifyNoAccessForRole($role_slug)
    {
        $company = $this->createUserAndCompanyAndLoginUser($role_slug);

        $response = $this->put('companies/jpk_details/?selected_company_id=' . $company->id);
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

    protected function getValidJpkDetails(Company $company, TaxOffice $tax_office)
    {
        return [
            'company_id' => $company->id,
            'regon' => '233132310',
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
    }

    protected function getValidTaxOfficeData()
    {
        return [
            'name' => 'This is sample name of tax-office',
            'zip_code' => '23-123',
            'city' => 'Very sample city',
            'street' => 'Street for tests',
            'number' => 412,
            'code' => '15XRt',
        ];
    }
}
