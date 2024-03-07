<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\IntegrationController\Hubstaff;

use App\Helpers\ErrorCode;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CreateUser;
use Mockery as m;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CreateUser;

    /** @test */
    public function it_saves_data_when_valid_data_is_sent()
    {
        $track_time = m::mock(TrackTime::class);
        $track_time->shouldReceive('verify')->once()->andReturn(true);

        app()->instance(TrackTime::class, $track_time);
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $request_data = [
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
            'settings' => [
                'app_token' => 'SAMPLE_APP_TOKEN_AAA',
                'auth_token' => 'super_secret authentication token',
                'start_time' => Carbon::now()->subDays(7)->toDateTimeString(),
            ],
        ];

        $this->assertSame(0, Integration::count());

        $this->post('integrations?selected_company_id=' . $company->id, $request_data)
            ->seeStatusCode(201);

        $this->assertSame(1, Integration::count());

        // make sure valid data are stored
        $integration = Integration::first();
        $this->assertEquals(
            $request_data['integration_provider_id'],
            $integration->integration_provider_id
        );
        $this->assertEquals($company->id, $integration->company_id);
        $this->assertEquals($request_data['settings'], (array) $integration->settings);
        $this->assertTrue($integration->active);

        // make sure raw data was encrypted
        $raw_settings = json_decode($integration->getRawOriginal('settings'));
        $this->assertStringNotContainsString($raw_settings->app_token, $request_data['settings']['app_token']);
        $this->assertSame(
            $request_data['settings']['app_token'],
            decrypt($raw_settings->app_token)
        );
        $this->assertStringNotContainsString(
            $raw_settings->auth_token,
            $request_data['settings']['auth_token']
        );
        $this->assertSame(
            $request_data['settings']['auth_token'],
            decrypt($raw_settings->auth_token)
        );
        $this->assertEquals($raw_settings->start_time, $request_data['settings']['start_time']);

        $response = $this->decodeResponseJson()['data'];
        $this->assertEquals($integration->id, $response['id']);
        $this->assertEquals(
            $request_data['integration_provider_id'],
            $response['integration_provider_id']
        );
        $this->assertEquals($company->id, $response['company_id']);
        $this->assertTrue($response['active']);
        $this->assertFalse(array_key_exists('settings', $response));
    }

    /** @test */
    public function it_doesnt_save_data_when_verification_failed()
    {
        $track_time = m::mock(TrackTime::class);
        $track_time->shouldReceive('verify')->once()->andReturn(false);

        app()->instance(TrackTime::class, $track_time);
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $request_data = [
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
            'settings' => [
                'app_token' => 'SAMPLE_APP_TOKEN_AAA',
                'auth_token' => 'super_secret authentication token',
                'start_time' => Carbon::now()->subDays(7)->toDateTimeString(),
            ],
        ];

        $this->assertSame(0, Integration::count());

        $this->post('integrations?selected_company_id=' . $company->id, $request_data);

        $this->verifyErrorResponse(412, ErrorCode::INTEGRATION_INVALID_TIME_TRACKING_DATA);

        $this->assertSame(0, Integration::count());
    }

    /** @test */
    public function it_saves_data_without_verification_for_other_provider_type()
    {
        $this->withoutExceptionHandling();
        $track_time = m::mock(TrackTime::class);
        $track_time->shouldNotReceive('verify');

        app()->instance(TrackTime::class, $track_time);
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->login($this->user);

        $hubstaff_provider = IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF);
        $hubstaff_provider->type = 'other';
        $hubstaff_provider->save();

        $request_data = [
            'integration_provider_id' => $hubstaff_provider->id,
            'settings' => [
                'app_token' => 'SAMPLE_APP_TOKEN_AAA',
                'auth_token' => 'super_secret authentication token',
                'start_time' => Carbon::now()->subDays(7)->toDateTimeString(),
            ],
        ];

        $this->assertSame(0, Integration::count());

        $this->expectException(Exception::class, 'No class found');

        $this->post('integrations?selected_company_id=' . $company->id, $request_data);
    }

    /** @test */
    public function it_gets_validation_error_when_required_settings_are_missing()
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
}
