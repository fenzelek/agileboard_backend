<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CreateUser;

class IntegrationProviderControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CreateUser;

    /** @test */
    public function index_it_gets_list_of_all_available_providers_for_owner()
    {
        $this->verifyForCompanyRole(RoleType::OWNER);
    }

    /** @test */
    public function index_it_gets_list_of_all_available_providers_for_admin()
    {
        $this->verifyForCompanyRole(RoleType::ADMIN);
    }

    /** @test */
    public function index_it_gets_list_of_all_available_providers_for_dealer()
    {
        $this->verifyForCompanyRole(RoleType::DEALER);
    }

    /** @test */
    public function index_it_gets_list_of_all_available_providers_for_client()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::CLIENT);
        auth()->login($this->user);

        $this->get('integrations/providers/?selected_company_id=' . $company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_it_gets_permission_error_when_no_company_selected()
    {
        $this->createUser();
        $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $this->get('integrations/providers/');

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function verifyForCompanyRole($role_type)
    {
        $this->createUser();
        $company = $this->createCompanyWithRole($role_type);
        auth()->login($this->user);

        IntegrationProvider::whereRaw('1=1')->delete();

        $integration_providers = factory(IntegrationProvider::class, 3)->create();

        $this->get('integrations/providers/?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson()['data'];

        $this->assertCount(3, $response);
        $this->assertEquals($this->getExpectedResponse($integration_providers), $response);
    }

    protected function getExpectedResponse(Collection $integration_providers)
    {
        return $integration_providers->map(function ($integration_provider) {
            /* @var \App\Models\Db\Model $integration_provider */
            return $integration_provider->attributesToArray();
        })->all();
    }
}
