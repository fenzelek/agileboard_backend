<?php

namespace Tests\Feature\App\Modules\Contractor\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\ContractorAddress;
use App\Models\Db\Company;
use App\Models\Db\Contractor;
use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice;
use App\Models\Db\PaymentMethod;
use App\Models\Other\ModuleType;
use App\Models\Other\ContractorAddressType;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\BrowserKitTestCase;

class ContractorControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_success_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(Contractor::class)->create(['company_id' => $company->id]);

        $this->get('contractors?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'company_id',
                        'country_vatin_prefix_id',
                        'vatin',
                        'email',
                        'phone',
                        'bank_name',
                        'bank_account_number',
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
                        'payments_all',
                        'payments_paid',
                        'payments_paid_late',
                        'payments_not_paid',
                        'is_used',
                        'creator_id',
                        'editor_id',
                        'remover_id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'vatin_prefix',
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'links',
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function index_success_simply()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $contractors = [
            factory(Contractor::class)->create([
                'company_id' => $company->id,
                'country_vatin_prefix_id' => 1,
            ]),
            factory(Contractor::class)->create([
                'company_id' => $company->id,
                'country_vatin_prefix_id' => 1,
            ]),
            factory(Contractor::class)->create(['company_id' => $otherCompany->id]),
            factory(Contractor::class)->create(
                ['company_id' => $company->id, 'deleted_at' => Carbon::now()]
            ),
        ];

        $this->get('contractors?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseContractors = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($responseContractors));

        for ($i = 0; $i < 2; ++$i) {
            foreach ($contractors[$i]->getAttributes() as $key => $value) {
                $this->assertEquals($value, $responseContractors[$i][$key]);
            }

            $this->assertSame(0, $responseContractors[$i]['payments_all']);
            $this->assertSame(0, $responseContractors[$i]['payments_paid']);
            $this->assertSame(0, $responseContractors[$i]['payments_paid_late']);
            $this->assertSame(0, $responseContractors[$i]['payments_not_paid']);
            $this->assertSame(0, $responseContractors[$i]['is_used']);
            $this->assertSame(
                'Afganistan',
                $responseContractors[$i]['vatin_prefix']['data']['name']
            );
        }
    }

    /** @test */
    public function index_success_simply_with_addresses()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            true
        );
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $contractors = [
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $otherCompany->id]),
            factory(Contractor::class)->create(
                ['company_id' => $company->id, 'deleted_at' => Carbon::now()]
            ),
        ];
        foreach ($contractors as $contractor) {
            factory(ContractorAddress::class, 2)->create([
                'contractor_id' => $contractor->id,
                'name' => 'Nazwa',
                'type' => 'Typical',
                'street' => 'Street',
                'number' => '123',
                'zip_code' => '61-000',
                'city' => 'Poznan',
                'country' => 'Polska',
                'default' => true,
            ]);
        }

        $this->get('contractors?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseContractors = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($responseContractors));

        for ($i = 0; $i < 2; ++$i) {
            foreach ($contractors[$i]->getAttributes() as $key => $value) {
                $this->assertEquals($value, $responseContractors[$i][$key]);
            }

            $this->assertSame(0, $responseContractors[$i]['payments_all']);
            $this->assertSame(0, $responseContractors[$i]['payments_paid']);
            $this->assertSame(0, $responseContractors[$i]['payments_paid_late']);
            $this->assertSame(0, $responseContractors[$i]['payments_not_paid']);

            // Check address
            $this->assertCount(2, $responseContractors[$i]['addresses']['data']);
            $address = $responseContractors[$i]['addresses']['data'][0];
            $this->assertEquals($responseContractors[$i]['id'], $address['contractor_id']);
            $this->assertEquals('Nazwa', $address['name']);
            $this->assertEquals('Typical', $address['type']);
            $this->assertEquals('Street', $address['street']);
            $this->assertEquals('123', $address['number']);
            $this->assertEquals('61-000', $address['zip_code']);
            $this->assertEquals('Poznan', $address['city']);
            $this->assertEquals('Polska', $address['country']);
            $this->assertTrue((bool) $address['default']);
        }
    }

    /** @test */
    public function index_success_simply_with_addresses_when_setting_is_turn_off()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            false
        );
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $contractors = [
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $otherCompany->id]),
            factory(Contractor::class)->create(
                ['company_id' => $company->id, 'deleted_at' => Carbon::now()]
            ),
        ];
        foreach ($contractors as $contractor) {
            factory(ContractorAddress::class, 2)->create([
                'contractor_id' => $contractor->id,
                'name' => 'Nazwa',
                'type' => 'Typical',
                'street' => 'Street',
                'number' => '123',
                'zip_code' => '61-000',
                'city' => 'Poznan',
                'country' => 'Polska',
            ]);
        }

        $this->get('contractors?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseContractors = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($responseContractors));

        for ($i = 0; $i < 2; ++$i) {
            foreach ($contractors[$i]->getAttributes() as $key => $value) {
                $this->assertEquals($value, $responseContractors[$i][$key]);
            }

            $this->assertSame(0, $responseContractors[$i]['payments_all']);
            $this->assertSame(0, $responseContractors[$i]['payments_paid']);
            $this->assertSame(0, $responseContractors[$i]['payments_paid_late']);
            $this->assertSame(0, $responseContractors[$i]['payments_not_paid']);

            // Check address
            $this->assertArrayNotHasKey('addresses', $responseContractors[$i]);
        }
    }

    /** @test */
    public function index_success_with_stats()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $contractors = [
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $otherCompany->id]),
            factory(Contractor::class)->create(
                ['company_id' => $company->id, 'deleted_at' => Carbon::now()]
            ),
        ];

        $invoices = [
            factory(Invoice::class)->make([
                'price_gross' => 51321,
                'issue_date' => '2017-01-01',
                'payment_term_days' => 4,
                'paid_at' => '2017-01-03 08:21:23', // paid at given period
            ]),
            factory(Invoice::class)->make([
                'price_gross' => 21722,
                'issue_date' => '2017-01-01',
                'payment_term_days' => 4,
                'paid_at' => null,
            ]),
            factory(Invoice::class)->make([
                'price_gross' => 2354,
                'issue_date' => '2017-01-01',
                'payment_term_days' => 4,
                'paid_at' => '2017-01-25 08:08:15', // overdue payment
            ]),
            factory(Invoice::class)->make([
                'price_gross' => 7123,
                'issue_date' => '2017-01-01',
                'payment_term_days' => 4,
                'paid_at' => '2017-01-06 00:00:00', // overdue payment
            ]),
            factory(Invoice::class)->make([
                'price_gross' => 19812,
                'issue_date' => '2017-01-01',
                'payment_term_days' => 4,
                'paid_at' => '2017-01-05 08:22:15', // paid at given period
            ]),
            factory(Invoice::class)->make([
                'price_gross' => 170923,
                'issue_date' => '2017-01-01',
                'payment_term_days' => 4,
                'paid_at' => null,
            ]),
        ];

        // all invoices = 51321 + 21722 + 2354 + 7123 + 19812 + 170923 = 273255 = 2732.55 zl
        // paid invoices = 51321 + 2354 + 7123 + 19812 = 80610 = 806.10 zł
        // paid too late = 2354 + 7123 = 9477 = 94.77 zł
        // not paid = 21722 + 170923 = 192645 = 1926.45 zł

        $contractors[1]->invoices()->saveMany($invoices);

        $this->get('contractors?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseContractors = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($responseContractors));

        for ($i = 0; $i < 2; ++$i) {
            foreach ($contractors[$i]->getAttributes() as $key => $value) {
                $this->assertEquals($value, $responseContractors[$i][$key]);
            }

            // for 2nc contractor we have some values
            if ($i == 1) {
                $this->assertEqualsWithDelta(2732.55, $responseContractors[$i]['payments_all'], 0.00001);
                $this->assertEqualsWithDelta(806.10, $responseContractors[$i]['payments_paid'], 0.00001);
                $this->assertEqualsWithDelta(
                    94.77,
                    $responseContractors[$i]['payments_paid_late'],
                    0.00001
                );
                $this->assertEqualsWithDelta(
                    1926.45,
                    $responseContractors[$i]['payments_not_paid'],
                    0.00001
                );
            } else {
                $this->assertSame(0, $responseContractors[$i]['payments_all']);
                $this->assertSame(0, $responseContractors[$i]['payments_paid']);
                $this->assertSame(0, $responseContractors[$i]['payments_paid_late']);
                $this->assertSame(0, $responseContractors[$i]['payments_not_paid']);
            }
        }
    }

    /** @test */
    public function index_success_search()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractors = [
            factory(Contractor::class)->create([
                'company_id' => $company->id,
                'name' => 'test',
                'vatin' => '0123',
            ]),
            factory(Contractor::class)->create([
                'company_id' => $company->id,
                'name' => 'tst',
                'vatin' => '4567',
            ]),
            factory(Contractor::class)->create([
                'company_id' => $company->id,
                'name' => 'other',
                'vatin' => '123es456',
            ]),
        ];

        $this->get('contractors?search=es&selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseContractors = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($responseContractors));

        foreach ($contractors[0]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseContractors[0][$key]);
        }

        foreach ($contractors[2]->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseContractors[1][$key]);
        }

        $this->assertSame(0, $responseContractors[0]['payments_all']);
        $this->assertSame(0, $responseContractors[0]['payments_paid']);
        $this->assertSame(0, $responseContractors[0]['payments_paid_late']);
        $this->assertSame(0, $responseContractors[0]['payments_not_paid']);
    }

    /** @test */
    public function index_success_pagination()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractors = [
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $company->id]),
            factory(Contractor::class)->create(['company_id' => $company->id]),
        ];

        $this->get('contractors?limit=2&selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseContractors = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($responseContractors));
    }

    /** @test */
    public function show_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);
        factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);

        $this->get('contractors/' . $contractor->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'company_id',
                    'country_vatin_prefix_id',
                    'vatin',
                    'email',
                    'phone',
                    'bank_name',
                    'bank_account_number',
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
                    'payments_all',
                    'payments_paid',
                    'payments_paid_late',
                    'payments_not_paid',
                    'is_used',
                    'creator_id',
                    'editor_id',
                    'remover_id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'addresses' => [
                        'data' => [
                            [
                                'name',
                                'type',
                                'street',
                                'number',
                                'zip_code',
                                'city',
                                'country',
                                'default',
                            ],
                        ],
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function show_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $pl_vatin_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'country_vatin_prefix_id' => $pl_vatin_prefix_id,
        ]);

        $contractor_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
            'default' => true,
        ]);

        $this->get('contractors/' . $contractor->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $responseContractor = $this->decodeResponseJson()['data'];

        foreach ($contractor->getAttributes() as $key => $value) {
            $this->assertEquals($value, $responseContractor[$key]);
        }

        $this->assertSame(0, $responseContractor['payments_all']);
        $this->assertSame(0, $responseContractor['payments_paid']);
        $this->assertSame(0, $responseContractor['payments_paid_late']);
        $this->assertSame(0, $responseContractor['payments_not_paid']);

        $contractor_delivery_address = $responseContractor['addresses']['data'][0];
        $this->assertSame($contractor_address->name, $contractor_delivery_address['name']);
        $this->assertSame(ContractorAddressType::DELIVERY, $contractor_delivery_address['type']);
        $this->assertSame($contractor_address->street, $contractor_delivery_address['street']);
        $this->assertEquals($contractor_address->number, $contractor_delivery_address['number']);
        $this->assertEquals($contractor_address->zip_code, $contractor_delivery_address['zip_code']);
        $this->assertSame($contractor_address->city, $contractor_delivery_address['city']);
        $this->assertSame($contractor_address->country, $contractor_delivery_address['country']);
        $this->assertEquals($contractor_address->default, $contractor_delivery_address['default']);

        $this->assertEquals($responseContractor['vatin_prefix']['data']['id'], $pl_vatin_prefix_id);
    }

    /** @test */
    public function show_not_my_contractor()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $contractor = factory(Contractor::class)->create(['company_id' => $otherCompany->id]);

        $this->get('contractors/' . $contractor->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(404);
    }

    /** @test */
    public function show_deleted()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'remover_id' => $this->user->id,
            'deleted_at' => Carbon::now(),
        ]);

        $this->get('contractors/' . $contractor->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(404);
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('/contractors?selected_company_id=' . $company->id, []);

        $this->verifyValidationResponse(
            [
                'name',
                'email',
                'phone',
                'bank_name',
                'bank_account_number',

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
                'addresses',
            ]
        );
    }

    /** @test */
    public function store_it_returns_validation_error_without_delivery_addresses_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $contractor = [
            'addresses' => [[]],
        ];
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor);
        $this->verifyValidationResponse(
            [
                'addresses.0.type',
                'addresses.0.street',
                'addresses.0.number',
                'addresses.0.zip_code',
                'addresses.0.city',
                'addresses.0.country',
            ]
        );
    }

    /** @test */
    public function store_it_passing_validation_without_addresses_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $contractor = $this->incomingData();
        unset($contractor['addresses']);
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            false
        );

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);
    }

    /** @test */
    public function store_it_checks_zip_code_validation_for_different_countries()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $contractor = $this->incomingData();
        $contractor['main_address_zip_code'] = 123456789;
        $contractor['contact_address_zip_code'] = 123456789;

        $contractor['addresses'][0]['country'] = 'Polska';
        $contractor['addresses'][0]['zip_code'] = 123456789;

        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            true
        );

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(422);

        $this->verifyValidationResponse(
            ['main_address_zip_code', 'addresses'],
            ['contact_address_zip_code']
        );
    }

    /** @test */
    public function store_it_pass_zip_code_validation_for_delivery_addresses_for_poland()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $contractor = $this->incomingData();

        $contractor['addresses'][0]['country'] = 'Polska';
        $contractor['addresses'][0]['zip_code'] = 1234567;

        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            true
        );

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);
    }

    /** @test */
    public function store_it_pass_zip_code_validation_for_delivery_addresses_for_germany()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $contractor = $this->incomingData();

        $contractor['addresses'][0]['country'] = 'Niemcy';
        $contractor['addresses'][0]['zip_code'] = 1234567890123456789;

        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED,
            true
        );

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);
    }

    /** @test */
    public function store_it_returns_validation_error_address_type_not_valid()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $contractor = [
            'addresses' => [[
                'type' => 'not_valid_address_type',
            ]],
        ];
        $this->post('/contractors?selected_company_id=' . $company->id, $contractor);
        $this->verifyValidationResponse(
            [
                'addresses.0.type',
            ]
        );
    }

    /** @test */
    public function store_it_returns_validation_error_delivery_address_not_boolean()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);

        $contractor = $this->incomingData();
        Arr::set($contractor, 'addresses.0.default', -1);

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor);
        $this->verifyValidationResponse(
            [
                'addresses.0.default',
            ]
        );
    }

    /** @test */
    public function store_it_returns_validation_error_delivery_address_not_default_indicated()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);

        $contractor = $this->incomingData();
        Arr::set($contractor, 'addresses.0.default', 0);

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor);
        $this->verifyValidationResponse(
            [
                'one_default_delivery_address',
            ]
        );
    }

    /** @test */
    public function store_it_returns_validation_error_delivery_address_too_many_default_indicated()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);

        $contractor = $this->incomingData();
        $second_default_address = [
            'type' => ContractorAddressType::DELIVERY,
            'street' => 'Delivery address_street2',
            'number' => '999',
            'zip_code' => '89-999',
            'city' => 'Delivery 2 address_city',
            'country' => 'Delivery 2 address_country',
            'default' => 1,
            ];
        Arr::set($contractor, 'addresses.1', $second_default_address);

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor);
        $this->verifyValidationResponse(
            [
                'one_default_delivery_address',
            ]
        );
    }

    /** @test */
    public function store_success_response_without_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();

        $beforeContractors = Contractor::all();

        $contractor = $this->incomingData();

        $contractor['default_payment_term_days'] = '7';
        $contractor['default_payment_method_id'] = $paymentMethod->id;

        //response
        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);

        $responseContractor = $this->decodeResponseJson()['data'];

        foreach ($contractor as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals(trim($value), $responseContractor[$key]);
            }
        }

        $this->assertEquals($company->id, $responseContractor['company_id']);
        $this->assertEquals($this->user->id, $responseContractor['creator_id']);
        $this->assertEquals(0, $responseContractor['editor_id']);
        $this->assertEquals(0, $responseContractor['remover_id']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['created_at']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['updated_at']);
        $this->assertSame(null, $responseContractor['deleted_at']);

        //db
        $this->assertEquals(count($beforeContractors) + 1, count(Contractor::all()));

        $dbContractor = Contractor::find($responseContractor['id']);

        foreach ($contractor as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals($dbContractor->$key, trim($value));
            }
        }
    }

    /** @test */
    public function store_success_db_without_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();

        $beforeContractors = Contractor::all();

        $contractor = $this->incomingData();
        $contractor['default_payment_term_days'] = '7';
        $contractor['default_payment_method_id'] = $paymentMethod->id;

        //response
        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);

        $responseContractor = $this->decodeResponseJson()['data'];

        $this->assertEquals(count($beforeContractors) + 1, count(Contractor::all()));
        $dbContractor = Contractor::find($responseContractor['id']);

        foreach ($contractor as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals(trim($value), $dbContractor->$key);
            }
        }

        $this->assertEquals($company->id, $dbContractor->company_id);
        $this->assertEquals($this->user->id, $dbContractor->creator_id);
        $this->assertEquals(0, $dbContractor->editor_id);
        $this->assertEquals(0, $dbContractor->remover_id);
        $this->assertSame($now->toDateTimeString(), $dbContractor->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $dbContractor->updated_at->toDateTimeString());
        $this->assertSame(null, $dbContractor->deleted_at);
    }

    /** @test */
    public function store_success_response_with_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();

        $contractor = $this->incomingData();

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);

        $responseContractor = $this->decodeResponseJson()['data'];

        foreach ($contractor as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals(trim($value), $responseContractor[$key]);
            }
        }

        $this->assertSame(null, $responseContractor['default_payment_term_days']);
        $this->assertSame(null, $responseContractor['default_payment_method_id']);
        $this->assertEquals($company->id, $responseContractor['company_id']);
        $this->assertEquals($this->user->id, $responseContractor['creator_id']);
        $this->assertEquals(0, $responseContractor['editor_id']);
        $this->assertEquals(0, $responseContractor['remover_id']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['created_at']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['updated_at']);
        $this->assertSame(null, $responseContractor['deleted_at']);
    }

    /** @test */
    public function store_success_response_with_default_company_settings_without_vatin()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();

        $contractor = $this->incomingData();

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)->seeStatusCode(201);
        $responseContractor = $this->decodeResponseJson()['data'];

        foreach ($contractor as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals(trim($value), $responseContractor[$key]);
            }
        }
        $this->assertSame(null, $responseContractor['default_payment_term_days']);
        $this->assertSame(null, $responseContractor['default_payment_method_id']);
        $this->assertEquals($company->id, $responseContractor['company_id']);
        $this->assertEquals($this->user->id, $responseContractor['creator_id']);
        $this->assertEquals(0, $responseContractor['editor_id']);
        $this->assertEquals(0, $responseContractor['remover_id']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['created_at']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['updated_at']);
        $this->assertSame(null, $responseContractor['deleted_at']);
    }

    /** @test */
    public function store_success_response_with_vatin()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = $this->incomingData();
        $pl_vatin_prefix = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $contractor['country_vatin_prefix_id'] = $pl_vatin_prefix;
        $contractor['vatin'] = 1231234545;

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);
        $responseContractor = $this->decodeResponseJson()['data'];
        $this->assertEquals(1231234545, $responseContractor['vatin']);
        $this->assertEquals($pl_vatin_prefix, $responseContractor['country_vatin_prefix_id']);
    }

    /** @test */
    public function store_success_response_with_too_long_vatin_with_polish_prefix()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = $this->incomingData();
        $pl_vatin_prefix = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $contractor['country_vatin_prefix_id'] = $pl_vatin_prefix;
        $contractor['vatin'] = 1234567890123456;

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(422);
        $this->verifyValidationResponse(['vatin']);
    }

    /** @test */
    public function store_success_response_with_long_vatin_with_not_polish_prefix()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = $this->incomingData();
        $not_pl_vatin_prefix = CountryVatinPrefix::first()->id;
        $contractor['country_vatin_prefix_id'] = $not_pl_vatin_prefix;
        $contractor['vatin'] = 1234567890123456;

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);
    }

    /** @test */
    public function store_success_response_with_wrong_country_vatin_prefix_id()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = $this->incomingData();
        $pl_vatin_prefix = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $contractor['country_vatin_prefix_id'] = $pl_vatin_prefix + 9999;
        $contractor['vatin'] = 1231234545;

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(422);
        $this->verifyValidationResponse(['country_vatin_prefix_id']);
    }

    /** @test */
    public function store_validation_pass_when_vatin_prefix_is_null()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = $this->incomingData();
        $pl_vatin_prefix = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $contractor['country_vatin_prefix_id'] = null;
        $contractor['vatin'] = 1231234545;

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);

        $responseContractor = $this->decodeResponseJson()['data'];
        $this->assertEmpty($responseContractor['country_vatin_prefix_id']);
    }

    /** @test */
    public function store_blocked_adding_delivery_addresses()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        ContractorAddress::whereRaw('1=1')->delete();
        $this->assertSame(0, ContractorAddress::count());

        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, false);

        $contractor = $this->incomingData();

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)->seeStatusCode(201);
        $this->assertSame(1, ContractorAddress::count());
        $contractor_address = ContractorAddress::latest()->first();

        $this->assertSame(ContractorAddressType::DELIVERY, $contractor_address['type']);

        $this->assertSame('Set address_street', $contractor_address->street);
        $this->assertSame('00', $contractor_address->number);
        $this->assertSame('00-000', $contractor_address->zip_code);
        $this->assertSame('Set address_city', $contractor_address->city);
        $this->assertSame('Polska', $contractor_address->country);
        $this->assertTrue((bool) $contractor_address->default);
    }

    /** @test */
    public function store_success_db_with_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);

        $beforeContractors = Contractor::all();
        $contractor = $this->incomingData();

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)
            ->seeStatusCode(201);

        $responseContractor = $this->decodeResponseJson()['data'];

        $this->assertEquals(count($beforeContractors) + 1, count(Contractor::all()));
        $dbContractor = Contractor::find($responseContractor['id']);

        foreach ($contractor as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals(trim($value), $dbContractor->$key);
            }
        }

        $dbContractorAddress = ContractorAddress::where('contractor_id', $responseContractor['id'])->first();

        $this->assertSame('Delivery address_street 99, Delivery address_city', $dbContractorAddress->name);
        $this->assertSame($contractor['addresses'][0]['type'], $dbContractorAddress->type);
        $this->assertSame($contractor['addresses'][0]['street'], $dbContractorAddress->street);
        $this->assertSame($contractor['addresses'][0]['number'], $dbContractorAddress->number);
        $this->assertSame($contractor['addresses'][0]['zip_code'], $dbContractorAddress->zip_code);
        $this->assertSame($contractor['addresses'][0]['city'], $dbContractorAddress->city);
        $this->assertSame($contractor['addresses'][0]['country'], $dbContractorAddress->country);
        $this->assertTrue((bool) $dbContractorAddress->default);

        $this->assertSame(null, $dbContractor->default_payment_term_days);
        $this->assertSame(null, $dbContractor->default_payment_method_id);
        $this->assertEquals($company->id, $dbContractor->company_id);
        $this->assertEquals($this->user->id, $dbContractor->creator_id);
        $this->assertEquals(0, $dbContractor->editor_id);
        $this->assertEquals(0, $dbContractor->remover_id);
        $this->assertSame($now->toDateTimeString(), $dbContractor->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $dbContractor->updated_at->toDateTimeString());
        $this->assertSame(null, $dbContractor->deleted_at);
    }

    /** @test */
    public function store_success_db_triming_extra_address_fields()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $beforeContractorAddress = ContractorAddress::all();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);

        $contractor = $this->incomingData();
        $contractor['addresses'] = [
            [
                'type' => ContractorAddressType::DELIVERY,
                'street' => ' Set address_street ',
                'number' => ' 99 ',
                'zip_code' => ' 99-000',
                'city' => ' Set address_city ',
                'country' => ' Polska ',
                'default' => true,
            ],
        ];
        //response
        $this->post('/contractors?selected_company_id=' . $company->id, $contractor)->seeStatusCode(201);

        $this->assertEquals(count($beforeContractorAddress) + 1, count(ContractorAddress::all()));

        $dbContractorAddress = ContractorAddress::where('contractor_id', Contractor::latest()->first()->id)->first();

        $this->assertSame($contractor['addresses'][0]['type'], $dbContractorAddress->type);
        $this->assertSame('Set address_street', $dbContractorAddress->street);
        $this->assertSame('99', $dbContractorAddress->number);
        $this->assertSame('99-000', $dbContractorAddress->zip_code);
        $this->assertSame('Set address_city', $dbContractorAddress->city);
        $this->assertSame('Polska', $dbContractorAddress->country);
        $this->assertTrue((bool) $dbContractorAddress->default);
    }

    /** @test */
    public function store_many_delivery_addresses_but_default_one()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);

        $contractor = $this->incomingData();
        $second_default_address = [
            'type' => ContractorAddressType::DELIVERY,
            'street' => 'Delivery address_street2',
            'number' => '999',
            'zip_code' => '89-999',
            'city' => 'Delivery 2 address_city',
            'country' => 'Polska',
            'default' => 0,
        ];
        Arr::set($contractor, 'addresses.1', $second_default_address);

        $this->post('/contractors?selected_company_id=' . $company->id, $contractor);

        $this->assertEquals(2, count(ContractorAddress::all()));

        $dbContractorAddress = ContractorAddress::where('contractor_id', Contractor::latest()->first()->id)->first();
        $dbContractorAddressSecond = ContractorAddress::where('contractor_id', Contractor::latest()->first()->id)->skip(1)->first();
        $this->assertSame('Delivery address_street 99, Delivery address_city', $dbContractorAddress->name);
        $this->assertSame($contractor['addresses'][0]['type'], $dbContractorAddress->type);
        $this->assertSame('Delivery address_street', $dbContractorAddress->street);
        $this->assertSame('99', $dbContractorAddress->number);
        $this->assertSame('99-999', $dbContractorAddress->zip_code);
        $this->assertSame('Delivery address_city', $dbContractorAddress->city);
        $this->assertSame('Albania', $dbContractorAddress->country);
        $this->assertTrue((bool) $dbContractorAddress->default);

        $this->assertSame('Delivery address_street2 999, Delivery 2 address_city', $dbContractorAddressSecond->name);
        $this->assertSame($contractor['addresses'][1]['type'], $dbContractorAddressSecond->type);
        $this->assertSame('Delivery address_street2', $dbContractorAddressSecond->street);
        $this->assertSame('999', $dbContractorAddressSecond->number);
        $this->assertSame('89-999', $dbContractorAddressSecond->zip_code);
        $this->assertSame('Delivery 2 address_city', $dbContractorAddressSecond->city);
        $this->assertSame('Polska', $dbContractorAddressSecond->country);
        $this->assertFalse((bool) $dbContractorAddressSecond->default);
    }

    /** @test */
    public function update_it_returns_validation_error_without_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put('/contractors/100?selected_company_id=' . $company->id, []);

        $this->verifyValidationResponse(
            [
                'name',
                'email',
                'phone',
                'bank_name',
                'bank_account_number',

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
    public function update_success_response_without_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);

        $fields = $this->incomingData();
        $fields['default_payment_term_days'] = '7';
        $fields['default_payment_method_id'] = $paymentMethod->id;
        $fields['country_vatin_prefix_id'] = null;

        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        )->seeStatusCode(200);

        $responseContractor = $this->decodeResponseJson()['data'];
        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals($responseContractor[$key], trim($value));
            }
        }

        $this->assertEquals($company->id, $responseContractor['company_id']);
        $this->assertEquals($this->user->id, $responseContractor['editor_id']);
        $this->assertEquals(0, $responseContractor['remover_id']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['created_at']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['updated_at']);
        $this->assertSame(null, $responseContractor['deleted_at']);
    }

    /** @test */
    public function update_it_returns_validation_error_without_delivery_addresses_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $incoming_data = [
            'addresses' => [[]],
        ];

        $this->put('/contractors/' . $contractor->id . '?selected_company_id=' . $company->id, $incoming_data)->seeStatusCode(422);
        $this->verifyValidationResponse(
            [
                'addresses.0.type',
                'addresses.0.default',
                'addresses.0.street',
                'addresses.0.number',
                'addresses.0.zip_code',
                'addresses.0.city',
                'addresses.0.country',
                'addresses.0.default',
            ]
        );
    }

    /** @test */
    public function update_it_returns_validation_error_delivery_address_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $delivery_address = factory(ContractorAddress::class)->create();
        $delivery_address_id = $delivery_address->id;
        $delivery_address->delete();
        $incoming_data = [
            'addresses' => [[
                'id' => $delivery_address_id,
            ]],
        ];

        $this->put('/contractors/' . $contractor->id . '?selected_company_id=' . $company->id, $incoming_data)->seeStatusCode(422);
        $this->verifyValidationResponse(
            [
                'addresses.0.id',
            ]
        );
    }

    /** @test */
    public function update_it_returns_validation_error_address_type_not_valid()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $incoming_data = [
            'addresses' => [[
                'type' => 'not_valid_address_type',
            ]],
        ];
        $this->put('/contractors/' . $contractor->id . '?selected_company_id=' . $company->id, $incoming_data)->seeStatusCode(422);
        $this->verifyValidationResponse(
            [
                'addresses.0.type',
            ]
        );
    }

    /** @test */
    public function update_success_db_without_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $beforeContractors = Contractor::all();

        $fields = $this->incomingData();
        $fields['default_payment_term_days'] = '7';
        $fields['default_payment_method_id'] = $paymentMethod->id;

        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        )->seeStatusCode(200);

        $this->assertEquals(count($beforeContractors), count(Contractor::all()));
        $dbContractor = $contractor->fresh();

        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals($dbContractor->$key, trim($value));
            }
        }
        $this->assertEquals($company->id, $dbContractor->company_id);
        $this->assertEquals($this->user->id, $dbContractor->editor_id);
        $this->assertEquals(0, $dbContractor->remover_id);
        $this->assertSame($now->toDateTimeString(), $dbContractor->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $dbContractor->updated_at->toDateTimeString());
        $this->assertSame(null, $dbContractor->deleted_at);
        $this->assertSame(null, $dbContractor->deleted_at);

        $dbContractorAddress = ContractorAddress::where('contractor_id', Contractor::latest()->first()->id)->first();

        $this->assertSame('Delivery address_street 99, Delivery address_city', $dbContractorAddress->name);
        $this->assertSame($contractor['addresses'][0]['type'], $dbContractorAddress->type);
        $this->assertSame('Delivery address_street', $dbContractorAddress->street);
        $this->assertSame('99', $dbContractorAddress->number);
        $this->assertSame('99-999', $dbContractorAddress->zip_code);
        $this->assertSame('Delivery address_city', $dbContractorAddress->city);
        $this->assertSame('Albania', $dbContractorAddress->country);
        $this->assertTrue((bool) $dbContractorAddress->default);
    }

    /** @test */
    public function update_delivery_address()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $beforeContractors = Contractor::all();

        $contractor_address = $contractor->addresses()->create([
            'name' => 'old_name',
            'type' => ContractorAddressType::DELIVERY,
            'street' => 'street old',
            'number' => 00,
            'zip_code' => '00-000',
            'city' => 'old_city',
            'country' => 'old_country',
        ]);

        $init_contractor_id = $contractor_address->id;
        $fields = $this->incomingData();
        Arr::set($fields, 'addresses.0.id', $init_contractor_id);
        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        )->seeStatusCode(200);
        $dbContractorAddress = $contractor_address->fresh();
        $this->assertSame(1, ContractorAddress::count());
        $this->assertSame($init_contractor_id, $dbContractorAddress->id);
        $this->assertSame('Delivery address_street 99, Delivery address_city', $dbContractorAddress->name);
        $this->assertSame($contractor['addresses'][0]['type'], $dbContractorAddress->type);
        $this->assertSame('Delivery address_street', $dbContractorAddress->street);
        $this->assertSame('99', $dbContractorAddress->number);
        $this->assertSame('99-999', $dbContractorAddress->zip_code);
        $this->assertSame('Delivery address_city', $dbContractorAddress->city);
        $this->assertSame('Albania', $dbContractorAddress->country);
        $this->assertTrue((bool) $dbContractorAddress->default);
    }

    /** @test */
    public function update_remove_not_used_delivery_addresses()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);

        $addresses = factory(ContractorAddress::class, 2)->create([
            'contractor_id' => $contractor->id,
        ]);

        $this->assertEquals(2, count(ContractorAddress::all()));

        $fields = $this->incomingData();
        Arr::set($fields, 'addresses.0.id', $addresses[0]->id);

        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        )->seeStatusCode(200);

        $this->assertEquals(1, count(ContractorAddress::all()));
        $dbContractor = $contractor->fresh();

        $dbContractorAddress = ContractorAddress::where('contractor_id', Contractor::latest()->first()->id)->first();
        $this->assertSame($addresses[0]->id, $dbContractorAddress->id);
        $this->assertSame('Delivery address_street 99, Delivery address_city', $dbContractorAddress->name);
        $this->assertSame($contractor['addresses'][0]['type'], $dbContractorAddress->type);
        $this->assertSame('Delivery address_street', $dbContractorAddress->street);
        $this->assertSame('99', $dbContractorAddress->number);
        $this->assertSame('99-999', $dbContractorAddress->zip_code);
        $this->assertSame('Delivery address_city', $dbContractorAddress->city);
        $this->assertSame('Albania', $dbContractorAddress->country);
        $this->assertTrue((bool) $dbContractorAddress->default);
    }

    /** @test */
    public function update_existing_delivery_address_and_add_new_one()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);

        $addresses = factory(ContractorAddress::class, 2)->create([
            'contractor_id' => $contractor->id,
        ]);

        $this->assertEquals(2, count(ContractorAddress::all()));

        $fields = $this->incomingData();
        Arr::set($fields, 'addresses.0.id', $addresses[0]->id);
        Arr::set($fields, 'addresses.1', [
            'type' => ContractorAddressType::DELIVERY,
            'street' => 'street new',
            'number' => 11,
            'zip_code' => '11-111',
            'city' => 'new_city',
            'country' => 'Niemcy',
            'default' => false,
         ]);

        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        )->seeStatusCode(200);

        $this->assertEquals(2, count(ContractorAddress::all()));
        $dbContractor = $contractor->fresh();

        $dbContractorAddress = ContractorAddress::where('contractor_id', $dbContractor->id)->first();
        $this->assertSame($addresses[0]->id, $dbContractorAddress->id);
        $this->assertSame('Delivery address_street 99, Delivery address_city', $dbContractorAddress->name);
        $this->assertSame($contractor['addresses'][0]['type'], $dbContractorAddress->type);
        $this->assertSame('Delivery address_street', $dbContractorAddress->street);
        $this->assertSame('99', $dbContractorAddress->number);
        $this->assertSame('99-999', $dbContractorAddress->zip_code);
        $this->assertSame('Delivery address_city', $dbContractorAddress->city);
        $this->assertSame('Albania', $dbContractorAddress->country);
        $this->assertTrue((bool) $dbContractorAddress->default);
        $dbContractorAddress = ContractorAddress::where('contractor_id', $dbContractor->id)->skip(1)->first();

        $this->assertSame($contractor['addresses'][1]['type'], $dbContractorAddress->type);
        $this->assertSame('street new', $dbContractorAddress->street);
        $this->assertSame('11', $dbContractorAddress->number);
        $this->assertSame('11-111', $dbContractorAddress->zip_code);
        $this->assertSame('new_city', $dbContractorAddress->city);
        $this->assertSame('Niemcy', $dbContractorAddress->country);
        $this->assertFalse((bool) $dbContractorAddress->default);
    }

    /** @test */
    public function update_success_response_with_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);

        $fields = $this->incomingData();

        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        )->seeStatusCode(200);

        $responseContractor = $this->decodeResponseJson()['data'];

        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals($responseContractor[$key], trim($value));
            }
        }

        $this->assertSame(null, $responseContractor['default_payment_term_days']);
        $this->assertSame(null, $responseContractor['default_payment_method_id']);
        $this->assertEquals($company->id, $responseContractor['company_id']);
        $this->assertEquals($this->user->id, $responseContractor['editor_id']);
        $this->assertEquals(0, $responseContractor['remover_id']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['created_at']);
        $this->assertSame($now->toDateTimeString(), $responseContractor['updated_at']);
        $this->assertSame(null, $responseContractor['deleted_at']);
    }

    /** @test */
    public function update_success_db_with_default_company_settings()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $paymentMethod = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);

        $beforeContractors = Contractor::all();

        $fields = $this->incomingData();

        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        )->seeStatusCode(200);

        $this->assertEquals(count($beforeContractors), count(Contractor::all()));
        $dbContractor = $contractor->fresh();

        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $this->assertEquals($dbContractor->$key, trim($value));
            }
        }

        $this->assertSame(null, $dbContractor->default_payment_term_days);
        $this->assertSame(null, $dbContractor->default_payment_method_id);
        $this->assertEquals($company->id, $dbContractor->company_id);
        $this->assertEquals($this->user->id, $dbContractor->editor_id);
        $this->assertEquals(0, $dbContractor->remover_id);
        $this->assertSame($now->toDateTimeString(), $dbContractor->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $dbContractor->updated_at->toDateTimeString());
        $this->assertSame(null, $dbContractor->deleted_at);
    }

    /** @test */
    public function update_not_my_contractor()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);
        $contractor = factory(Contractor::class)->create(['company_id' => $otherCompany->id]);

        $fields = $this->incomingData();

        $this->put(
            '/contractors/' . $contractor->id . '?selected_company_id=' . $company->id,
            $fields
        );
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);

        $dbContractor = $contractor->fresh();

        foreach ($contractor->getAttributes() as $key => $value) {
            $this->assertEquals($dbContractor->$key, trim($value));
        }
    }

    /** @test */
    public function delete_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = factory(Contractor::class)->create(['company_id' => $company->id]);

        $this->delete('contractors/' . $contractor->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $contractor_deleted = Contractor::withTrashed()->find($contractor->id);
        $this->assertSame($this->user->id, $contractor_deleted->remover_id);
        $this->assertNotNull($contractor_deleted->deleted_at);
    }

    /** @test */
    public function delete_cant_delete_used_contractor()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'is_used' => true,
        ]);

        $this->delete('contractors/' . $contractor->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(404);

        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function delete_not_my_contractor()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $otherCompany = factory(Company::class)->create();
        auth()->loginUsingId($this->user->id);

        $contractor = factory(Contractor::class)->create(['company_id' => $otherCompany->id]);

        $this->delete('contractors/' . $contractor->id . '?selected_company_id=' . $company->id);

        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
        $contractor_deleted = Contractor::withTrashed()->find($contractor->id);
        $this->assertNull($contractor_deleted->deleted_at);
    }

    protected function incomingData()
    {
        $contractor = [
            'name' => 'Contractor name',
            'country_vatin_prefix_id' => '',
            'vatin' => '',
            'email' => 'set@example.com',
            'phone' => '123456789',
            'bank_name' => 'Set bank_name',
            'bank_account_number' => '0123456789012345678901234',

            'main_address_street' => 'Set address_street',
            'main_address_number' => '00',
            'main_address_zip_code' => '00-000',
            'main_address_city' => 'Set address_city',
            'main_address_country' => 'Polska',

            'contact_address_street' => ' Set c_address_street',
            'contact_address_number' => '11',
            'contact_address_zip_code' => '11-111',
            'contact_address_city' => 'Set address_city ',
            'contact_address_country' => ' Afganistan ',
            'addresses' => [
                [
                    'type' => ContractorAddressType::DELIVERY,
                    'street' => 'Delivery address_street',
                    'number' => '99',
                    'zip_code' => '99-999',
                    'city' => 'Delivery address_city',
                    'country' => 'Albania',
                    'default' => 1,
                ],
            ],
        ];

        return $contractor;
    }
}
