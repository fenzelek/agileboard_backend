<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Models\Db\CompanyModule;
use App\Helpers\ErrorCode;
use App\Models\Db\BankAccount;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\GusCompany;
use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Transaction;
use App\Models\Db\User;
use App\Models\Other\ModuleType;
use App\Models\Db\Company;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Models\Db\UserCompany;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use App\Models\Other\UserCompanyStatus;
use App\Models\Db\PaymentMethod;
use App\Models\Other\VatReleaseReasonType;
use App\Models\Db\VatReleaseReason;
use App\Modules\Company\Services\Gus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use File;
use Tests\BrowserKitTestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class CompanyControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ArraySubsetAsserts, CompanyControllerTrait;

    protected $file_name;

    protected $gus;

    protected function setUp(): void
    {
        parent::setUp();

        $gus_company = new GusCompany();
        $this->gus = $this->getMockBuilder(Gus::class)
            ->setConstructorArgs([$gus_company])
            ->onlyMethods(['pullDataFromServer'])->getMock();

        app()->bind(Gus::class, function () {
            return $this->gus;
        });
    }

    protected function tearDown(): void
    {
        Storage::disk('logotypes')->delete($this->file_name);

        parent::tearDown();
    }

    /** @test */
    public function store_get_error_with_missing_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->post('/companies');

        $this->verifyValidationResponse(['name']);
    }

    /** @test */
    public function store_get_validation_error_no_vat_release_reason_for_not_vat_payer()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $data = ['name' => 'Test company', 'vat_payer' => false];

        $this->post('/companies', $data);

        $this->verifyValidationResponse(['vat_release_reason_id']);
    }

    /** @test */
    public function store_get_validation_error_no_vat_release_reason_note_for_not_vat_payer()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $data = [
            'name' => 'Test company',
            'vat_payer' => false,
            'vat_release_reason_id' => VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_BASIS)->id,
        ];

        $this->post('/companies', $data);

        $this->verifyValidationResponse(['vat_release_reason_note']);
    }

    /** @test */
    public function store_create_company_and_assign_when_user_owns_no_company()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $data = [
            'name' => 'Test company',
            'vat_payer' => false,
            'vat_release_reason_id' => VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_REGULATION)->id,
        ];

        $this->post('/companies', $data)->seeStatusCode(201);

        // make sure in response we have valid user data
        $json = $this->decodeResponseJson();
        $responseCompany = $json['data'];

        $this->assertEquals($data['name'], $responseCompany['name']);

        // db verification - company record created
        $db_company = Company::find($responseCompany['id']);
        $this->assertEquals($data['name'], $db_company->name);
        $this->assertEquals($data['vat_release_reason_id'], $db_company->vatReleaseReason->id);
        $this->assertFalse($db_company->vat_payer);
        $this->assertNull($db_company->vat_release_reason_note);
        //  db verification - default company package
        $default_package = Package::findDefault();
        $this->assertEquals($default_package->id, $db_company->realPackage()->id);
        $this->verifyStartPackageDb($db_company);

        // db verification - user assigned to company as owner
        $dbUserCompany = UserCompany::where('user_id', $this->user->id)
            ->companyId($responseCompany['id'])->get();
        $this->assertEquals(1, $dbUserCompany->count());
        $this->assertEquals(
            RoleType::OWNER,
            $dbUserCompany->first()->role->name
        );
        $this->assertEquals(
            UserCompanyStatus::APPROVED,
            $dbUserCompany->first()->status
        );
    }

    /** @test */
    public function store_check_default_enable_activity_value()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $data = [
            'name' => 'Test company',
            'vat_payer' => false,
            'vat_release_reason_id' => VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_REGULATION)->id,
        ];

        $this->post('/companies', $data)->seeStatusCode(201);

        // make sure in response we have valid user data
        $json = $this->decodeResponseJson();
        $responseCompany = $json['data'];

        $this->seeInDatabase('companies', [
            'id' => $responseCompany['id'],
            'enable_activity' => false,
        ]);
    }

    /** @test */
    public function store_create_company_check_cep_free_package()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        Package::where('default', 1)->update(['default' => 0]);
        Package::where('slug', Package::CEP_FREE)->update(['default' => 1]);

        $data = [
            'name' => 'Test company',
            'vat_payer' => false,
            'vat_release_reason_id' => VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_REGULATION)->id,
        ];

        $this->post('/companies', $data)->seeStatusCode(201);

        $json = $this->decodeResponseJson();

        // db verification - company record created
        $db_company = Company::find($json['data']['id']);
        $default_package = Package::findDefault();
        $this->assertEquals($default_package->id, $db_company->realPackage()->id);
        $this->verifyCepPackageDb($db_company);
    }

    /** @test */
    public function store_it_save_boolean_vat_payer_column()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $data = ['name' => 'Test company', 'vat_payer' => true];

        $this->post('/companies', $data)->seeStatusCode(201);

        // make sure in response we have valid user data
        $json = $this->decodeResponseJson();
        $responseCompany = $json['data'];

        $this->assertEquals($data['name'], $responseCompany['name']);

        // db verification - company record created
        $db_company = Company::find($responseCompany['id']);
        $this->assertEquals($data['name'], $db_company->name);
        $this->assertTrue($db_company->vat_payer);
        $this->assertNull($db_company->vat_release_reason_id);
        $this->assertNull($db_company->vat_release_reason_note);
        //  db verification - default company package
        $default_package = Package::findDefault();
        $this->assertEquals($default_package->id, $db_company->realPackage()->id);
        $this->verifyStartPackageDb($db_company);

        // db verification - user assigned to company as owner
        $dbUserCompany = UserCompany::where('user_id', $this->user->id)
            ->companyId($responseCompany['id'])->get();
        $this->assertEquals(1, $dbUserCompany->count());
        $this->assertEquals(
            RoleType::OWNER,
            $dbUserCompany->first()->role->name
        );
        $this->assertEquals(
            UserCompanyStatus::APPROVED,
            $dbUserCompany->first()->status
        );
    }

    /** @test */
    public function store_error_user_owns_already_company()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = factory(Company::class)->create();

        $userCompany = new UserCompany();
        $userCompany->user_id = $this->user->id;
        $userCompany->role_id = Role::findByName(RoleType::OWNER)->id;
        $userCompany->status = UserCompanyStatus::APPROVED;
        $userCompany->company_id = $company->id;
        $userCompany->save();

        $data = [
            'name' => 'Test company',
            'vat_payer' => false,
            'vat_release_reason_id' => VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_REGULATION)->id,
        ];

        $this->post('/companies', $data);

        $this->verifyErrorResponse(422, ErrorCode::COMPANY_CREATION_LIMIT);
    }

    /** @test */
    public function store_create_company_and_assign_when_user_is_assigned_to_company()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = factory(Company::class)->create();

        $userCompany = new UserCompany();
        $userCompany->user_id = $this->user->id;
        $userCompany->role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $userCompany->status = UserCompanyStatus::APPROVED;
        $userCompany->company_id = $company->id;
        $userCompany->save();

        $data = [
            'name' => 'Test company',
            'vat_payer' => false,
            'vat_release_reason_id' => VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_REGULATION)->id,
        ];

        $this->post('/companies', $data)->seeStatusCode(201);

        // make sure in response we have valid user data
        $json = $this->decodeResponseJson();
        $responseCompany = $json['data'];

        $this->assertEquals($data['name'], $responseCompany['name']);

        // db verification - company record created
        $dbCompany = Company::find($responseCompany['id']);
        $this->assertEquals($data['name'], $dbCompany->name);
        $this->assertFalse($dbCompany->vat_payer);
        $this->assertEquals($data['vat_release_reason_id'], $dbCompany->vat_release_reason_id);

        //  db verification - default company package
        $default_package = Package::findDefault();
        $this->assertEquals($default_package->id, $dbCompany->realPackage()->id);
        $this->verifyStartPackageDb($dbCompany);

        // db verification - user assigned to company as owner
        $dbUserCompany = UserCompany::where('user_id', $this->user->id)
            ->where('company_id', $responseCompany['id'])->get();
        $this->assertEquals(1, $dbUserCompany->count());
        $this->assertEquals(RoleType::OWNER, $dbUserCompany->first()->role->name);
        $this->assertEquals(UserCompanyStatus::APPROVED, $dbUserCompany->first()->status);

        // db verification - roles copied to company
        $expected_default_roles = [
            RoleType::OWNER,
            RoleType::ADMIN,
            RoleType::EMPLOYEE,
            RoleType::TAX_OFFICE,
        ];

        $company_roles = $dbCompany->roles->pluck('name')->all();
        $this->assertEquals($expected_default_roles, $company_roles);
    }

    /** @test */
    public function store_it_save_vat_release_reason_if_vat_payer_int_representing()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = factory(Company::class)->create();

        $data = [
            'name' => 'Test company',
            'vat_payer' => 0,
            'vat_release_reason_id' => VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_BASIS)->id,
            'vat_release_reason_note' => 'release_note',
        ];

        $this->post('/companies', $data)->seeStatusCode(201);

        $json = $this->decodeResponseJson();
        $responseCompany = $json['data'];

        $dbCompany = Company::find($responseCompany['id']);
        $this->assertEquals($data['name'], $dbCompany->name);
        $this->assertFalse($dbCompany->vat_payer);
        $this->assertEquals($data['vat_release_reason_id'], $dbCompany->vat_release_reason_id);
        $this->assertEquals('release_note', $dbCompany->vat_release_reason_note);
    }

    /** @test */
    public function update_it_returns_validation_error_without_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put('companies?selected_company_id=' . $company->id, []);

        $this->verifyValidationResponse(
            [
                'name',
                'email',
                'website',
                'phone',
                'main_address_street',
                'main_address_number',
                'main_address_zip_code',
                'main_address_city',
                'main_address_country',

                'contact_address_street',
                'contact_address_number',
                'contact_address_zip_code',
                'contact_address_city',
                'contact_address_country',
            ],
            [
                'default_payment_term_days',
                'default_payment_method_id',
            ]
        );
    }

    /** @test */
    public function update_it_returns_validation_error_bank_account_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put(
            'companies?selected_company_id=' . $company->id,
            ['bank_accounts' => [['id' => 'no_valid_bank_account_id']]]
        );
        $this->verifyValidationResponse(
            [
                'bank_accounts.0.number',
                'bank_accounts.0.bank_name',
                'bank_accounts.0.default',
                'bank_accounts.0.id',
            ]
        );
    }

    /** @test */
    public function update_it_returns_validation_error_with_invalid_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put('companies?selected_company_id=' . $company->id, [
            'name' => 'Sample company',
            'vatin' => '',
            'email' => 'invalidemail',
            'phone' => '',

            'main_address_street' => 'Sample street',
            'main_address_number' => '',
            'main_address_zip_code' => '',
            'main_address_city' => '',
            'main_address_country' => '',

            'contact_address_street' => '',
            'contact_address_number' => '',
            'contact_address_zip_code' => '',
            'contact_address_city' => '',
            'contact_address_country' => '',
            'bank_accounts' => [
                [
                    'bank_name' => str_repeat('a', 64),
                    'number' => str_repeat('a', 64),
                    'default' => 'no_valid_boolean',
                ],
            ],
        ]);

        $this->verifyValidationResponse(
            [
                'vat_payer',
                'email',
                'website',
                'main_address_number',
                'main_address_zip_code',
                'main_address_city',
                'main_address_country',
                'contact_address_street',
                'contact_address_number',
                'contact_address_zip_code',
                'contact_address_city',
                'contact_address_country',
                'bank_accounts.0.number',
                'bank_accounts.0.bank_name',
                'bank_accounts.0.default',
            ],
            [
                'name',
                'main_address_street',
                'phone',
            ]
        );
    }

    /** @test */
    public function update_it_returns_validation_error_lack_vat_release_reason()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['vat_payer'] = false;

        $this->put('companies?selected_company_id=' . $company->id, $data);

        $this->verifyValidationResponse(
            [
                'vat_release_reason_id',
            ]
        );
    }

    /** @test */
    public function update_it_returns_validation_error_lack_vat_release_reason_note()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['vat_payer'] = false;
        $data['vat_release_reason_id'] =
            VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_BASIS)->id;

        $this->put('companies?selected_company_id=' . $company->id, $data);

        $this->verifyValidationResponse(
            [
                'vat_release_reason_note',
            ]
        );
    }

    /** @test */
    public function update_it_returns_validation_error_no_one_default_bank_accounts()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['bank_accounts'] = [
            [
                'bank_name' => 'Initial bank_name',
                'number' => 'Initial bank_account_number',
                'default' => false,
            ],
            [
                'bank_name' => 'Initial bank_name_2',
                'number' => 'Initial bank_account_number_2',
                'default' => false,
            ],
        ];

        $this->put('companies?selected_company_id=' . $company->id, $data);

        $this->verifyValidationResponse(['bank_accounts']);

        array_set($data, 'bank_accounts.0.default', true);
        array_set($data, 'bank_accounts.1.default', true);

        $this->put('companies?selected_company_id=' . $company->id, $data);

        $this->verifyValidationResponse(['bank_accounts']);
    }

    /** @test */
    public function update_it_updates_company_when_valid_full_data_are_sent()
    {
        $pl_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = [
            'name' => 'Initial company name',
            'vatin' => 'xxx',
            'vat_payer' => null,
            'email' => 'initial@example.com',
            'phone' => 'Initial phone',

            'main_address_street' => 'Initial main_address_street',
            'main_address_number' => 'Initial main_address_number',
            'main_address_zip_code' => 'O m zip',
            'main_address_city' => 'Initial main_address_city',
            'main_address_country' => 'Initial main_address_country',

            'contact_address_street' => 'Initial contact_address_street',
            'contact_address_number' => 'Initial contact_address_number',
            'contact_address_zip_code' => 'O c zip',
            'contact_address_city' => 'Initial contact_address_city',
            'contact_address_country' => 'Initial contact_address_country',
            'default_payment_term_days' => 25,

            // extra junk data that should not be used
            'default_payment_method_id' => 70,
            'creator_id' => $this->user->id + 100,
            'editor_id' => $this->user->id + 200,
        ];
        $company->forceFill($initial_data)->save();
        $this->assertSame($now->toDateTimeString(), $company->created_at->toDateTimeString());

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData($pl_prefix_id);
        $data['vat_payer'] = true;
        $data['bank_accounts'] = [
            [

                'bank_name' => 'Initial bank_name',
                'number' => 'Initial bank_account_number',
                'default' => true,
            ],
            [
                'bank_name' => 'no default bank_name',
                'number' => 'no default bank_account_number',
                'default' => false,
            ],
        ];
        $initialCompanyCount = Company::count();
        BankAccount::whereRaw('1=1')->delete();
        $this->assertSame(0, BankAccount::count());

        $now->addHour(1);
        Carbon::setTestNow($now);

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $this->assertSame($initialCompanyCount, Company::count());
        $this->assertSame(2, BankAccount::count());

        // test record in database
        $data = (object) $data;
        $initial_data = (object) $initial_data;
        $modified_company = $company->fresh();
        $this->assertSame(trim($data->name), $modified_company->name);
        $this->assertEquals($pl_prefix_id, $modified_company->country_vatin_prefix_id);
        $this->assertSame(trim($data->vatin), $modified_company->vatin);
        $this->assertTrue($modified_company->vat_payer);
        $this->assertSame(trim($data->email), $modified_company->email);
        $this->assertSame('', $modified_company->logotype);
        $this->assertSame('www.bestcompany.pl', $modified_company->website);
        $this->assertSame(trim($data->phone), $modified_company->phone);
        $this->assertSame(trim($data->main_address_street), $modified_company->main_address_street);
        $this->assertSame(trim($data->main_address_number), $modified_company->main_address_number);
        $this->assertSame(
            trim($data->main_address_zip_code),
            $modified_company->main_address_zip_code
        );
        $this->assertSame(trim($data->main_address_city), $modified_company->main_address_city);
        $this->assertSame(
            trim($data->main_address_country),
            $modified_company->main_address_country
        );
        $this->assertSame(
            trim($data->contact_address_street),
            $modified_company->contact_address_street
        );
        $this->assertSame(
            trim($data->contact_address_number),
            $modified_company->contact_address_number
        );
        $this->assertSame(
            trim($data->contact_address_zip_code),
            $modified_company->contact_address_zip_code
        );
        $this->assertSame(
            trim($data->contact_address_city),
            $modified_company->contact_address_city
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $modified_company->contact_address_country
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $modified_company->contact_address_country
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $modified_company->contact_address_country
        );
        $this->assertSame(
            $initial_data->default_payment_term_days,
            $modified_company->default_payment_term_days
        );
        $this->assertSame(
            $initial_data->default_payment_method_id,
            $modified_company->default_payment_method_id
        );
        $this->assertSame($initial_data->creator_id, $modified_company->creator_id);
        $this->assertNotEquals($initial_data->editor_id, $modified_company->editor_id);
        $this->assertSame($this->user->id, $modified_company->editor_id);
        $this->assertSame(
            $now->toDateTimeString(),
            $modified_company->updated_at->toDateTimeString()
        );

        $bank_account = $modified_company->bankAccounts()->orderBy('id')->first();
        $this->assertSame($modified_company->id, $bank_account->company_id);
        $this->assertSame('Initial bank_name', $bank_account->bank_name);
        $this->assertSame('Initial bank_account_number', $bank_account->number);
        $this->assertTrue((bool) $bank_account->default);
        $bank_account = $modified_company->bankAccounts()->orderBy('id', 'desc')->first();
        $this->assertSame($modified_company->id, $bank_account->company_id);
        $this->assertSame('no default bank_name', $bank_account->bank_name);
        $this->assertSame('no default bank_account_number', $bank_account->number);
        $this->assertFalse((bool) $bank_account->default);

        $return_data = [
            'data' => $data,
            'initial_data' => $initial_data,
            'response' => $this->response->json(),
            'company' => $company,
            'user' => $this->user,
        ];

        $this->update_it_returns_valid_response_structure($return_data);

        return $return_data;
    }

    /** @test */
    public function update_it_updates_company_when_valid_min_data_are_sent()
    {
        $pl_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = [
            'name' => 'Initial company name',
            'vatin' => 'xxx',
            'vat_payer' => null,
            'email' => 'initial@example.com',
            'phone' => 'Initial phone',

            'main_address_street' => 'Initial main_address_street',
            'main_address_number' => 'Initial main_address_number',
            'main_address_zip_code' => 'O m zip',
            'main_address_city' => 'Initial main_address_city',
            'main_address_country' => 'Initial main_address_country',

            'contact_address_street' => 'Initial contact_address_street',
            'contact_address_number' => 'Initial contact_address_number',
            'contact_address_zip_code' => 'O c zip',
            'contact_address_city' => 'Initial contact_address_city',
            'contact_address_country' => 'Initial contact_address_country',
            'default_payment_term_days' => 25,

            // extra junk data that should not be used
            'default_payment_method_id' => 70,
            'creator_id' => $this->user->id + 100,
            'editor_id' => $this->user->id + 200,
        ];
        $company->forceFill($initial_data)->save();
        $this->assertSame($now->toDateTimeString(), $company->created_at->toDateTimeString());

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData($pl_prefix_id);
        $data['vatin'] = '';
        $data['vat_payer'] = true;
        $data['bank_accounts'] = null;

        $initialCompanyCount = Company::count();
        BankAccount::whereRaw('1=1')->delete();
        $this->assertSame(0, BankAccount::count());

        $now->addHour(1);
        Carbon::setTestNow($now);

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $this->assertSame($initialCompanyCount, Company::count());
        $this->assertSame(0, BankAccount::count());

        // test record in database
        $data = (object) $data;
        $initial_data = (object) $initial_data;
        $modified_company = $company->fresh();
        $this->assertSame(trim($data->name), $modified_company->name);
        $this->assertEquals($pl_prefix_id, $modified_company->country_vatin_prefix_id);
        $this->assertSame(trim($data->vatin), $modified_company->vatin);
        $this->assertTrue($modified_company->vat_payer);
        $this->assertSame(trim($data->email), $modified_company->email);
        $this->assertSame('', $modified_company->logotype);
        $this->assertSame('www.bestcompany.pl', $modified_company->website);
        $this->assertSame(trim($data->phone), $modified_company->phone);
        $this->assertSame(trim($data->main_address_street), $modified_company->main_address_street);
        $this->assertSame(trim($data->main_address_number), $modified_company->main_address_number);
        $this->assertSame(
            trim($data->main_address_zip_code),
            $modified_company->main_address_zip_code
        );
        $this->assertSame(trim($data->main_address_city), $modified_company->main_address_city);
        $this->assertSame(
            trim($data->main_address_country),
            $modified_company->main_address_country
        );
        $this->assertSame(
            trim($data->contact_address_street),
            $modified_company->contact_address_street
        );
        $this->assertSame(
            trim($data->contact_address_number),
            $modified_company->contact_address_number
        );
        $this->assertSame(
            trim($data->contact_address_zip_code),
            $modified_company->contact_address_zip_code
        );
        $this->assertSame(
            trim($data->contact_address_city),
            $modified_company->contact_address_city
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $modified_company->contact_address_country
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $modified_company->contact_address_country
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $modified_company->contact_address_country
        );
        $this->assertSame(
            $initial_data->default_payment_term_days,
            $modified_company->default_payment_term_days
        );
        $this->assertSame(
            $initial_data->default_payment_method_id,
            $modified_company->default_payment_method_id
        );
        $this->assertSame($initial_data->creator_id, $modified_company->creator_id);
        $this->assertNotEquals($initial_data->editor_id, $modified_company->editor_id);
        $this->assertSame($this->user->id, $modified_company->editor_id);
        $this->assertSame(
            $now->toDateTimeString(),
            $modified_company->updated_at->toDateTimeString()
        );

        return [
            'data' => $data,
            'initial_data' => $initial_data,
            'response' => $this->decodeResponseJson(),
            'company' => $company,
            'user' => $this->user,
        ];
    }

    /** @test */
    public function update_saving_vat_release_reason_in_DB()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['vat_payer'] = false;
        $vat_release_reason = VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_BASIS);
        $data['vat_release_reason_id'] = $vat_release_reason->id;
        $data['vat_release_reason_note'] = 'release note';

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $company = Company::latest()->first();
        $this->assertSame($vat_release_reason->id, $company->vatReleaseReason->id);
        $this->assertSame('release note', $company->vat_release_reason_note);
    }

    /** @test */
    public function update_save_existing_bank_accounts()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);
        $initial_bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());

        $this->assertSame(1, $company->bankAccounts()->count());

        // now prepare data to send
        $data = $this->requestData();
        $initial_bank_account->update(['default' => false]);
        array_push($data['bank_accounts'], $initial_bank_account->toArray());
        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();
        $this->assertSame(3, $company->bankAccounts()->count());
        $this->assertInstanceOf(BankAccount::class, BankAccount::find($initial_bank_account->id));
    }

    /** @test */
    public function update_remove_old_bank_accounts()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        $this->assertSame(1, $company->bankAccounts()->count());
        $invoice = factory(Invoice::class)->create([
            'bank_account_id' => $bank_account->id,
        ]);
        $this->assertSame($bank_account->id, $invoice->bank_account_id);

        // now prepare data to send
        $data = $this->requestData();

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $this->assertSame(2, $company->bankAccounts()->count());
        $this->assertNull($invoice->fresh()->bank_account_id);
    }

    /** @test */
    public function update_saving_nullable_vat_release_reason_in_DB()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['vat_payer'] = true;
        $vat_release_reason = VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_BASIS);
        $data['vat_release_reason_id'] = $vat_release_reason->id;
        $data['vat_release_reason_note'] = 'release note';

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $company = Company::latest()->first();
        $this->assertNull($company->vatReleaseReason);
        $this->assertNull($company->vat_release_reason_note);
    }

    /** @test */
    public function update_check_validation_vatin_when_prefix_is_polish()
    {
        $pl_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $this->assertSame($now->toDateTimeString(), $company->created_at->toDateTimeString());

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = [
            'vatin' => '  1234567890123456  ',
        ];

        $this->put('companies?selected_company_id=' . $company->id, $data)->seeStatusCode(422);

        $this->verifyValidationResponse(['vatin']);
    }

    /** @test */
    public function update_check_validation_pass_when_vatin_is_null()
    {
        $pl_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = [
            'vatin' => 'xxx',
            'vat_payer' => false,
        ];
        $company->forceFill($initial_data)->save();
        $this->assertSame($now->toDateTimeString(), $company->created_at->toDateTimeString());

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        array_set($data, 'vatin', '  12345678901234  ');
        array_set($data, 'country_vatin_prefix_id', null);
        array_set($data, 'vat_payer', true);

        $this->put('companies?selected_company_id=' . $company->id, $data);

        $modified_company = $company->fresh();
        $this->assertNull($modified_company->country_vatin_prefix_id);
        $this->assertTrue($modified_company->vat_payer);
    }

    /** @test */
    public function update_validation_throw_error_when_vatin_prefix_is_invalid()
    {
        $pl_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();
        $this->assertSame($now->toDateTimeString(), $company->created_at->toDateTimeString());

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        array_set($data, 'vatin', '  12345678901234  ');
        array_set($data, 'country_vatin_prefix_id', 'aa');
        array_set($data, 'vat_payer', true);

        $this->put('companies?selected_company_id=' . $company->id, $data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse(['country_vatin_prefix_id']);
    }

    /** @test */
    public function update_check_validation_of_vatin_when_prefix_is_not_polish()
    {
        $not_pl_prefix_id = CountryVatinPrefix::first()->id;
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $company->forceFill($this->initialData())->save();
        $this->assertSame($now->toDateTimeString(), $company->created_at->toDateTimeString());

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        array_set($data, 'vatin', '  1234567890123456123456789456123  ');
        array_set($data, 'country_vatin_prefix_id', $not_pl_prefix_id);
        array_set($data, 'vat_payer', false);
        array_set(
            $data,
            'vat_release_reason_id',
            VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_REGULATION)->id
        );

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $modified_company = $company->fresh();
        $this->assertSame('1234567890123456123456789456123', $modified_company->vatin);
        $this->assertFalse($modified_company->vat_payer);
        $this->assertSame(NoVat::COUNT_TYPE, $modified_company->default_invoice_gross_counted);
    }

    /** @test */
    public function update_check_validation_of_vatin_when_prefix_is_not_polish_test()
    {
        $not_pl_prefix_id = CountryVatinPrefix::first()->id;
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = [
            'name' => 'Initial company name',
            'vatin' => 'xxx',
            'logotype' => 'abc.jpg',
            'email' => 'initial@example.com',
            'phone' => 'Initial phone',

            'main_address_street' => 'Initial main_address_street',
            'main_address_number' => 'Initial main_address_number',
            'main_address_zip_code' => 'O m zip',
            'main_address_city' => 'Initial main_address_city',
            'main_address_country' => 'Initial main_address_country',

            'contact_address_street' => 'Initial contact_address_street',
            'contact_address_number' => 'Initial contact_address_number',
            'contact_address_zip_code' => 'O c zip',
            'contact_address_city' => 'Initial contact_address_city',
            'contact_address_country' => 'Initial contact_address_country',
            'default_payment_term_days' => 25,

            // extra junk data that should not be used
            'default_payment_method_id' => 70,
            'creator_id' => $this->user->id + 100,
            'editor_id' => $this->user->id + 200,
        ];
        $company->forceFill($initial_data)->save();
        $this->assertSame($now->toDateTimeString(), $company->created_at->toDateTimeString());

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = [
            'name' => '  New company name  ',
            'country_vatin_prefix_id' => $not_pl_prefix_id,
            'vatin' => '  1234567890123456123456789456123  ',
            'email' => '  sample@example.com  ',
            'phone' => '  New phone  ',
            'main_address_street' => '  New main_address_street  ',
            'main_address_number' => '  New main_address_number  ',
            'main_address_zip_code' => '  123456789  ',
            'main_address_city' => '  New main_address_city  ',
            'main_address_country' => '  Polska  ',
            'contact_address_street' => '  New contact_address_street  ',
            'contact_address_number' => '  New contact_address_number  ',
            'contact_address_zip_code' => '  123456789  ',
            'contact_address_city' => '  New contact_address_city  ',
            'contact_address_country' => '  Albania  ',
            'default_payment_term_days' => 90,
            'default_payment_method_id' => 17,
            'creator_id' => $this->user->id + 267,
            'editor_id' => $this->user->id + 297,
        ];

        $this->put('companies?selected_company_id=' . $company->id, $data)
            ->seeStatusCode(422);

        $this->verifyValidationResponse(['main_address_zip_code'], ['contact_address_zip_code']);
    }

    /** @test */
    public function update_blocked_changing_vat_payer_flag_if_company_has_invoice()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');

        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        $data = $this->requestData();
        $data['vat_payer'] = true;

        factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);

        $this->put('companies?selected_company_id=' . $company->id, $data);

        $this->verifyErrorResponse(421, ErrorCode::COMPANY_BLOCKED_CHANGING_VAT_PAYER_SETTING);
    }

    /**
     * @test
     * @depends update_it_updates_company_when_valid_full_data_are_sent
     */
    public function update_it_returns_valid_response_data(array $data)
    {
        // extract $data, $initial_data, $response, $company, $user
        extract($data);

        $response_obj = (object) $response['data'];
        $this->assertSame($company->id, $response_obj->id);
        $this->assertSame(trim($data->name), $response_obj->name);
        $this->assertSame(trim($data->vatin), $response_obj->vatin);
        $this->assertSame((bool) trim($data->vat_payer), $response_obj->vat_payer);
        $this->assertNull($response_obj->vat_release_reason_id);
        $this->assertNull($response_obj->vat_release_reason_note);
        $this->assertSame(trim($data->email), $response_obj->email);
        $this->assertArrayHasKey('logotype', $response['data']);
        $this->assertSame(trim($data->phone), $response_obj->phone);
        $this->assertSame(trim($data->main_address_street), $response_obj->main_address_street);
        $this->assertSame(trim($data->main_address_number), $response_obj->main_address_number);
        $this->assertSame(trim($data->main_address_zip_code), $response_obj->main_address_zip_code);
        $this->assertSame(trim($data->main_address_city), $response_obj->main_address_city);
        $this->assertSame(trim($data->main_address_country), $response_obj->main_address_country);
        $this->assertSame(
            trim($data->contact_address_street),
            $response_obj->contact_address_street
        );
        $this->assertSame(
            trim($data->contact_address_number),
            $response_obj->contact_address_number
        );
        $this->assertSame(
            trim($data->contact_address_zip_code),
            $response_obj->contact_address_zip_code
        );
        $this->assertSame(trim($data->contact_address_city), $response_obj->contact_address_city);
        $this->assertSame(
            trim($data->contact_address_country),
            $response_obj->contact_address_country
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $response_obj->contact_address_country
        );
        $this->assertSame(
            trim($data->contact_address_country),
            $response_obj->contact_address_country
        );
        $this->assertSame(
            $initial_data->default_payment_term_days,
            $response_obj->default_payment_term_days
        );
        $this->assertSame(
            $initial_data->default_payment_method_id,
            $response_obj->default_payment_method_id
        );
        $this->assertSame($initial_data->creator_id, $response_obj->creator_id);
        $this->assertNotEquals($initial_data->editor_id, $response_obj->editor_id);
        $this->assertSame($user->id, $response_obj->editor_id);
        $this->assertTrue($response_obj->vat_settings_is_editable);

        $bank_account = $response_obj->bank_accounts['data'];
        $expected_bank_accounts = $data->bank_accounts;
        $this->assertTrue((bool) $bank_account[0]['default']);
        $this->assertSame($expected_bank_accounts[0]['number'], $bank_account[0]['number']);
        $this->assertSame($expected_bank_accounts[0]['bank_name'], $bank_account[0]['bank_name']);

        $this->assertFalse((bool) $bank_account[1]['default']);
        $this->assertSame($expected_bank_accounts[1]['number'], $bank_account[1]['number']);
        $this->assertSame($expected_bank_accounts[1]['bank_name'], $bank_account[1]['bank_name']);
    }

    /** @test */
    public function update_it_doesnt_allow_to_update_not_assigned_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $otherCompany = factory(Company::class)->create();

        $this->put('companies?selected_company_id=' . $otherCompany->id, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function update_it_doesnt_allow_to_update_when_employee()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::EMPLOYEE, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $this->put('companies?selected_company_id=' . $company->id, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function showCurrent_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        $bank_account_details = [
            'number' => 9876543210,
            'bank_name' => 'bank_name_account',
            'default' => (int) true,
        ];
        $company->bankAccounts()->save(factory(BankAccount::class)->make($bank_account_details));
        $company->vatin = 123456789;
        $company->country_vatin_prefix_id = 1;
        $vat_release_reason = VatReleaseReason::findBySlug(VatReleaseReasonType::INCOME);
        $company->vatReleaseReason()->associate($vat_release_reason);
        $company->vat_release_reason_note = 'reason note';
        $company->save();

        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseCompany = $this->decodeResponseJson()['data'];
        foreach (array_except(
            $company->getAttributes(),
            ['package_id', 'package_until']
        ) as $key => $value) {
            $this->assertEquals($value, $responseCompany[$key]);
        }

        $start_package = Package::where('slug', Package::START)->first();

        $this->assertEquals([
            'id' => $start_package->id,
            'slug' => $start_package->slug,
            'expires_at' => null,
        ], $responseCompany['real_package']['data']);
        $this->assertEquals('AF', $responseCompany['vatin_prefix']['data']['key']);
        $this->assertEquals('AF123456789', $responseCompany['full_vatin']);
        $this->assertTrue($responseCompany['vat_settings_is_editable']);
        $this->assertSame($vat_release_reason->id, $responseCompany['vat_release_reason_id']);
        $this->assertSame('reason note', $responseCompany['vat_release_reason_note']);
        $this->assertArraySubset(
            $bank_account_details,
            $responseCompany['bank_accounts']['data']['0']
        );
    }

    /** @test */
    public function showCurrent_return_vat_settings_is_not_editable()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $company->save();

        factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);

        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseCompany = $this->decodeResponseJson()['data'];

        $this->assertFalse($responseCompany['vat_settings_is_editable']);
    }

    /** @test */
    public function showCurrent_success_when_package_not_expired()
    {
        $date = Carbon::now()->addDays(10);
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, $date);

        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseCompany = $this->decodeResponseJson()['data'];
        foreach (array_except(
            $company->getAttributes(),
            ['package_id', 'package_until']
        ) as $key => $value) {
            $this->assertEquals($value, $responseCompany[$key]);
        }

        $premium_package = Package::where('slug', Package::PREMIUM)->first();

        $this->assertEquals([
            'id' => $premium_package->id,
            'slug' => $premium_package->slug,
            'expires_at' => $date->toDateTimeString(),
        ], $responseCompany['real_package']['data']);
    }

    /** @test */
    public function current_structure_with_default_application_settings()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $company->bankAccounts()->save(factory(BankAccount::class)->make());
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'vatin',
                    'vat_payer',
                    'vat_release_reason_id',
                    'vat_release_reason_note',
                    'vat_settings_is_editable',
                    'email',
                    'phone',
                    'bank_accounts' => [
                        'data' => [
                            [
                                'id',
                                'number',
                                'bank_name',
                                'default',
                            ],
                        ],
                    ],
                    'app_settings' => [
                        [
                            'slug',
                            'value',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function current_without_any_application_settings()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Module::whereRaw('1=1')->delete();

        $this->assertSame(0, Module::count());
        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];

        $this->assertSame(0, count($application_settings));
    }

    /** @test */
    public function current_with_all_default_application_settings_for_start_package()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];
        $this->verifyStartPackage($application_settings);
    }

    /** @test */
    public function current_with_all_default_application_settings_for_cep_free_package()
    {
        config()->set('app_settings.package_portal_name', 'ab');

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_FREE);
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];
        $this->verifyCepFreePackage($application_settings);
    }

    /** @test */
    public function current_with_all_default_application_settings_for_cep_classic_package()
    {
        config()->set('app_settings.package_portal_name', 'ab');

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_CLASSIC);
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];
        $this->verifyCepClassicPackage($application_settings);
    }

    /** @test */
    public function current_with_all_default_application_settings_for_cep_business_package()
    {
        config()->set('app_settings.package_portal_name', 'ab');

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_BUSINESS);
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];
        $this->verifyCepBusinessPackage($application_settings);
    }

    /** @test */
    public function current_with_all_default_application_settings_for_cep_enterprise_package()
    {
        config()->set('app_settings.package_portal_name', 'ab');

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_ENTERPRISE);
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];
        $this->verifyCepEnterprisePackage($application_settings);
    }

    /** @test */
    public function current_with_all_default_application_settings_for_icontrol_package()
    {
        config()->set('app_settings.package_portal_name', 'icontrol');

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::ICONTROL);
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];
        $this->verifyIcontrolPackage($application_settings);
    }

    /** @test */
    public function current_with_all_default_application_settings_for_premium_package()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(
            RoleType::OWNER,
            Package::PREMIUM,
            Carbon::now()->addDays(10)
        );
        auth()->loginUsingId($this->user->id);

        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $application_settings = $this->decodeResponseJson()['data']['app_settings'];
        $this->verifyPremiumPackage($application_settings);
    }

    /** @test */
    public function current_with_customize_application_settings()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);

        $module = Module::where('slug', ModuleType::GENERAL_WELCOME_URL)->first();
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)
            ->update(['value' => 'other_url']);

        auth()->loginUsingId($this->user->id);
        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];
        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $response['app_settings'][0]['slug']
        );
        $this->assertTrue(! (bool) $response['app_settings'][0]['value']);
        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $response['app_settings'][1]['slug']
        );
        $this->assertTrue((bool) $response['app_settings'][1]['value']);
        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $response['app_settings'][2]['slug']
        );
        $this->assertSame('other_url', $response['app_settings'][2]['value']);
    }

    /** @test */
    public function showCurrent_without_correct_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $otherCompany = factory(Company::class)->create();

        $this->get('companies/current?selected_company_id=' . $otherCompany->id)
            ->seeStatusCode(401)
            ->isJson();
    }

    /** @test */
    public function current_with_blockaded_company_for_owner()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $company->blockade_company = 'test';
        $company->save();

        auth()->loginUsingId($this->user->id);
        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(200);
    }

    /** @test */
    public function current_with_blockaded_company_for_non_owner()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $company->blockade_company = 'test';
        $company->save();

        auth()->loginUsingId($this->user->id);
        $this->get('companies/current?selected_company_id=' . $company->id)
            ->seeStatusCode(401);
    }

    /** @test */
    public function index_regular_user_has_no_permission()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->get('companies')->assertResponseStatus(401);
    }

    /** @test */
    public function index_super_user_has_permission()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);
        $this->get('companies')->assertResponseOk();
    }

    /** @test */
    public function index_retrieve_amount_of_companies_equal_to_amount_add_to_database()
    {
        Company::whereRaw('1 = 1')->delete();
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);
        $companies_amount = 5;
        $companies = factory(Company::class, 5)->create();

        $this->get('companies')->assertResponseOk();

        $json = $this->decodeResponseJson();
        $companies_list = $json['data'];

        $this->assertSame($companies_amount, count($companies_list));
    }

    /** @test */
    public function index_structure_response()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);
        $companies = factory(Company::class)->create();

        $this->get('companies')
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'vatin',
                        'email',
                        'blockade_company',
                        'phone',
                        'force_calendar_to_complete',
                        'enable_calendar',
                        'enable_activity',
                        'main_address_street',
                        'main_address_number',
                        'main_address_zip_code',
                        'main_address_city',
                        'main_address_country',
                        'contact_address_street',
                        'contact_address_number',
                        'contact_address_zip_code',
                        'contact_address_city',
                        'contact_address_country',
                        'default_payment_term_days',
                        'default_payment_method_id',
                        'creator_id',
                        'editor_id',
                        'created_at',
                        'updated_at',
                        'bank_accounts' => [
                            'data' => [],
                        ],
                    ],
                ],
                'exec_time',
            ]);
    }

    /** @test */
    public function index_response_has_data_correct()
    {
        $this->createUser();
        $this->setSuperAdminPermissionForUser();
        auth()->loginUsingId($this->user->id);
        $company = factory(Company::class)->create();
        $expected_bank_account = factory(BankAccount::class)->make();
        $company->bankAccounts()->save($expected_bank_account);
        $this->get('companies');

        $json = $this->response->json();
        $companies_item = array_pop($json['data']);
        $company = $company->fresh();
        $this->assertSame($company->id, $companies_item['id']);
        $this->assertSame($company->name, $companies_item['name']);
        $this->assertSame($company->vatin, $companies_item['vatin']);
        $this->assertSame($company->email, $companies_item['email']);
        $this->assertSame($company->blockade_company, $companies_item['blockade_company']);
        $this->assertSame($company->phone, $companies_item['phone']);
        $this->assertSame(
            $company->force_calendar_to_complete,
            $companies_item['force_calendar_to_complete']
        );
        $this->assertSame($company->enable_calendar, $companies_item['enable_calendar']);
        $this->assertSame($company->enable_activity, $companies_item['enable_activity']);
        $this->assertSame($company->main_address_street, $companies_item['main_address_street']);
        $this->assertSame($company->main_address_number, $companies_item['main_address_number']);
        $this->assertSame(
            $company->main_address_zip_code,
            $companies_item['main_address_zip_code']
        );
        $this->assertSame($company->main_address_city, $companies_item['main_address_city']);
        $this->assertSame($company->main_address_country, $companies_item['main_address_country']);
        $this->assertSame(
            $company->contact_address_street,
            $companies_item['contact_address_street']
        );
        $this->assertSame(
            $company->contact_address_number,
            $companies_item['contact_address_number']
        );
        $this->assertSame(
            $company->contact_address_zip_code,
            $companies_item['contact_address_zip_code']
        );
        $this->assertSame($company->contact_address_city, $companies_item['contact_address_city']);
        $this->assertSame(
            $company->contact_address_country,
            $companies_item['contact_address_country']
        );
        $this->assertSame(
            $company->default_payment_term_days,
            $companies_item['default_payment_term_days']
        );
        $this->assertSame(
            $company->default_payment_method_id,
            $companies_item['default_payment_method_id']
        );
        $this->assertSame($company->creator_id, $companies_item['creator_id']);
        $this->assertSame($company->editor_id, $companies_item['editor_id']);

        $bank_account = array_get($companies_item, 'bank_accounts.data.0');

        $this->assertSame($expected_bank_account->number, $bank_account['number']);
        $this->assertSame($expected_bank_account->bank_name, $bank_account['bank_name']);
        $this->assertSame((int) $expected_bank_account->default, $bank_account['default']);
        $this->assertSame($expected_bank_account->id, $bank_account['id']);
    }

    /** @test */
    public function update_default_payment_method_validation_error_without_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put('companies/default-payment-method?selected_company_id=' . $company->id, [])
            ->seeStatusCode(422);

        $this->verifyValidationResponse(['default_payment_method_id']);
    }

    /** @test */
    public function update_default_payment_method_invalid_method_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put('companies/default-payment-method?selected_company_id=' . $company->id, [
            'default_payment_method_id' => 'abc',
        ])->seeStatusCode(422);

        $this->verifyValidationResponse(['default_payment_method_id']);
    }

    /** @test */
    public function indexCountryVatinPrefixes_get_list_of_prefixes()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('companies/country-vatin-prefixes?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $data = $this->response->getData()->data;

        $dbPrefixes = CountryVatinPrefix::all()->sortBy('id');
        foreach ($data as $key => $prefix) {
            $this->assertEquals($dbPrefixes[$key]->name, $prefix->name);
        }
    }

    /** @test */
    public function update_default_payment_method_valid_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $company->default_payment_method_id = 1;
        $company->save();
        auth()->loginUsingId($this->user->id);

        $payment_methods = factory(PaymentMethod::class, 5)->create();

        $this->put('companies/default-payment-method?selected_company_id=' . $company->id, [
            'default_payment_method_id' => $payment_methods[3]->id,
        ])->seeStatusCode(200);

        $company_fresh = $company->fresh();

        $this->assertEquals($payment_methods[3]->id, $company_fresh->default_payment_method_id);

        $this->assertSame($company->id, $company_fresh->id);
        $this->assertSame($company->name, $company_fresh->name);
        $this->assertSame($company->vatin, $company_fresh->vatin);
        $this->assertSame($company->email, $company_fresh->email);
        $this->assertSame($company->phone, $company_fresh->phone);
        $this->assertSame(
            trim($company->main_address_street),
            trim($company_fresh->main_address_street)
        );
        $this->assertSame(
            trim($company->main_address_number),
            trim($company_fresh->main_address_number)
        );
        $this->assertSame(
            trim($company->main_address_zip_code),
            trim($company_fresh->main_address_zip_code)
        );
        $this->assertSame(
            trim($company->main_address_city),
            trim($company_fresh->main_address_city)
        );
        $this->assertSame(
            trim($company->main_address_country),
            trim($company_fresh->main_address_country)
        );
        $this->assertSame(
            trim($company->contact_address_street),
            trim($company_fresh->contact_address_street)
        );
        $this->assertSame(
            trim($company->contact_address_number),
            trim($company_fresh->contact_address_number)
        );
        $this->assertSame(
            trim($company->contact_address_zip_code),
            trim($company_fresh->contact_address_zip_code)
        );
        $this->assertSame(
            trim($company->contact_address_city),
            trim($company_fresh->contact_address_city)
        );
        $this->assertSame(
            trim($company->contact_address_country),
            trim($company_fresh->contact_address_country)
        );
        $this->assertSame(
            trim($company->contact_address_country),
            trim($company_fresh->contact_address_country)
        );
        $this->assertSame(
            trim($company->contact_address_country),
            trim($company_fresh->contact_address_country)
        );
        $this->assertSame(
            trim($company->default_payment_term_days),
            trim($company_fresh->default_payment_term_days)
        );
        $this->assertSame($company->creator_id, $company_fresh->creator_id);
        $this->assertSame($this->user->id, $company_fresh->editor_id);
    }

    /** @test */
    public function update_sending_null_as_logotype()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['logotype'] = null;

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $updated_company = $this->response->getData()->data;
        $this->assertEmpty($updated_company->logotype);
    }

    /** @test */
    public function update_uploading_file()
    {
        $files = $this->file_environment();

        $file = new UploadedFile($files->logo, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();

        $this->call(
            'PUT',
            'companies?selected_company_id=' . $company->id,
            $data,
            [],
            ['logotype' => $file]
        );
        $this->assertResponseOk();

        $updated_company = $this->response->getData()->data;
        $this->file_name = $updated_company->logotype;
        $this->assertNotEmpty($updated_company->logotype);
        Storage::disk('logotypes')->exists($updated_company->logotype);
    }

    /** @test */
    public function update_uploading_too_height_file()
    {
        $files = $this->file_environment();

        $file = new UploadedFile($files->height, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['logotype'] = $file;

        $this->call(
            'PUT',
            'companies?selected_company_id=' . $company->id,
            $data,
            [],
            ['logotype' => $file]
        );
        $this->assertResponseOk();

        $updated_company = $this->response->getData()->data;
        $this->file_name = $updated_company->logotype;
        $this->assertNotEmpty($this->file_name);
        Storage::disk('logotypes')->exists($this->file_name);
        $size = getimagesize(storage_path('logotypes/') . $this->file_name);
        $this->assertEquals(75, $size[0]);
        $this->assertEquals(300, $size[1]);
    }

    /** @test */
    public function update_uploading_too_width_file()
    {
        $files = $this->file_environment();

        $file = new UploadedFile($files->width, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();

        $this->call(
            'PUT',
            'companies?selected_company_id=' . $company->id,
            $data,
            [],
            ['logotype' => $file]
        );
        $this->assertResponseOk();

        $updated_company = $this->response->getData()->data;
        $this->file_name = $updated_company->logotype;
        $this->assertNotEmpty($this->file_name);
        Storage::disk('logotypes')->exists($this->file_name);
        $size = getimagesize(storage_path('logotypes/') . $this->file_name);
        $this->assertEquals(300, $size[0]);
        $this->assertEquals(75, $size[1]);
    }

    /** @test */
    public function update_updating_logotype()
    {
        $files = $this->file_environment();
        File::copy(
            storage_path('phpunit_tests/samples/avatar_1.4mb.jpg'),
            storage_path('logotypes/old_avatar.jpg')
        );

        $new_file = new UploadedFile($files->logo, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $initial_data['logotype'] = 'old_avatar.jpg';
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();

        $this->call(
            'PUT',
            'companies?selected_company_id=' . $company->id,
            $data,
            [],
            ['logotype' => $new_file]
        );
        $this->assertResponseOk();

        $updated_company = $this->response->getData()->data;
        $this->file_name = $updated_company->logotype;
        $this->assertNotEmpty($updated_company->logotype);
        Storage::disk('logotypes')->exists($updated_company->logotype);
        Storage::disk('logotypes')->assertAbsent('old_avatar.jpg');
    }

    /** @test */
    public function update_delete_and_upload_throw_error()
    {
        $files = $this->file_environment();

        $new_file = new UploadedFile($files->big, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $initial_data['logotype'] = 'old_avatar.jpg';
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['remove_logotype'] = 1;

        $this->call(
            'PUT',
            'companies?selected_company_id=' . $company->id,
            $data,
            [],
            ['logotype' => $new_file]
        );
        $this->seeStatusCode(422);

        $this->verifyValidationResponse(['remove_logotype']);
    }

    /** @test */
    public function update_deleting_logotype()
    {
        $files = $this->file_environment();
        File::copy(
            storage_path('phpunit_tests/samples/avatar.jpg'),
            storage_path('logotypes/old_avatar.jpg')
        );

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $initial_data['logotype'] = 'old_avatar.jpg';
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['remove_logotype'] = 1;

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $updated_company = $this->response->getData()->data;
        $this->assertEmpty($updated_company->logotype);
        Storage::disk('logotypes')->assertAbsent('old_avatar.jpg');
    }

    /** @test */
    public function update_deleting_logotype_used_in_invoice()
    {
        $files = $this->file_environment();
        File::copy(
            storage_path('phpunit_tests/samples/avatar.jpg'),
            storage_path('logotypes/old_avatar.jpg')
        );

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $initial_data['logotype'] = 'old_avatar.jpg';
        $company->forceFill($initial_data)->save();
        factory(InvoiceCompany::class)->create([
            'company_id' => $company->id,
            'logotype' => 'old_avatar.jpg',
        ]);

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['remove_logotype'] = 1;

        $this->put('companies?selected_company_id=' . $company->id, $data)->assertResponseOk();

        $updated_company = $this->response->getData()->data;
        $this->assertEmpty($updated_company->logotype);
        Storage::disk('logotypes')->exists('old_avatar.jpg');
        // Delete test file
        Storage::disk('logotypes')->delete('old_avatar.jpg');
        Storage::disk('logotypes')->assertAbsent('old_avatar.jpg');
    }

    /** @test */
    public function update_too_big_file_size()
    {
        $files = $this->file_environment();

        $file = new UploadedFile($files->too_big, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['logotype'] = $file;

        $this->put('companies?selected_company_id=' . $company->id, $data)->seeStatusCode(422);

        $this->verifyValidationResponse(['logotype']);
    }

    /** @test */
    public function update_too_big_image_dimensions()
    {
        $files = $this->file_environment();

        $file = new UploadedFile($files->big, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['logotype'] = $file;

        $this->put('companies?selected_company_id=' . $company->id, $data)->seeStatusCode(422);

        $this->verifyValidationResponse(['logotype']);
    }

    /** @test */
    public function update_too_small_image_dimensions()
    {
        $files = $this->file_environment();

        $file = new UploadedFile($files->small, 'avatar.jpg', 'image/jpeg', null, true);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        // now prepare data to send
        $data = $this->requestData();
        $data['logotype'] = $file;

        $this->put('companies?selected_company_id=' . $company->id, $data)->seeStatusCode(422);

        $this->verifyValidationResponse(['logotype']);
    }

    /** @test */
    public function getLogotype_receiving_logotype_with_success()
    {
        File::copy(
            storage_path('phpunit_tests/samples/avatar.jpg'),
            storage_path('logotypes/old_avatar.jpg')
        );
        $this->file_name = 'old_avatar.jpg';

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $initial_data['logotype'] = 'old_avatar.jpg';
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        $this->get('companies/get-logotype?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $data = $this->response->getContent();
        $text_content = file_get_contents(storage_path('logotypes/old_avatar.jpg'));

        $this->assertEquals($text_content, $data);
    }

    /** @test */
    public function getLogotype_no_logotype_for_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        $this->get('companies/get-logotype?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $data = $this->response->getData()->data;
        $this->assertEmpty($data);
    }

    /** @test */
    public function getLogotype_for_selected_company_receiving_logotype_with_success()
    {
        File::copy(
            storage_path('phpunit_tests/samples/avatar.jpg'),
            storage_path('logotypes/old_avatar.jpg')
        );
        $this->file_name = 'old_avatar.jpg';

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $initial_data['logotype'] = 'old_avatar.jpg';
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        $this->get('companies/get-logotype/' . $company->id . '?selected_company_id=' .
            $company->id)
            ->assertResponseOk();

        $data = $this->response->getContent();
        $text_content = file_get_contents(storage_path('logotypes/old_avatar.jpg'));

        $this->assertEquals($text_content, $data);
    }

    /** @test */
    public function getLogotype_for_selected_company_no_logotype_for_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        $this->get('companies/get-logotype/' . $company->id . '?selected_company_id=' .
            $company->id)
            ->assertResponseOk();

        $data = $this->response->getData()->data;
        $this->assertEmpty($data);
    }

    /** @test */
    public function getLogotype_for_selected_other_company_receiving_logotype_with_success()
    {
        File::copy(
            storage_path('phpunit_tests/samples/avatar.jpg'),
            storage_path('logotypes/old_avatar.jpg')
        );
        $this->file_name = 'old_avatar.jpg';

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $company2 = $this->createCompanyWithRole(RoleType::DEVELOPER);

        // set initial data to user company
        $initial_data = $this->initialData();
        $initial_data['logotype'] = 'old_avatar.jpg';
        $company->forceFill($initial_data)->save();

        auth()->loginUsingId($this->user->id);

        $this->get('companies/get-logotype/' . $company->id . '?selected_company_id=' .
            $company2->id)
            ->assertResponseOk();

        $data = $this->response->getContent();
        $text_content = file_get_contents(storage_path('logotypes/old_avatar.jpg'));

        $this->assertEquals($text_content, $data);
    }

    /** @test */
    public function getGusData_get_correct_data_company()
    {
        $vatin = 5261040828;
        $gus_data [] = $this->getGusData($vatin);
        $this->gus->method('pullDataFromServer')->willReturn($gus_data);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(200)->isJson();

        $this->assertCount(1, $this->response->getData()->data);
        $data = $this->response->getData()->data[0];

        $this->assertEquals('GŁÓWNY URZĄD STATYSTYCZNY', $data->name);
        $this->assertEquals($vatin, $data->vatin);
        $this->assertEquals('00033150100000', $data->regon);
        $this->assertEquals(208, $data->main_address_number);
        $this->assertEquals('Aleja Niepodległości', $data->main_address_street);
        $this->assertEquals('00-925', $data->main_address_zip_code);
        $this->assertEquals('Warszawa', $data->main_address_city);
        $this->assertEquals('POLSKA', $data->main_address_country);
        $this->assertEquals('6083000', $data->phone);
        $this->assertEquals('dgsek@stat.gov.pl', $data->email);
        $this->assertEquals('www.stat.gov.pl', $data->website);
    }

    /** @test */
    public function getGusData_get_correct_data_for_vatin_with_dashes()
    {
        $gus_data [] = $this->getGusData(5261040828);
        $this->gus->method('pullDataFromServer')->with(5261040828)->willReturn($gus_data);
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $vatin = '526-104-08-28';
        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(200)->isJson();

        $this->assertCount(1, $this->response->getData()->data);
        $data = $this->response->getData()->data[0];

        $this->assertEquals('GŁÓWNY URZĄD STATYSTYCZNY', $data->name);
        $this->assertEquals('5261040828', $data->vatin);
        $this->assertEquals('00033150100000', $data->regon);
        $this->assertEquals(208, $data->main_address_number);
        $this->assertEquals('Aleja Niepodległości', $data->main_address_street);
        $this->assertEquals('00-925', $data->main_address_zip_code);
        $this->assertEquals('Warszawa', $data->main_address_city);
        $this->assertEquals('POLSKA', $data->main_address_country);
        $this->assertEquals('6083000', $data->phone);
        $this->assertEquals('dgsek@stat.gov.pl', $data->email);
        $this->assertEquals('www.stat.gov.pl', $data->website);
    }

    /** @test */
    public function getGusData_get_correct_data_economic_activity()
    {
        $vatin = 5992819905;
        $gus_data [] = $this->getGusDataEconomic($vatin);
        $this->gus->method('pullDataFromServer')->with($vatin)->willReturn($gus_data);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $vatin = 5992819905;
        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(200)->isJson();

        $this->assertCount(1, $this->response->getData()->data);
        $this->assertCount(1, GusCompany::all());
        $data = $this->response->getData()->data[0];

        $this->assertEquals('AND MDX MAGDALENA ŚLEBIODA', $data->name);
        $this->assertEquals($vatin, $data->vatin);
        $this->assertEquals('302040833', $data->regon);
        $this->assertEquals(3, $data->main_address_number);
        $this->assertEquals('ul. Skryta', $data->main_address_street);
        $this->assertEquals('64-930', $data->main_address_zip_code);
        $this->assertEquals('Szydłowo', $data->main_address_city);
        $this->assertEquals('POLSKA', $data->main_address_country);
        $this->assertEquals('', $data->phone);
        $this->assertEquals('magdalena.slebioda@wp.pl', $data->email);
        $this->assertEquals('', $data->website);
    }

    /** @test */
    public function getGusData_get_correct_data_for_company_hold_off()
    {
        $vatin = 9721146394;
        $gus_data [] = $this->getGusDataEconomic($vatin);
        $this->gus->method('pullDataFromServer')->with($vatin)->willReturn($gus_data);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(200)->isJson();

        $this->assertCount(1, $this->response->getData()->data);
    }

    /** @test */
    public function getGusData_get_correct_data_from_DB()
    {
        $vatin = 123;
        $this->gus->method('pullDataFromServer')->with($vatin)->willReturn([]);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        factory(GusCompany::class)->create([
            'name' => 'Best company',
            'vatin' => '123',
            'main_address_country' => 'Kabuto',
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'Poznań',
            'main_address_street' => 'Some street',
            'main_address_number' => '7',
            'phone' => '0700',
            'email' => 'some@email.pl',
            'website' => 'website.pl',
        ]);

        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(200)->isJson();

        $this->assertCount(1, $this->response->getData()->data);
        $data = $this->response->getData()->data[0];
        $this->assertEquals('Best company', $data->name);
        $this->assertEquals(123, $data->vatin);
        $this->assertEquals(7, $data->main_address_number);
        $this->assertEquals('Some street', $data->main_address_street);
        $this->assertEquals('11-111', $data->main_address_zip_code);
        $this->assertEquals('Poznań', $data->main_address_city);
        $this->assertEquals('Kabuto', $data->main_address_country);
        $this->assertEquals('0700', $data->phone);
        $this->assertEquals('some@email.pl', $data->email);
        $this->assertEquals('website.pl', $data->website);
    }

    /** @test */
    public function getGusData_check_saving_data_to_DB()
    {
        $vatin = 5261040828;
        $gus_data [] = $this->getGusData($vatin);
        $this->gus->method('pullDataFromServer')->with($vatin)->willReturn($gus_data);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->assertCount(0, GusCompany::all());

        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(200)->isJson();

        $this->assertCount(1, GusCompany::all());
        $data = GusCompany::first();

        $this->assertEquals('GŁÓWNY URZĄD STATYSTYCZNY', $data->name);
        $this->assertEquals($vatin, $data->vatin);
        $this->assertEquals(208, $data->main_address_number);
        $this->assertEquals('Aleja Niepodległości', $data->main_address_street);
        $this->assertEquals('00-925', $data->main_address_zip_code);
        $this->assertEquals('Warszawa', $data->main_address_city);
        $this->assertEquals('POLSKA', $data->main_address_country);
        $this->assertEquals('6083000', $data->phone);
        $this->assertEquals('dgsek@stat.gov.pl', $data->email);
        $this->assertEquals('www.stat.gov.pl', $data->website);
    }

    /** @test */
    public function getGusData_wrong_vatin_returns_empty_array()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = factory(Company::class)->create();
        $this->assignUsersToCompany(User::all(), $company, RoleType::OWNER);

        $vatin = 123;
        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(200)->isJson();

        $data = $this->response->getData()->data;
        $this->assertEmpty($data);
    }

    /** @test */
    public function getGusData_too_long_vatin_returns_validation_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = factory(Company::class)->create();
        $this->assignUsersToCompany(User::all(), $company, RoleType::OWNER);

        $vatin = 'over15characters';
        $this->get(
            'companies/get-gus-data?vatin=' . $vatin
        )->seeStatusCode(422);

        $this->verifyValidationResponse(['vatin']);
    }

    /** @test */
    public function updateSettings_it_returns_validation_error_without_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $this->put('/companies/settings?selected_company_id=' . $company->id, []);

        $this->verifyValidationResponse([
            'force_calendar_to_complete',
            'enable_calendar',
            'enable_activity',
        ]);
    }

    /** @test */
    public function updateSettings_error_has_not_permission()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);

        $this->put('/companies/settings?selected_company_id=' . $company->id, []);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function updateSettings_success_db()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $company->force_calendar_to_complete = false;
        $company->enable_calendar = false;
        $company->enable_activity = false;
        $company->save();

        $data = [
            'force_calendar_to_complete' => true,
            'enable_calendar' => true,
            'enable_activity' => true,
        ];

        $this->put('/companies/settings?selected_company_id=' . $company->id, $data);

        $company = $company->fresh();

        $this->assertSame(1, $company->force_calendar_to_complete);
        $this->assertSame(1, $company->enable_calendar);
        $this->assertSame(1, $company->enable_activity);
    }

    protected function update_it_returns_valid_response_structure(array $data)
    {
        $this->seeJsonStructure([
            'data' => [
                'id',
                'name',
                'vatin',
                'vat_payer',
                'vat_release_reason_id',
                'vat_release_reason_note',
                'email',
                'phone',
                'main_address_street',
                'main_address_number',
                'main_address_zip_code',
                'main_address_city',
                'main_address_country',
                'contact_address_street',
                'contact_address_number',
                'contact_address_zip_code',
                'contact_address_city',
                'contact_address_country',
                'default_payment_term_days',
                'default_payment_method_id',
                'default_invoice_gross_counted',
                'creator_id',
                'editor_id',
                'created_at',
                'updated_at',
                'bank_accounts',
            ],
            'exec_time',
        ], $data['response']);
        $this->seeJsonStructure([
            'data' => [
                [
                    'number',
                    'bank_name',
                    'default',
                ],
            ],
        ], $data['response']['data']['bank_accounts']);
    }

    protected function file_environment()
    {
        $files = new \stdClass();
        $files->dir = storage_path('phpunit_tests/samples');
        $files->logo = storage_path('phpunit_tests/samples/avatar.jpg');
        $files->small = storage_path('phpunit_tests/samples/small_avatar.jpg');
        $files->big = storage_path('phpunit_tests/samples/avatar_1.4mb.jpg');
        $files->too_big = storage_path('phpunit_tests/samples/phpunit_test.txt');
        $files->height = storage_path('phpunit_tests/samples/avatar_height.jpg');
        $files->width = storage_path('phpunit_tests/samples/avatar_width.jpg');

        return $files;
    }

    protected function verifyStartPackageDb($company)
    {
        $package_id = Package::where('slug', Package::START)->first()->id;
        $companyModules = $company->companyModules;
        $modules = [];

        $mod_prices = ModPrice::where(function ($q) use ($package_id) {
            $q->where('package_id', $package_id);
            $q->where('default', 1);
            $q->where('currency', 'PLN');
        })->orWhere(function ($q) {
            $q->orWhereNull('package_id');
            $q->where('default', 1);
            $q->where('currency', 'PLN');
        })->get();

        $transaction = Transaction::orderByDesc('id')->first();

        foreach ($mod_prices as $mod) {
            $modules[$mod->moduleMod->module_id] = $mod->moduleMod->value;
        }

        $this->assertSame(count($modules), count($companyModules));

        foreach ($companyModules as $module) {
            $this->assertEquals($module->value, $modules[$module->module_id]);

            $history = CompanyModuleHistory::where('company_id', $module->company_id)
                ->where('module_id', $module->module_id)->first();
            $this->assertEquals($transaction->id, $history->transaction_id);
            $this->assertEquals($module->value, $history->new_value);
        }
    }

    protected function verifyCepPackageDb($company)
    {
        $package_id = Package::where('slug', Package::CEP_FREE)->first()->id;
        $companyModules = $company->companyModules;
        $modules = [];

        $mod_prices = ModPrice::where(function ($q) use ($package_id) {
            $q->where('package_id', $package_id);
            $q->where('default', 1);
            $q->where('currency', 'PLN');
        })->orWhere(function ($q) {
            $q->orWhereNull('package_id');
            $q->where('default', 1);
            $q->where('currency', 'PLN');
        })->get();

        $transaction = Transaction::orderByDesc('id')->first();

        foreach ($mod_prices as $mod) {
            $modules[$mod->moduleMod->module_id] = $mod->moduleMod->value;
        }

        $this->assertSame(count($modules), count($companyModules));

        foreach ($companyModules as $module) {
            $this->assertEquals($module->value, $modules[$module->module_id]);

            $history = CompanyModuleHistory::where('company_id', $module->company_id)
                ->where('module_id', $module->module_id)->first();
            $this->assertEquals($transaction->id, $history->transaction_id);
            $this->assertEquals($module->value, $history->new_value);
        }
    }

    protected function verifyStartPackage($application_settings)
    {
        $this->checkSettingsCount($application_settings);

        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $application_settings[0]['slug']
        );
        $this->assertFalse((bool) $application_settings[0]['value']);

        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $application_settings[1]['slug']
        );
        $this->assertTrue((bool) $application_settings[1]['value']);

        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $application_settings[2]['slug']
        );
        $this->assertSame('app.dashboard', $application_settings[2]['value']);

        $this->assertSame(
            ModuleType::GENERAL_COMPANIES_VISIBLE,
            $application_settings[3]['slug']
        );
        $this->assertTrue((bool) $application_settings[3]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ACTIVE,
            $application_settings[4]['slug']
        );
        $this->assertTrue((bool) $application_settings[4]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            $application_settings[5]['slug']
        );
        $this->assertFalse((bool) $application_settings[5]['value']);

        $this->assertSame(
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            $application_settings[6]['slug']
        );
        $this->assertFalse((bool) $application_settings[6]['value']);

        $this->assertSame(
            ModuleType::INVOICES_PROFORMA_ENABLED,
            $application_settings[7]['slug']
        );
        $this->assertFalse((bool) $application_settings[7]['value']);

        $this->assertSame(
            ModuleType::INVOICES_UE_ENABLED,
            $application_settings[8]['slug']
        );
        $this->assertFalse((bool) $application_settings[8]['value']);

        $this->assertSame(
            ModuleType::INVOICES_FOOTER_ENABLED,
            $application_settings[9]['slug']
        );
        $this->assertTrue((bool) $application_settings[9]['value']);

        $this->assertSame(
            ModuleType::RECEIPTS_ACTIVE,
            $application_settings[10]['slug']
        );
        $this->assertFalse((bool) $application_settings[10]['value']);
        $this->assertSame(
            ModuleType::INVOICES_MARGIN_ENABLED,
            $application_settings[11]['slug']
        );
        $this->assertFalse((bool) $application_settings[11]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REGISTER_EXPORT_NAME,
            $application_settings[12]['slug']
        );
        $this->assertEquals('', $application_settings[12]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
            $application_settings[13]['slug']
        );
        $this->assertFalse((bool) $application_settings[13]['value']);
        $this->assertSame(
            ModuleType::INVOICES_ADVANCE_ENABLED,
            $application_settings[14]['slug']
        );
        $this->assertFalse((bool) $application_settings[14]['value']);
        $this->assertSame(
            ModuleType::GENERAL_MULTIPLE_USERS,
            $application_settings[15]['slug']
        );
        $this->assertSame('1', $application_settings[15]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertFalse((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertFalse((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_DISC_VOLUME,
            $application_settings[17]['slug']
        );
        $this->assertFalse((bool) $application_settings[17]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_FILE_SIZE,
            $application_settings[18]['slug']
        );
        $this->assertFalse((bool) $application_settings[18]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF,
            $application_settings[19]['slug']
        );
        $this->assertFalse((bool) $application_settings[19]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_MULTIPLE_PROJECTS,
            $application_settings[20]['slug']
        );
        $this->assertFalse((bool) $application_settings[20]['value']);
    }

    protected function verifyPremiumPackage($application_settings)
    {
        $this->checkSettingsCount($application_settings);

        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $application_settings[0]['slug']
        );
        $this->assertFalse((bool) $application_settings[0]['value']);

        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $application_settings[1]['slug']
        );
        $this->assertTrue((bool) $application_settings[1]['value']);

        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $application_settings[2]['slug']
        );
        $this->assertSame('app.dashboard', $application_settings[2]['value']);

        $this->assertSame(
            ModuleType::GENERAL_COMPANIES_VISIBLE,
            $application_settings[3]['slug']
        );
        $this->assertTrue((bool) $application_settings[3]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ACTIVE,
            $application_settings[4]['slug']
        );
        $this->assertTrue((bool) $application_settings[4]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            $application_settings[5]['slug']
        );
        $this->assertTrue((bool) $application_settings[5]['value']);

        $this->assertSame(
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            $application_settings[6]['slug']
        );
        $this->assertTrue((bool) $application_settings[6]['value']);

        $this->assertSame(
            ModuleType::INVOICES_PROFORMA_ENABLED,
            $application_settings[7]['slug']
        );
        $this->assertTrue((bool) $application_settings[7]['value']);

        $this->assertSame(
            ModuleType::INVOICES_UE_ENABLED,
            $application_settings[8]['slug']
        );
        $this->assertTrue((bool) $application_settings[8]['value']);

        $this->assertSame(
            ModuleType::INVOICES_FOOTER_ENABLED,
            $application_settings[9]['slug']
        );
        $this->assertTrue((bool) $application_settings[9]['value']);

        $this->assertSame(
            ModuleType::RECEIPTS_ACTIVE,
            $application_settings[10]['slug']
        );
        $this->assertFalse((bool) $application_settings[10]['value']);
        $this->assertSame(
            ModuleType::INVOICES_MARGIN_ENABLED,
            $application_settings[11]['slug']
        );
        $this->assertTrue((bool) $application_settings[11]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REGISTER_EXPORT_NAME,
            $application_settings[12]['slug']
        );
        $this->assertEquals('', $application_settings[12]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
            $application_settings[13]['slug']
        );
        $this->assertTrue((bool) $application_settings[13]['value']);
        $this->assertSame(
            ModuleType::INVOICES_ADVANCE_ENABLED,
            $application_settings[14]['slug']
        );
        $this->assertTrue((bool) $application_settings[14]['value']);
        $this->assertSame(
            ModuleType::GENERAL_MULTIPLE_USERS,
            $application_settings[15]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[15]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertTrue((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_DISC_VOLUME,
            $application_settings[17]['slug']
        );
        $this->assertFalse((bool) $application_settings[17]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_FILE_SIZE,
            $application_settings[18]['slug']
        );
        $this->assertFalse((bool) $application_settings[18]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF,
            $application_settings[19]['slug']
        );
        $this->assertFalse((bool) $application_settings[19]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_MULTIPLE_PROJECTS,
            $application_settings[20]['slug']
        );
        $this->assertFalse((bool) $application_settings[20]['value']);
    }

    protected function verifyCepFreePackage($application_settings)
    {
        $this->checkSettingsCount($application_settings);

        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $application_settings[0]['slug']
        );
        $this->assertTrue((bool) $application_settings[0]['value']);

        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $application_settings[1]['slug']
        );
        $this->assertTrue((bool) $application_settings[1]['value']);

        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $application_settings[2]['slug']
        );
        $this->assertSame('app.projects-list', $application_settings[2]['value']);

        $this->assertSame(
            ModuleType::GENERAL_COMPANIES_VISIBLE,
            $application_settings[3]['slug']
        );
        $this->assertTrue((bool) $application_settings[3]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ACTIVE,
            $application_settings[4]['slug']
        );
        $this->assertFalse((bool) $application_settings[4]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            $application_settings[5]['slug']
        );
        $this->assertFalse((bool) $application_settings[5]['value']);

        $this->assertSame(
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            $application_settings[6]['slug']
        );
        $this->assertFalse((bool) $application_settings[6]['value']);

        $this->assertSame(
            ModuleType::INVOICES_PROFORMA_ENABLED,
            $application_settings[7]['slug']
        );
        $this->assertFalse((bool) $application_settings[7]['value']);

        $this->assertSame(
            ModuleType::INVOICES_UE_ENABLED,
            $application_settings[8]['slug']
        );
        $this->assertFalse((bool) $application_settings[8]['value']);

        $this->assertSame(
            ModuleType::INVOICES_FOOTER_ENABLED,
            $application_settings[9]['slug']
        );
        $this->assertFalse((bool) $application_settings[9]['value']);

        $this->assertSame(
            ModuleType::RECEIPTS_ACTIVE,
            $application_settings[10]['slug']
        );
        $this->assertFalse((bool) $application_settings[10]['value']);
        $this->assertSame(
            ModuleType::INVOICES_MARGIN_ENABLED,
            $application_settings[11]['slug']
        );
        $this->assertFalse((bool) $application_settings[11]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REGISTER_EXPORT_NAME,
            $application_settings[12]['slug']
        );
        $this->assertEquals('', $application_settings[12]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
            $application_settings[13]['slug']
        );
        $this->assertFalse((bool) $application_settings[13]['value']);
        $this->assertSame(
            ModuleType::INVOICES_ADVANCE_ENABLED,
            $application_settings[14]['slug']
        );
        $this->assertFalse((bool) $application_settings[14]['value']);
        $this->assertSame(
            ModuleType::GENERAL_MULTIPLE_USERS,
            $application_settings[15]['slug']
        );
        $this->assertSame('3', $application_settings[15]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertFalse((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_DISC_VOLUME,
            $application_settings[17]['slug']
        );
        $this->assertSame('3', $application_settings[17]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_FILE_SIZE,
            $application_settings[18]['slug']
        );
        $this->assertSame('10', $application_settings[18]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF,
            $application_settings[19]['slug']
        );
        $this->assertFalse((bool) $application_settings[19]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_MULTIPLE_PROJECTS,
            $application_settings[20]['slug']
        );
        $this->assertSame('3', $application_settings[20]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_USERS_IN_PROJECT,
            $application_settings[21]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[21]['value']);
    }

    protected function verifyCepClassicPackage($application_settings)
    {
        $this->checkSettingsCount($application_settings);

        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $application_settings[0]['slug']
        );
        $this->assertTrue((bool) $application_settings[0]['value']);

        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $application_settings[1]['slug']
        );
        $this->assertTrue((bool) $application_settings[1]['value']);

        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $application_settings[2]['slug']
        );
        $this->assertSame('app.projects-list', $application_settings[2]['value']);

        $this->assertSame(
            ModuleType::GENERAL_COMPANIES_VISIBLE,
            $application_settings[3]['slug']
        );
        $this->assertTrue((bool) $application_settings[3]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ACTIVE,
            $application_settings[4]['slug']
        );
        $this->assertFalse((bool) $application_settings[4]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            $application_settings[5]['slug']
        );
        $this->assertFalse((bool) $application_settings[5]['value']);

        $this->assertSame(
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            $application_settings[6]['slug']
        );
        $this->assertFalse((bool) $application_settings[6]['value']);

        $this->assertSame(
            ModuleType::INVOICES_PROFORMA_ENABLED,
            $application_settings[7]['slug']
        );
        $this->assertFalse((bool) $application_settings[7]['value']);

        $this->assertSame(
            ModuleType::INVOICES_UE_ENABLED,
            $application_settings[8]['slug']
        );
        $this->assertFalse((bool) $application_settings[8]['value']);

        $this->assertSame(
            ModuleType::INVOICES_FOOTER_ENABLED,
            $application_settings[9]['slug']
        );
        $this->assertFalse((bool) $application_settings[9]['value']);

        $this->assertSame(
            ModuleType::RECEIPTS_ACTIVE,
            $application_settings[10]['slug']
        );
        $this->assertFalse((bool) $application_settings[10]['value']);
        $this->assertSame(
            ModuleType::INVOICES_MARGIN_ENABLED,
            $application_settings[11]['slug']
        );
        $this->assertFalse((bool) $application_settings[11]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REGISTER_EXPORT_NAME,
            $application_settings[12]['slug']
        );
        $this->assertEquals('', $application_settings[12]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
            $application_settings[13]['slug']
        );
        $this->assertFalse((bool) $application_settings[13]['value']);
        $this->assertSame(
            ModuleType::INVOICES_ADVANCE_ENABLED,
            $application_settings[14]['slug']
        );
        $this->assertFalse((bool) $application_settings[14]['value']);
        $this->assertSame(
            ModuleType::GENERAL_MULTIPLE_USERS,
            $application_settings[15]['slug']
        );
        $this->assertSame('5', $application_settings[15]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertFalse((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_DISC_VOLUME,
            $application_settings[17]['slug']
        );
        $this->assertSame('30', $application_settings[17]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_FILE_SIZE,
            $application_settings[18]['slug']
        );
        $this->assertSame('300', $application_settings[18]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF,
            $application_settings[19]['slug']
        );
        $this->assertFalse((bool) $application_settings[19]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_MULTIPLE_PROJECTS,
            $application_settings[20]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[20]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_USERS_IN_PROJECT,
            $application_settings[21]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[21]['value']);
    }

    protected function verifyCepBusinessPackage($application_settings)
    {
        $this->checkSettingsCount($application_settings);

        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $application_settings[0]['slug']
        );
        $this->assertTrue((bool) $application_settings[0]['value']);

        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $application_settings[1]['slug']
        );
        $this->assertTrue((bool) $application_settings[1]['value']);

        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $application_settings[2]['slug']
        );
        $this->assertSame('app.projects-list', $application_settings[2]['value']);

        $this->assertSame(
            ModuleType::GENERAL_COMPANIES_VISIBLE,
            $application_settings[3]['slug']
        );
        $this->assertTrue((bool) $application_settings[3]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ACTIVE,
            $application_settings[4]['slug']
        );
        $this->assertFalse((bool) $application_settings[4]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            $application_settings[5]['slug']
        );
        $this->assertFalse((bool) $application_settings[5]['value']);

        $this->assertSame(
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            $application_settings[6]['slug']
        );
        $this->assertFalse((bool) $application_settings[6]['value']);

        $this->assertSame(
            ModuleType::INVOICES_PROFORMA_ENABLED,
            $application_settings[7]['slug']
        );
        $this->assertFalse((bool) $application_settings[7]['value']);

        $this->assertSame(
            ModuleType::INVOICES_UE_ENABLED,
            $application_settings[8]['slug']
        );
        $this->assertFalse((bool) $application_settings[8]['value']);

        $this->assertSame(
            ModuleType::INVOICES_FOOTER_ENABLED,
            $application_settings[9]['slug']
        );
        $this->assertFalse((bool) $application_settings[9]['value']);

        $this->assertSame(
            ModuleType::RECEIPTS_ACTIVE,
            $application_settings[10]['slug']
        );
        $this->assertFalse((bool) $application_settings[10]['value']);
        $this->assertSame(
            ModuleType::INVOICES_MARGIN_ENABLED,
            $application_settings[11]['slug']
        );
        $this->assertFalse((bool) $application_settings[11]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REGISTER_EXPORT_NAME,
            $application_settings[12]['slug']
        );
        $this->assertEquals('', $application_settings[12]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
            $application_settings[13]['slug']
        );
        $this->assertFalse((bool) $application_settings[13]['value']);
        $this->assertSame(
            ModuleType::INVOICES_ADVANCE_ENABLED,
            $application_settings[14]['slug']
        );
        $this->assertFalse((bool) $application_settings[14]['value']);
        $this->assertSame(
            ModuleType::GENERAL_MULTIPLE_USERS,
            $application_settings[15]['slug']
        );
        $this->assertSame('50', $application_settings[15]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertFalse((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_DISC_VOLUME,
            $application_settings[17]['slug']
        );
        $this->assertSame('30', $application_settings[17]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_FILE_SIZE,
            $application_settings[18]['slug']
        );
        $this->assertSame('300', $application_settings[18]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF,
            $application_settings[19]['slug']
        );
        $this->assertFalse((bool) $application_settings[19]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_MULTIPLE_PROJECTS,
            $application_settings[20]['slug']
        );
        $this->assertSame('2', $application_settings[20]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_USERS_IN_PROJECT,
            $application_settings[21]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[21]['value']);
    }

    protected function verifyCepEnterprisePackage($application_settings)
    {
        $this->checkSettingsCount($application_settings);

        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $application_settings[0]['slug']
        );
        $this->assertTrue((bool) $application_settings[0]['value']);

        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $application_settings[1]['slug']
        );
        $this->assertTrue((bool) $application_settings[1]['value']);

        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $application_settings[2]['slug']
        );
        $this->assertSame('app.projects-list', $application_settings[2]['value']);

        $this->assertSame(
            ModuleType::GENERAL_COMPANIES_VISIBLE,
            $application_settings[3]['slug']
        );
        $this->assertTrue((bool) $application_settings[3]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ACTIVE,
            $application_settings[4]['slug']
        );
        $this->assertFalse((bool) $application_settings[4]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            $application_settings[5]['slug']
        );
        $this->assertFalse((bool) $application_settings[5]['value']);

        $this->assertSame(
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            $application_settings[6]['slug']
        );
        $this->assertFalse((bool) $application_settings[6]['value']);

        $this->assertSame(
            ModuleType::INVOICES_PROFORMA_ENABLED,
            $application_settings[7]['slug']
        );
        $this->assertFalse((bool) $application_settings[7]['value']);

        $this->assertSame(
            ModuleType::INVOICES_UE_ENABLED,
            $application_settings[8]['slug']
        );
        $this->assertFalse((bool) $application_settings[8]['value']);

        $this->assertSame(
            ModuleType::INVOICES_FOOTER_ENABLED,
            $application_settings[9]['slug']
        );
        $this->assertFalse((bool) $application_settings[9]['value']);

        $this->assertSame(
            ModuleType::RECEIPTS_ACTIVE,
            $application_settings[10]['slug']
        );
        $this->assertFalse((bool) $application_settings[10]['value']);
        $this->assertSame(
            ModuleType::INVOICES_MARGIN_ENABLED,
            $application_settings[11]['slug']
        );
        $this->assertFalse((bool) $application_settings[11]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REGISTER_EXPORT_NAME,
            $application_settings[12]['slug']
        );
        $this->assertEquals('', $application_settings[12]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
            $application_settings[13]['slug']
        );
        $this->assertFalse((bool) $application_settings[13]['value']);
        $this->assertSame(
            ModuleType::INVOICES_ADVANCE_ENABLED,
            $application_settings[14]['slug']
        );
        $this->assertFalse((bool) $application_settings[14]['value']);
        $this->assertSame(
            ModuleType::GENERAL_MULTIPLE_USERS,
            $application_settings[15]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[15]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertFalse((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_DISC_VOLUME,
            $application_settings[17]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[17]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_FILE_SIZE,
            $application_settings[18]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[18]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF,
            $application_settings[19]['slug']
        );
        $this->assertTrue((bool) $application_settings[19]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_MULTIPLE_PROJECTS,
            $application_settings[20]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[20]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_USERS_IN_PROJECT,
            $application_settings[21]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[21]['value']);
    }

    protected function verifyIcontrolPackage($application_settings)
    {
        $this->checkSettingsCount($application_settings);

        $this->assertSame(
            ModuleType::PROJECTS_ACTIVE,
            $application_settings[0]['slug']
        );
        $this->assertFalse((bool) $application_settings[0]['value']);

        $this->assertSame(
            ModuleType::GENERAL_INVITE_ENABLED,
            $application_settings[1]['slug']
        );
        $this->assertFalse((bool) $application_settings[1]['value']);

        $this->assertSame(
            ModuleType::GENERAL_WELCOME_URL,
            $application_settings[2]['slug']
        );
        $this->assertSame('app.invoices-list', $application_settings[2]['value']);

        $this->assertSame(
            ModuleType::GENERAL_COMPANIES_VISIBLE,
            $application_settings[3]['slug']
        );
        $this->assertFalse((bool) $application_settings[3]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ACTIVE,
            $application_settings[4]['slug']
        );
        $this->assertTrue((bool) $application_settings[4]['value']);

        $this->assertSame(
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            $application_settings[5]['slug']
        );
        $this->assertFalse((bool) $application_settings[5]['value']);

        $this->assertSame(
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            $application_settings[6]['slug']
        );
        $this->assertFalse((bool) $application_settings[6]['value']);

        $this->assertSame(
            ModuleType::INVOICES_PROFORMA_ENABLED,
            $application_settings[7]['slug']
        );
        $this->assertFalse((bool) $application_settings[7]['value']);

        $this->assertSame(
            ModuleType::INVOICES_UE_ENABLED,
            $application_settings[8]['slug']
        );
        $this->assertFalse((bool) $application_settings[8]['value']);

        $this->assertSame(
            ModuleType::INVOICES_FOOTER_ENABLED,
            $application_settings[9]['slug']
        );
        $this->assertFalse((bool) $application_settings[9]['value']);

        $this->assertSame(
            ModuleType::RECEIPTS_ACTIVE,
            $application_settings[10]['slug']
        );
        $this->assertTrue((bool) $application_settings[10]['value']);
        $this->assertSame(
            ModuleType::INVOICES_MARGIN_ENABLED,
            $application_settings[11]['slug']
        );
        $this->assertFalse((bool) $application_settings[11]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REGISTER_EXPORT_NAME,
            $application_settings[12]['slug']
        );
        $this->assertEquals('', $application_settings[12]['value']);
        $this->assertSame(
            ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
            $application_settings[13]['slug']
        );
        $this->assertFalse((bool) $application_settings[13]['value']);
        $this->assertSame(
            ModuleType::INVOICES_ADVANCE_ENABLED,
            $application_settings[14]['slug']
        );
        $this->assertFalse((bool) $application_settings[14]['value']);
        $this->assertSame(
            ModuleType::GENERAL_MULTIPLE_USERS,
            $application_settings[15]['slug']
        );
        $this->assertSame(ModuleMod::UNLIMITED, $application_settings[15]['value']);
        $this->assertSame(
            ModuleType::INVOICES_JPK_EXPORT,
            $application_settings[16]['slug']
        );
        $this->assertFalse((bool) $application_settings[16]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_DISC_VOLUME,
            $application_settings[17]['slug']
        );
        $this->assertFalse((bool) $application_settings[17]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_FILE_SIZE,
            $application_settings[18]['slug']
        );
        $this->assertFalse((bool) $application_settings[18]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF,
            $application_settings[19]['slug']
        );
        $this->assertFalse((bool) $application_settings[19]['value']);
        $this->assertSame(
            ModuleType::PROJECTS_MULTIPLE_PROJECTS,
            $application_settings[20]['slug']
        );
        $this->assertFalse((bool) $application_settings[20]['value']);
    }

    /**
     * @param $application_settings
     */
    protected function checkSettingsCount($application_settings)
    {
        $this->assertSame(23, count($application_settings));
    }

    /**
     * @return array
     */
    protected function requestData($prefix_id = null)
    {
        return [
            'name' => '  New company name  ',
            'vatin' => '  PL123987123  ',
            'vat_payer' => true,
            'country_vatin_prefix_id' => $prefix_id,
            'email' => '  sample@example.com  ',
            'logotype' => '',
            'website' => 'www.bestcompany.pl',
            'phone' => '  New phone  ',
            'main_address_street' => '  New main_address_street  ',
            'main_address_number' => '  New main_address_number  ',
            'main_address_zip_code' => '  N m zip  ',
            'main_address_city' => '  New main_address_city  ',
            'main_address_country' => '  Polska  ',
            'contact_address_street' => '  New contact_address_street  ',
            'contact_address_number' => '  New contact_address_number  ',
            'contact_address_zip_code' => '  N c zip  ',
            'contact_address_city' => '  New contact_address_city  ',
            'contact_address_country' => '  Polska  ',
            'default_payment_term_days' => 90,
            'default_payment_method_id' => 17,
            'creator_id' => $this->user->id + 267,
            'editor_id' => $this->user->id + 297,
            'bank_accounts' => [
                [
                    'bank_name' => 'Initial bank_name',
                    'number' => 'Initial bank_account_number',
                    'default' => true,
                ],
                [
                    'bank_name' => 'no default bank_name',
                    'number' => 'no default bank_account_number',
                    'default' => false,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function initialData()
    {
        $initial_data = [
            'name' => 'Initial company name',
            'vatin' => 'xxx',
            'vat_payer' => null,
            'email' => 'initial@example.com',
            'phone' => 'Initial phone',

            'main_address_street' => 'Initial main_address_street',
            'main_address_number' => 'Initial main_address_number',
            'main_address_zip_code' => 'O m zip',
            'main_address_city' => 'Initial main_address_city',
            'main_address_country' => 'Initial main_address_country',

            'contact_address_street' => 'Initial contact_address_street',
            'contact_address_number' => 'Initial contact_address_number',
            'contact_address_zip_code' => 'O c zip',
            'contact_address_city' => 'Initial contact_address_city',
            'contact_address_country' => 'Initial contact_address_country',
            'default_payment_term_days' => 25,
        ];

        return $initial_data;
    }
}
