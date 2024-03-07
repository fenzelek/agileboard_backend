<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingUserController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use App\Models\Db\User;
use App\Models\Other\RoleType;
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
     * @var Integration
     */
    protected $hubstaff_integration;

    /**
     * @var Integration
     */
    protected $upwork_integration;

    /**
     * @var Collection
     */
    protected $users;

    /**
     * @var Collection
     */
    protected $tracking_users;

    /**
     * @var Collection
     */
    protected $expected_response_users;

    /**
     * @inheritdoc
     */
    public function setUp():void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = factory(Company::class)->create();

        $this->hubstaff_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);

        $this->upwork_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
        ]);

        $this->users = factory(User::class, 2)->create();
        $this->setCompanyRole($this->company, $this->users[0], RoleType::DEVELOPER);

        $this->tracking_users = $this->createTimeTrackingUsers();
        $this->expected_response_users = $this->getExpectedResponses();
    }

    /** @test */
    public function it_gets_list_of_all_company_users_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/users/' . '?selected_company_id=' .
            $this->company->id)
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
    public function it_gets_list_of_company_users_not_assigned_to_any_user_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/users/' . '?selected_company_id=' .
            $this->company->id . '&user_id=empty')
            ->seeStatusCode(200);

        $this->verifyResponseItems([0]);
    }

    /** @test */
    public function it_gets_list_of_company_users_filtered_by_external_user_name_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/users/' . '?selected_company_id=' .
            $this->company->id . '&external_user_name=very_specific_user_name')
            ->seeStatusCode(200);

        $this->verifyResponseItems([0]);
    }

    /** @test */
    public function it_gets_list_of_company_users_filtered_by_external_user_email_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/users/' . '?selected_company_id=' .
            $this->company->id . '&external_user_email=very_specific_email')
            ->seeStatusCode(200);

        $this->verifyResponseItems([1]);
    }

    /** @test */
    public function it_gets_list_of_company_users_filtered_by_user_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/users/' . '?selected_company_id=' .
            $this->company->id . '&user_id=' . $this->users[0]->id)
            ->seeStatusCode(200);

        $this->verifyResponseItems([1]);
    }

    /** @test */
    public function it_gets_validation_error_when_filtering_bu_user_id_not_assigned_to_company()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/users/' . '?selected_company_id=' .
            $this->company->id . '&user_id=' . $this->users[1]->id);

        $this->verifyValidationResponse(['user_id']);
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

    protected function verifyResponseItems(array $expected_user_ids)
    {
        $this->verifyDataResponse($expected_user_ids, $this->expected_response_users);
    }

    protected function verifyNoPermissionForRole($role_slug)
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, $role_slug);

        $this->get('/integrations/time_tracking/users/' . '?selected_company_id=' .
            $this->company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function getExpectedResponses()
    {
        $responses = collect();
        $this->tracking_users->each(function ($project) use ($responses) {
            $responses->push($this->getTimeTrackingResponse($project));
        });

        return $responses;
    }

    protected function getTimeTrackingResponse(TimeTrackingUser $user)
    {
        $user = $user->fresh();
        $data = $user->attributesToArray();
        $data['user']['data'] = $user->user ? $this->getExpectedUserResponse($user->user) : null;

        return $data;
    }

    protected function createTimeTrackingUsers()
    {
        $tracking_users = collect();
        $tracking_users->push(factory(TimeTrackingUser::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => null,
            'external_user_name' => 'test very_specific_user_name abc',
        ]));

        $tracking_users->push(factory(TimeTrackingUser::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'user_id' => $this->users[0]->id,
            'external_user_email' => 'test_very_specific_email_for_example_com@example.com',
        ]));

        $tracking_users->push(factory(TimeTrackingUser::class)->create([
            'integration_id' => $this->upwork_integration->id,
            'user_id' => $this->users[1]->id,
        ]));

        $company = factory(Company::class)->create();
        $other_company_hubstaff_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);

        $tracking_users->push(factory(TimeTrackingUser::class)->create([
            'integration_id' => $other_company_hubstaff_integration->id,
            'user_id' => $this->users[1]->id,
        ]));

        return $tracking_users;
    }
}
