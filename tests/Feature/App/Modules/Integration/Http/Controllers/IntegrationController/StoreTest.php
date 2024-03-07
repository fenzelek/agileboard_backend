<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\IntegrationController;

use App\Helpers\ErrorCode;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Other\RoleType;
use App\Modules\Integration\Http\Requests\TimeTracking\Hubstaff as HubstaffRequest;
use App\Modules\Integration\Services\Factory;
use App\Modules\Integration\Services\TimeTracking\Hubstaff;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CreateUser;
use Mockery as m;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CreateUser;

    /** @test */
    public function it_returns_validation_error_when_no_data_sent()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $this->post('integrations?selected_company_id=' . $company->id);

        $this->verifyValidationResponse(['integration_provider_id']);
    }

    /** @test */
    public function it_gets_no_permission_error_for_admin()
    {
        $this->verifyNoPermissionForRole(RoleType::ADMIN);
    }

    /** @test */
    public function it_gets_no_permission_error_for_dealer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEALER);
    }

    /** @test */
    public function it_gets_no_permission_error_for_developer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEVELOPER);
    }

    /** @test */
    public function it_goes_to_further_validation_process_when_basic_data_is_valid()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $this->post('integrations?selected_company_id=' . $company->id, [
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
            'settings' => [
                'dummy_setting' => 1,
            ],
        ]);

        $this->verifyValidationResponse([
            'settings.app_token',
            'settings.auth_token',
            'settings.start_time',
        ], [
            'integration_provider_id',
            'settings',
            'settings.dummy_setting',
        ]);
    }

    /** @test */
    public function it_creates_integration_when_verification_was_fine()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $track_time = m::mock(TrackTime::class);
        $track_time->shouldReceive('verify')->once()->andReturn(true);

        $initial_integration_count = Integration::count();

        app()->instance(TrackTime::class, $track_time);

        $hubstaff_integration_type = IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF);

        $data = [
            'integration_provider_id' => $hubstaff_integration_type->id,
            'settings' => [
                'app_token' => 'sample token for hubstaff',
                'auth_token' => 'auth token for hubstaff',
                'start_time' => '2017-08-05 07:08:09',
                'junk' => 'aaaa',
            ],
        ];

        $this->post('integrations?selected_company_id=' . $company->id, $data)->seeStatusCode(201);

        $this->assertSame($initial_integration_count + 1, Integration::count());

        $response = $this->decodeResponseJson()['data'];

        // model/record in database
        $integration = Integration::findOrFail($response['id']);
        $this->assertSame($company->id, $integration->company_id);
        $this->assertSame($data['integration_provider_id'], $integration->integration_provider_id);
        $this->assertSame(array_except($data['settings'], 'junk'), (array) $integration->settings);
        $this->assertNull($integration->info);
        $this->assertTrue($integration->active);

        // make sure no raw secrets were saved
        $raw_settings = $integration->getRawOriginal('settings');
        $this->assertStringNotContainsString($data['settings']['app_token'], $raw_settings);
        $this->assertStringNotContainsString($data['settings']['auth_token'], $raw_settings);
        // and start time should be saved as it was given
        $this->assertStringContainsString($data['settings']['start_time'], $raw_settings);

        // response
        $this->assertSame($company->id, $response['company_id']);
        $this->assertSame($data['integration_provider_id'], $response['integration_provider_id']);
        $this->assertSame(
            ['start_time' => $data['settings']['start_time']],
            $response['public_settings']
        );
        $this->assertArrayNotHasKey('settings', $response);
        $this->assertArrayNotHasKey('info', $response);
        $this->assertTrue($response['active']);
    }

    /** @test */
    public function it_doesnt_create_integration_when_verification_failed()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $track_time = m::mock(TrackTime::class);
        $track_time->shouldReceive('verify')->once()->andReturn(false);

        $initial_integration_count = Integration::count();

        app()->instance(TrackTime::class, $track_time);

        $hubstaff_integration_type = IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF);

        $data = [
            'integration_provider_id' => $hubstaff_integration_type->id,
            'settings' => [
                'app_token' => 'sample token for hubstaff',
                'auth_token' => 'auth token for hubstaff',
                'start_time' => '2017-08-05 07:08:09',
                'junk' => 'aaaa',
            ],
        ];

        $this->post('integrations?selected_company_id=' . $company->id, $data);

        $this->verifyErrorResponse(412, ErrorCode::INTEGRATION_INVALID_TIME_TRACKING_DATA);

        $this->assertSame($initial_integration_count, Integration::count());
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_creates_integration_for_non_time_tracking_type_without_running_verification()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $track_time = m::mock(TrackTime::class);
        $track_time->shouldNotReceive('verify');

        $initial_integration_count = Integration::count();

        app()->instance(TrackTime::class, $track_time);

        // we mock here hubstaff just to have valid provider and validation
        $hubstaff = m::mock(Hubstaff::class)->makePartial();
        $hubstaff->shouldReceive('getValidationClass')->andReturn(HubstaffRequest::class);
        $factory = m::mock('overload:' . Factory::class);
        $factory->shouldReceive('make')->once()->andReturn($hubstaff);

        // but in data we will send different type to make sure verification won't be run for
        // other type
        $integration_provider = factory(IntegrationProvider::class)->create(['type' => 'unknown']);

        $data = [
            'integration_provider_id' => $integration_provider->id,
            'settings' => [
                'app_token' => 'sample token for hubstaff',
                'auth_token' => 'auth token for hubstaff',
                'start_time' => '2017-08-05 07:08:09',
                'junk' => 'aaaa',
            ],
        ];

        $this->post('integrations?selected_company_id=' . $company->id, $data)->seeStatusCode(201);

        $this->assertSame($initial_integration_count + 1, Integration::count());

        $response = $this->decodeResponseJson()['data'];

        // model/record in database
        $integration = Integration::findOrFail($response['id']);
        $this->assertSame($company->id, $integration->company_id);
        $this->assertSame($data['integration_provider_id'], $integration->integration_provider_id);
        $this->assertSame(array_except($data['settings'], 'junk'), (array) $integration->settings);
        $this->assertNull($integration->info);
        $this->assertTrue($integration->active);

        // make sure no raw secrets were saved
        $raw_settings = $integration->getRawOriginal('settings');
        $this->assertStringNotContainsString($data['settings']['app_token'], $raw_settings);
        $this->assertStringNotContainsString($data['settings']['auth_token'], $raw_settings);
        // and start time should be saved as it was given
        $this->assertStringContainsString($data['settings']['start_time'], $raw_settings);

        // response
        $this->assertSame($company->id, $response['company_id']);
        $this->assertSame($data['integration_provider_id'], $response['integration_provider_id']);
        $this->assertSame(
            ['start_time' => $data['settings']['start_time']],
            $response['public_settings']
        );
        $this->assertArrayNotHasKey('settings', $response);
        $this->assertArrayNotHasKey('info', $response);
        $this->assertTrue($response['active']);
    }

    /** @test */
    public function it_gets_validation_error_for_upwork_provider()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $this->post('integrations?selected_company_id=' . $company->id, [
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
            'settings' => [
                'sample' => 'test',
            ],
            'sample_abc' => 'TEST',
        ]);

        $this->verifyValidationResponse(null, ['integration_provider_id', 'settings']);
    }

    /**
     * @feature Integration
     * @scenario Add Time Tracker Integration
     * @case Time Tracker Integration added
     *
     * @test
     */
    public function store_add_time_tracker_integration()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $response = $this->post('integrations?selected_company_id=' . $company->id, [
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
            'settings' => ['not-empty'],
        ]);

        $response->assertResponseStatus(201);

        $data = $this->decodeResponseJson()['data'];
        $this->assertTrue($data['active']);
    }

    protected function verifyNoPermissionForRole($role_type)
    {
        $this->createUser();
        $company = $this->createCompanyWithRole($role_type);
        auth()->login($this->user);

        $this->post('integrations?selected_company_id=' . $company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
