<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\IntegrationController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class IndexTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ResponseHelper, ProjectHelper;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var IntegrationProvider
     */
    protected $hubstaff_integration_provider;

    /**
     * @var IntegrationProvider
     */
    protected $upwork_integration_provider;

    /**
     * @var Collection
     */
    protected $users;

    /**
     * @var Collection
     */
    protected $integrations;

    /**
     * @var Collection
     */
    protected $expected_response_integrations;

    /**
     * @inheritdoc
     */
    public function setUp():void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = factory(Company::class)->create();

        $this->hubstaff_integration_provider = IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF);
        $this->upwork_integration_provider = IntegrationProvider::findBySlug(IntegrationProvider::UPWORK);

        $this->integrations = $this->createIntegrations();
        $this->expected_response_integrations = $this->getExpectedResponses();
    }

    /** @test */
    public function it_gets_list_of_all_company_integrations_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/' . '?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);

        $this->verifyResponseItems([0, 1, 2]);

        $this->seeJsonStructure([
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
        ]);
    }

    /** @test */
    public function it_gets_list_of_company_integrations_with_given_provider_only_any_user_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/' . '?selected_company_id=' .
            $this->company->id . '&integration_provider_id=' . $this->hubstaff_integration_provider->id)
            ->seeStatusCode(200);

        $this->verifyResponseItems([0, 1]);
    }

    /** @test */
    public function it_gets_list_of_company_integrations_filtered_by_active_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/' . '?selected_company_id=' .
            $this->company->id . '&active=1')
            ->seeStatusCode(200);

        $this->verifyResponseItems([0, 2]);
    }

    /** @test */
    public function it_gets_no_permission_for_developer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEVELOPER);
    }

    /** @test */
    public function it_gets_no_permission_for_client()
    {
        $this->verifyNoPermissionForRole(RoleType::CLIENT);
    }

    /** @test */
    public function it_gets_no_permission_for_dealer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEALER);
    }

    protected function verifyResponseItems(array $integration_ids)
    {
        $this->verifyDataResponse($integration_ids, $this->expected_response_integrations);
    }

    protected function verifyNoPermissionForRole($role_slug)
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, $role_slug);

        $this->get('/integrations/' . '?selected_company_id=' .
            $this->company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function getExpectedResponses()
    {
        $responses = collect();
        $this->integrations->each(function ($project) use ($responses) {
            $responses->push($this->getIntegrationResponse($project));
        });

        return $responses;
    }

    protected function getIntegrationResponse(Integration $integration)
    {
        $integration = $integration->fresh();
        $data = array_except($integration->attributesToArray(), ['settings','info']);
        $data['active'] = (bool) $data['active'];
        $data['provider']['data'] = $integration->provider ? $integration->provider->attributesToArray() : null;
        if ($integration->settings) {
            $data['public_settings'] = [
                'start_time' => $integration->settings->start_time,
            ];
        } else {
            $data['public_settings'] = [];
        }

        return $data;
    }

    protected function createIntegrations()
    {
        $integrations = collect();
        $integrations->push(factory(Integration::class)->create([
            'company_id' => $this->company->id,
            'integration_provider_id' => $this->hubstaff_integration_provider->id,
            'settings' => [
                'app_token' => 'secret_token',
                'auth_token' => 'secret_auth_token',
                'start_time' => Carbon::now()->subDays(10)->toDateTimeString(),
            ],
            'info' => [
                'some' => 'data',
            ],
            'active' => 1,
        ]));

        $integrations->push(factory(Integration::class)->create([
            'company_id' => $this->company->id,
            'integration_provider_id' => $this->hubstaff_integration_provider->id,
            'settings' => [
                'app_token' => 'secret_token',
                'auth_token' => 'secret_auth_token',
                'start_time' => Carbon::now()->subDays(10)->toDateTimeString(),
            ],
            'info' => [
                'some' => 'data',
            ],
            'active' => 0,
        ]));

        $integrations->push(factory(Integration::class)->create([
            'company_id' => $this->company->id,
            'integration_provider_id' => $this->upwork_integration_provider->id,
            'settings' => null,
            'info' => null,
            'active' => 1,
        ]));

        $integrations->push(factory(Integration::class)->create([
            'company_id' => factory(Company::class)->create()->id, // other company
            'integration_provider_id' => $this->upwork_integration_provider->id,
            'settings' => null,
            'info' => null,
            'active' => 1,
        ]));

        return $integrations;
    }
}
