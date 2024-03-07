<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Models\Db\CompanyService;
use App\Models\Db\ServiceUnit;
use App\Models\Other\RoleType;
use App\Models\Db\VatRate;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\BrowserKitTestCase;

class CompanyServiceControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_data_structure()
    {
        $company = $this->login_and_get_company();
        factory(CompanyService::class, 3)->create(['company_id' => $company->id]);

        $this->get('/companies/services?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'company_id',
                        'name',
                        'print_on_invoice',
                        'description',
                        'pkwiu',
                        'price_net',
                        'price_gross',
                        'vat_rate_id',
                        'service_unit_id',
                        'is_used',
                        'creator_id',
                        'editor_id',
                        'created_at',
                        'updated_at',
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
    public function index_response_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $unit = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first();
        CompanyService::whereRaw('1 = 1')->delete();
        $company_services = factory(CompanyService::class, 3)
            ->create([
                'company_id' => $company->id,
                'price_net' => 110,
                'price_gross' => 220,
                'service_unit_id' => $unit->id,
            ]);

        $this->get('/companies/services?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $response = $this->decodeResponseJson();
        $data = $response['data'];
        $pagination = $response['meta']['pagination'];

        $this->assertEquals($company_services->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($company_services[$key]->id, $item['id']);
            $this->assertEquals($company_services[$key]->company_id, $item['company_id']);
            $this->assertEquals($company_services[$key]->name, $item['name']);
            $this->assertSame($company_services[$key]->pkwiu, $item['pkwiu']);
            $this->assertEquals($company_services[$key]->vat_rate_id, $item['vat_rate_id']);
            $this->assertEquals($unit->id, $item['service_unit_id']);
            $this->assertEquals(1.1, $item['price_net']);
            $this->assertEquals(2.2, $item['price_gross']);
            $this->assertEquals($company_services[$key]->is_used, $item['is_used']);
            $this->assertEquals($company_services[$key]->creator_id, $item['creator_id']);
            $this->assertEquals($company_services[$key]->editor_id, $item['editor_id']);
            $this->assertEquals('kilogram', $item['service_unit']['data']['name']);
        }

        $this->assertEquals($company_services->count(), $pagination['total']);
        $this->assertEquals($company_services->count(), $pagination['count']);
    }

    /** @test */
    public function index_response_with_invalid_company_id()
    {
        $company = $this->login_and_get_company();
        factory(CompanyService::class, 3)->create(['company_id' => $company->id + 15]);

        $this->get('/companies/services?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(0, count($data));
    }

    /** @test */
    public function index_it_returns_data_with_name_param()
    {
        $company = $this->login_and_get_company();
        $company_services_first = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'first service',
        ]);
        factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'second service',
        ]);

        $this->get('companies/services?selected_company_id=' . $company->id . '&name=first')
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));
        $this->assertEquals($company_services_first->id, $data[0]['id']);
        $this->assertEquals($company_services_first->name, $data[0]['name']);
    }

    /** @test */
    public function index_sort_by_is_used_desc()
    {
        $company = $this->login_and_get_company();
        $most_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'is_used' => 100,
        ]);
        $less_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'is_used' => 1,
        ]);
        $middle_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'is_used' => 10,
        ]);

        $this->json(Request::METHOD_GET, 'companies/services', [
            'selected_company_id' => $company->id,
            'sort' => '-is_used',
        ])->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(3, count($data));
        $this->assertEquals($most_used_company_services->id, $data[0]['id']);
        $this->assertEquals($most_used_company_services->name, $data[0]['name']);
        $this->assertEquals($middle_used_company_services->id, $data[1]['id']);
        $this->assertEquals($middle_used_company_services->name, $data[1]['name']);
        $this->assertEquals($less_used_company_services->id, $data[2]['id']);
        $this->assertEquals($less_used_company_services->name, $data[2]['name']);
    }

    /** @test */
    public function index_sort_by_is_used_asc()
    {
        $company = $this->login_and_get_company();
        $most_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'is_used' => 100,
        ]);
        $less_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'is_used' => 1,
        ]);
        $middle_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'is_used' => 10,
        ]);

        $this->json(Request::METHOD_GET, 'companies/services', [
            'selected_company_id' => $company->id,
            'sort' => 'is_used',
        ])->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(3, count($data));
        $this->assertEquals($less_used_company_services->id, $data[0]['id']);
        $this->assertEquals($less_used_company_services->name, $data[0]['name']);
        $this->assertEquals($middle_used_company_services->id, $data[1]['id']);
        $this->assertEquals($middle_used_company_services->name, $data[1]['name']);
        $this->assertEquals($most_used_company_services->id, $data[2]['id']);
        $this->assertEquals($most_used_company_services->name, $data[2]['name']);
    }

    /** @test */
    public function index_sort_by_id_desc()
    {
        $company = $this->login_and_get_company();
        $first_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $second_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $last_used_company_services = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);

        $this->json(Request::METHOD_GET, 'companies/services', [
            'selected_company_id' => $company->id,
            'sort' => 'id',
        ])->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(3, count($data));
        $this->assertEquals($first_company_services->id, $data[0]['id']);
        $this->assertEquals($first_company_services->name, $data[0]['name']);
        $this->assertEquals($second_used_company_services->id, $data[1]['id']);
        $this->assertEquals($second_used_company_services->name, $data[1]['name']);
        $this->assertEquals($last_used_company_services->id, $data[2]['id']);
        $this->assertEquals($last_used_company_services->name, $data[2]['name']);
    }

    /** @test */
    public function show_data_structure()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $company_service = factory(CompanyService::class)->create(['company_id' => $company->id]);

        $this->get('/companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'company_id',
                    'name',
                    'pkwiu',
                    'price_net',
                    'price_gross',
                    'vat_rate_id',
                    'service_unit_id',
                    'is_used',
                    'creator_id',
                    'editor_id',
                    'created_at',
                    'updated_at',
                ],
            ])->isJson();
    }

    /** @test */
    public function show_response_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'price_net' => 110,
            'price_gross' => 220,
        ]);

        $this->get('/companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($company_service->id, $data['id']);
        $this->assertEquals($company_service->company_id, $data['company_id']);
        $this->assertEquals($company_service->name, $data['name']);
        $this->assertEquals($company_service->pkwiu, $data['pkwiu']);
        $this->assertEquals($company_service->vat_rate_id, $data['vat_rate_id']);
        $this->assertEquals(1.1, $data['price_net']);
        $this->assertEquals(2.2, $data['price_gross']);
        $this->assertEquals($company_service->is_used, $data['is_used']);
        $this->assertEquals($company_service->creator_id, $data['creator_id']);
        $this->assertEquals($company_service->editor_id, $data['editor_id']);
        $this->assertEquals('sztuka', $data['service_unit']['data']['name']);
    }

    /** @test */
    public function show_response_data_with_nullable_prices()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'price_net' => null,
            'price_gross' => null,
        ]);

        $this->get('/companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($company_service->id, $data['id']);
        $this->assertEquals($company_service->company_id, $data['company_id']);
        $this->assertEquals($company_service->name, $data['name']);
        $this->assertEquals($company_service->pkwiu, $data['pkwiu']);
        $this->assertEquals($company_service->vat_rate_id, $data['vat_rate_id']);
        $this->assertNull($data['price_net']);
        $this->assertNull($data['price_gross']);
        $this->assertEquals($company_service->is_used, $data['is_used']);
        $this->assertEquals($company_service->creator_id, $data['creator_id']);
        $this->assertEquals($company_service->editor_id, $data['editor_id']);
    }

    /** @test */
    public function show_response_with_invalid_company_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $company_service = factory(CompanyService::class)
            ->create(['company_id' => $company->id + 10]);

        $this->get('/companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id)
            ->seeStatusCode(404);
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('companies/services?selected_company_id=' . $company->id);

        $this->verifyValidationResponse(['name', 'type', 'vat_rate_id', 'pkwiu']);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_vat_rate_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => 'xxx',
            'type' => CompanyService::TYPE_SERVICE,
            'vat_rate_id' => 'abc',
            'pkwiu' => '',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => null,
            'price_gross' => null,
        ]);

        $this->verifyValidationResponse(['vat_rate_id'], ['name', 'type', 'pkwiu', 'price_net', 'price_gross']);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_pkwiu()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'pkwiu' => 1234567890,
        ]);
        $this->verifyValidationResponse(['pkwiu']);
    }

    /** @test */
    public function store_it_returns_validation_error_when_lack_price_gross_to_incoming_price_net()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'price_net' => 123,
        ]);

        $this->verifyValidationResponse(['price_gross']);
    }

    /** @test */
    public function store_it_returns_validation_error_when_lack_price_net_to_incoming_price_gross()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'price_gross' => 123,
        ]);
        $this->verifyValidationResponse(['price_net']);
    }

    /** @test */
    public function store_it_returns_validation_error_price_net_and_price_gross()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'price_net' => 0,
            'price_gross' => 1000000000000,
        ]);
        $this->verifyValidationResponse(['price_net', 'price_gross']);
    }

    /** @test */
    public function store_it_saves_valid_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $unit = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first();
        $count = CompanyService::count();

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => $unit->id,
            'pkwiu' => '',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => 1.11,
            'price_gross' => 2.22,
        ]);

        $this->assertEquals($count + 1, CompanyService::count());

        $company_service = CompanyService::latest('id')->first();

        $this->assertSame($company->id, $company_service->company_id);
        $this->assertSame('Sample service', $company_service->name);
        $this->assertSame(CompanyService::TYPE_ARTICLE, $company_service->type);
        $this->assertEquals(111, $company_service->price_net);
        $this->assertEquals(222, $company_service->price_gross);
        $this->assertSame($vat_rate->id, $company_service->vat_rate_id);
        $this->assertSame($unit->id, $company_service->service_unit_id);
        $this->assertSame($this->user->id, $company_service->creator_id);
        $this->assertSame(0, $company_service->editor_id);
        $this->assertSame('', $company_service->pkwiu);
    }

    /** @test */
    public function store_it_sends_wrong_service_unit_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $count = CompanyService::count();

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => 4567,
            'pkwiu' => '',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => 1.11,
            'price_gross' => 2.22,
        ]);

        $this->verifyValidationResponse(['service_unit_id']);

        $this->assertEquals($count, CompanyService::count());
    }

    /** @test */
    public function store_it_saves_valid_data_when_prices_are_empty()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $unit = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first();
        $count = CompanyService::count();

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => $unit->id,
            'pkwiu' => '',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => null,
            'price_gross' => null,
        ]);

        $this->assertEquals($count + 1, CompanyService::count());

        $company_service = CompanyService::latest('id')->first();

        $this->assertSame($company->id, $company_service->company_id);
        $this->assertSame('Sample service', $company_service->name);
        $this->assertSame(CompanyService::TYPE_ARTICLE, $company_service->type);
        $this->assertNull($company_service->price_net);
        $this->assertNull($company_service->price_gross);
        $this->assertSame($vat_rate->id, $company_service->vat_rate_id);
        $this->assertSame($this->user->id, $company_service->creator_id);
        $this->assertSame(0, $company_service->editor_id);
        $this->assertSame('', $company_service->pkwiu);
    }

    /** @test */
    public function store_it_saves_valid_data_with_pkwiu_not_empty()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $count = CompanyService::count();

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'vat_rate_id' => $vat_rate->id,
            'pkwiu' => '15.20.99.0',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => null,
            'price_gross' => null,
        ])->seeStatusCode(201)->isJson();

        $this->assertEquals($count + 1, CompanyService::count());

        $company_service = CompanyService::latest('id')->first();

        $this->assertSame($company->id, $company_service->company_id);
        $this->assertSame('Sample service', $company_service->name);
        $this->assertSame($vat_rate->id, $company_service->vat_rate_id);
        $this->assertSame('15.20.99.0', $company_service->pkwiu);
    }

    /** @test */
    public function store_it_returns_valid_structure_response()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'pkwiu' => '',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => null,
            'price_gross' => null,
        ])->seeStatusCode(201)->isJson();

        $this->seeJsonStructure([
            'data' => [
                'id',
                'name',
                'type',
                'vat_rate_id',
                'price_net',
                'price_gross',
                'creator_id',
                'editor_id',
                'company_id',
            ],
        ]);

        return $this->decodeResponseJson()['data'];
    }

    /** @test */
    public function store_it_returns_valid_response_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'pkwiu' => '11.22.33.4',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => 1.11,
            'price_gross' => 2.22,

        ]);

        $output = $this->decodeResponseJson()['data'];

        $company_service = CompanyService::latest('id')->first();

        $this->assertSame($company_service->id, $output['id']);
        $this->assertSame('Sample service', $output['name']);
        $this->assertSame('11.22.33.4', $output['pkwiu']);
        $this->assertSame($vat_rate->id, $output['vat_rate_id']);
        $this->assertEquals(1.11, $output['price_net']);
        $this->assertEquals(2.22, $output['price_gross']);
        $this->assertSame($this->user->id, $output['creator_id']);
        $this->assertNull($output['editor_id']);
        $this->assertSame($company->id, $output['company_id']);
    }

    /** @test */
    public function store_it_stores_with_service_description()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'pkwiu' => '11.22.33.4',
            'print_on_invoice' => true,
            'description' => 'Some description',
            'price_net' => null,
            'price_gross' => null,
        ])->seeStatusCode(201)->isJson();

        $response_data = $this->response->getData()->data;

        $company_service = CompanyService::latest('id')->first();

        $this->assertEquals($company_service->id, $response_data->id);
        $this->assertEquals(1, $response_data->print_on_invoice);
        $this->assertEquals('Some description', $response_data->description);
    }

    /** @test */
    public function store_adding_description_without_print_on_invoice_doesnt_store_it()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'pkwiu' => '11.22.33.4',
            'print_on_invoice' => false,
            'description' => 'Some description',
            'price_net' => null,
            'price_gross' => null,
        ])->seeStatusCode(201)->isJson();

        $response_data = $this->response->getData()->data;
        $company_service = CompanyService::latest('id')->first();

        $this->assertEquals($company_service->id, $response_data->id);
        $this->assertEquals(0, $response_data->print_on_invoice);
        $this->assertEquals('Some description', $response_data->description);
    }

    /** @test */
    public function store_adding_too_long_description_throw_error()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);

        $this->post('companies/services?selected_company_id=' . $company->id, [
            'name' => '  Sample service  ',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'pkwiu' => '11.22.33.4',
            'print_on_invoice' => false,
            'description' => str_random(1001),
        ]);

        $this->verifyValidationResponse(['description']);
    }

    /** @test */
    public function update_it_returns_valid_structure_response()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $unit = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'pkwiu' => '11.22.33.4',
            'vat_rate_id' => $vat_rate->id,
            'creator_id' => $user->id,
            'price_net' => 111,
            'price_gross' => 222,
        ]);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => 'Example service',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => $unit->id,
            'pkwiu' => '',
            'print_on_invoice' => false,
            'description' => '',
            'price_net' => null,
            'price_gross' => null,
        ])->seeStatusCode(200)->isJson();

        $this->seeJsonStructure([
            'data' => [
                'id',
                'name',
                'type',
                'pkwiu',
                'vat_rate_id',
                'service_unit_id',
                'price_net',
                'price_gross',
                'creator_id',
                'editor_id',
                'company_id',
                'is_used',
            ],
        ]);
    }

    /** @test */
    public function update_it_returns_valid_response_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $unit = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'pkwiu' => '11.22.33.4',
            'vat_rate_id' => $vat_rate->id,
            'creator_id' => $user->id,
            'price_net' => null,
            'price_gross' => null,
        ]);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => 'Example service',
            'type' => CompanyService::TYPE_ARTICLE,
            'print_on_invoice' => false,
            'description' => '',
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => $unit->id,
            'pkwiu' => '',
            'price_net' => 1.11,
            'price_gross' => 2.22,
        ])->seeStatusCode(200);

        $output = $this->decodeResponseJson()['data'];

        $updated_company_service = CompanyService::latest('id')->first();

        $this->assertSame($updated_company_service->id, $output['id']);
        $this->assertSame('Example service', $output['name']);
        $this->assertSame(CompanyService::TYPE_ARTICLE, $output['type']);
        $this->assertEmpty($output['pkwiu']);
        $this->assertSame($vat_rate->id, $output['vat_rate_id']);
        $this->assertSame($unit->id, $output['service_unit_id']);
        $this->assertSame(1.11, $output['price_net']);
        $this->assertSame(2.22, $output['price_gross']);
        $this->assertSame($user->id, $output['creator_id']);
        $this->assertSame($this->user->id, $output['editor_id']);
        $this->assertSame($company->id, $output['company_id']);
    }

    /** @test */
    public function update_incorrect_company_service_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'print_on_invoice' => false,
            'description' => '',
            'vat_rate_id' => $vat_rate->id,
            'creator_id' => $user->id,
        ]);

        $this->put('companies/services/' . ($company_service->id + 100) . '?selected_company_id=' .
            $company->id, [
            'name' => 'Example service',
            'vat_rate_id' => $vat_rate->id,
        ])->seeStatusCode(401);

        $updated_company_service = $company_service->fresh();

        $this->assertSame($company_service->company_id, $updated_company_service->company_id);
        $this->assertSame($company_service->name, $updated_company_service->name);
        $this->assertSame($company_service->type, $updated_company_service->type);
        $this->assertSame($company_service->vat_rate_id, $updated_company_service->vat_rate_id);
        $this->assertSame($company_service->creator_id, $updated_company_service->creator_id);
        $this->assertSame(0, $updated_company_service->editor_id);
    }

    /** @test */
    public function update_incorrect_selected_company_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id + 50,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id,
            'creator_id' => $user->id,
        ]);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => 'Example service',
            'print_on_invoice' => false,
            'description' => '',
            'vat_rate_id' => $vat_rate->id,
        ])->seeStatusCode(401);

        $updated_company_service = $company_service->fresh();

        $this->assertSame($company_service->company_id, $updated_company_service->company_id);
        $this->assertSame($company_service->name, $updated_company_service->name);
        $this->assertSame($company_service->vat_rate_id, $updated_company_service->vat_rate_id);
        $this->assertSame($company_service->creator_id, $updated_company_service->creator_id);
        $this->assertSame(0, $updated_company_service->editor_id);
    }

    /** @test */
    public function update_save_incorrect_vat_rate_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id,
            'creator_id' => $user->id,
        ]);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => 'Example',
            'print_on_invoice' => false,
            'description' => '',
            'vat_rate_id' => 'abc',
        ]);

        $this->verifyValidationResponse(['vat_rate_id'], ['name']);
    }

    /** @test */
    public function update_save_validation_error_by_pkwiu_invalid()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id,
            'creator_id' => $user->id,
        ]);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'pkwiu' => 123456,
            'print_on_invoice' => false,
            'description' => '',
        ])->seeStatusCode(422);

        $this->verifyValidationResponse(['pkwiu']);
    }

    /** @test */
    public function update_save_correct_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id + 10,
            'creator_id' => $user->id,
            'is_used' => 0,
        ]);

        $count = CompanyService::count();

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => '   Example service   ',
            'type' => CompanyService::TYPE_ARTICLE,
            'print_on_invoice' => false,
            'description' => '',
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'is_used' => 1,
            'pkwiu' => '99.88.77.6',
            'price_net' => 1.11,
            'price_gross' => 2.22,
        ])->seeStatusCode(200);

        $this->assertEquals($count, CompanyService::count());

        $updated_company_service = $company_service->fresh();

        $this->assertSame($company_service->company_id, $updated_company_service->company_id);
        $this->assertSame('Example service', $updated_company_service->name);
        $this->assertSame(CompanyService::TYPE_ARTICLE, $updated_company_service->type);
        $this->assertSame($vat_rate->id, $updated_company_service->vat_rate_id);
        $this->assertSame(111, $updated_company_service->price_net);
        $this->assertSame(222, $updated_company_service->price_gross);
        $this->assertSame($company_service->creator_id, $updated_company_service->creator_id);
        $this->assertSame($this->user->id, $updated_company_service->editor_id);
        $this->assertSame($company_service->is_used, $updated_company_service->is_used);
    }

    /** @test */
    public function update_it_updates_price_net_and_price_gross()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id + 10,
            'creator_id' => $user->id,
            'is_used' => 0,
            'price_net' => 111,
            'price_gross' => 222,
        ]);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => '   Example service   ',
            'type' => CompanyService::TYPE_ARTICLE,
            'print_on_invoice' => false,
            'description' => '',
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'is_used' => 1,
            'pkwiu' => '99.88.77.6',
            'price_net' => 5.55,
            'price_gross' => 3.33,

        ])->seeStatusCode(200);
        $updated_company_service = $company_service->fresh();

        $this->assertSame(555, $updated_company_service->price_net);
        $this->assertSame(333, $updated_company_service->price_gross);
    }

    /** @test */
    public function update_it_adds_description_to_service()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id + 10,
            'creator_id' => $this->user->id,
            'is_used' => 0,
        ]);

        $count = CompanyService::count();
        $this->assertEquals(0, $company_service->print_on_invoice);
        $this->assertNull($company_service->description);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => 'First service',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'pkwiu' => '99.88.77.6',
            'print_on_invoice' => true,
            'description' => 'Some description',
            'price_net' => null,
            'price_gross' => null,
        ])->seeStatusCode(200);

        $this->assertEquals($count, CompanyService::count());

        $response_data = $this->response->getData()->data;

        $this->assertEquals($company_service->id, $response_data->id);
        $this->assertEquals(1, $response_data->print_on_invoice);
        $this->assertEquals('Some description', $response_data->description);
    }

    /** @test */
    public function update_it_updates_description_in_service()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id + 10,
            'creator_id' => $this->user->id,
            'is_used' => 0,
            'print_on_invoice' => true,
            'description' => 'Old description',
        ]);

        $count = CompanyService::count();
        $this->assertEquals(1, $company_service->print_on_invoice);
        $this->assertEquals('Old description', $company_service->description);

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => 'First service',
            'type' => CompanyService::TYPE_ARTICLE,
            'vat_rate_id' => $vat_rate->id,
            'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            'pkwiu' => '99.88.77.6',
            'print_on_invoice' => true,
            'description' => 'New description',
            'price_net' => null,
            'price_gross' => null,
        ])->seeStatusCode(200);

        $this->assertEquals($count, CompanyService::count());

        $response_data = $this->response->getData()->data;

        $this->assertEquals($company_service->id, $response_data->id);
        $this->assertEquals(1, $response_data->print_on_invoice);
        $this->assertEquals('New description', $response_data->description);
    }

    /** @test */
    public function update_when_item_is_used()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $vat_rate = factory(VatRate::class)->create(['is_visible' => 1]);
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
            'name' => 'First service',
            'vat_rate_id' => $vat_rate->id + 10,
            'creator_id' => $user->id,
            'is_used' => true,
        ]);

        $count = CompanyService::count();

        $this->put('companies/services/' . $company_service->id . '?selected_company_id=' .
            $company->id, [
            'name' => 'Example service',
            'vat_rate_id' => $vat_rate->id,
        ])->seeStatusCode(401);

        $this->assertEquals($count, CompanyService::count());

        $updated_company_service = $company_service->fresh();

        $this->assertSame($company_service->company_id, $updated_company_service->company_id);
        $this->assertSame($company_service->name, $updated_company_service->name);
        $this->assertSame($company_service->vat_rate_id, $updated_company_service->vat_rate_id);
        $this->assertSame($company_service->creator_id, $updated_company_service->creator_id);
        $this->assertSame(0, $updated_company_service->editor_id);
    }

    private function login_and_get_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        CompanyService::whereRaw('1 = 1')->delete();

        return $company;
    }
}
