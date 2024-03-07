<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking;

use App\Models\Other\Integration\TimeTracking\Activity;
use App\Models\Other\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Project;
use App\Models\Other\Integration\TimeTracking\User;
use App\Modules\Integration\Services\TimeTracking\Hubstaff;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery as m;
use stdClass;
use Tests\BrowserKitTestCase;

class HubstaffTest extends BrowserKitTestCase
{
    protected $settings = [];
    protected $info = [];

    protected $hubstaff_host = 'https://api.hubstaff.com/v1/';

    public function setUp():void
    {
        parent::setUp();

        $this->settings = [
            'app_token' => 'sample app token',
            'auth_token' => 'some auth token',
        ];

        $this->info = [
            'sample' => 'data',
            'another' => 'sample data',
        ];
    }

    /** @test */
    public function isReadyToRun_it_returns_true_when_start_date_in_past()
    {
        $this->settings['start_time'] = '2017-04-01 08:02:03';

        $hubstaff = $this->setNoFetchingHubstaffMock();

        $this->assertTrue($hubstaff->isReadyToRun());
    }

    /** @test */
    public function isReadyToRun_it_returns_true_when_start_date_in_future()
    {
        $this->settings['start_time'] = Carbon::now()->addHours(5)->toDateTimeString();

        $hubstaff = $this->setNoFetchingHubstaffMock();

        $this->assertFalse($hubstaff->isReadyToRun());
    }

    /** @test */
    public function projects_it_gets_empty_collection_when_no_projects()
    {
        $data = [
            'projects' => [],
        ];

        $hubstaff = $this->setHubstaffClientMock($data, 'projects');

        $projects = $hubstaff->projects();
        $this->assertTrue($projects->isEmpty());
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function projects_it_gets_valid_collection_when_projects_exist()
    {
        $hubstaff_projects = [
            [
                'id' => 34434,
                'name' => 'MODIFIED NAME',
                'last_activity' => '2017-08-13T11:10:00Z',
                'status' => 'Active',
                'description' => null,
            ],
            [
                'id' => 'HUBSTAFF_NEW_PROJECT',
                'name' => 'NEW PROJECT ',
                'last_activity' => '2017-08-14T11:10:00Z',
                'status' => 'Inactive',
                'description' => null,
            ],
        ];

        $data = [
            'projects' => $hubstaff_projects,
        ];

        $hubstaff = $this->setHubstaffClientMock($data, 'projects');

        $projects = $hubstaff->projects();
        $this->verifyProjects($projects, $hubstaff_projects);
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function projects_it_gets_valid_collection_when_there_are_202_projects()
    {
        $sample_project = [
            'id' => 34434,
            'name' => 'MODIFIED NAME',
            'last_activity' => '2017-08-13T11:10:00Z',
            'status' => 'Active',
            'description' => null,
        ];

        $hubstaff_projects = [];
        for ($i = 0; $i < 202; ++$i) {
            $project = $sample_project;
            $project['id'] = $i;
            $project['name'] = 'Some project name with ' . $i . ' index';
            $hubstaff_projects[] = $project;
        }

        $data = [
            'projects' => $hubstaff_projects,
        ];

        $hubstaff = $this->setHubstaffClientMock($data, 'projects');

        $projects = $hubstaff->projects();
        $this->verifyProjects($projects, $hubstaff_projects);
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function users_it_gets_empty_collection_when_no_users()
    {
        $data = [
            'users' => [],
        ];

        $hubstaff = $this->setHubstaffClientMock($data, 'users');

        $users = $hubstaff->users();
        $this->assertTrue($users->isEmpty());
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function users_it_gets_valid_collection_when_users_exist()
    {
        $hubstaff_users = [
            [
                'id' => 34434,
                'name' => 'Test user name',
                'last_activity' => '2017-08-13T11:10:00Z',
                'email' => 'sample@example.com',
            ],
            [
                'id' => 34435,
                'name' => 'Test user name 2',
                'last_activity' => '2017-09-13T11:10:00Z',
                'email' => 'sample2@example.com',
            ],
        ];

        $data = [
            'users' => $hubstaff_users,
        ];

        $hubstaff = $this->setHubstaffClientMock($data, 'users');

        $users = $hubstaff->users();

        $this->verifyUsers($users, $hubstaff_users);
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function users_it_gets_valid_collection_when_there_are_202_users()
    {
        $sample_user = [
            'id' => 34434,
            'name' => 'Test user name',
            'last_activity' => '2017-08-13T11:10:00Z',
            'email' => 'sample@example.com',
        ];

        $hubstaff_users = [];
        for ($i = 0; $i < 202; ++$i) {
            $user = $sample_user;
            $user['id'] = $i;
            $user['name'] = 'User name with ' . $i . ' index';
            $user['email'] = 'sample' . $i . '@example.com';
            $hubstaff_users[] = $user;
        }

        $data = [
            'users' => $hubstaff_users,
        ];

        $hubstaff = $this->setHubstaffClientMock($data, 'users');

        $users = $hubstaff->users();
        $this->verifyUsers($users, $hubstaff_users);
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function notes_it_gets_empty_collection_when_no_notes_and_use_24_hours_period()
    {
        $data = [
            'notes' => [],
        ];
        $this->settings['start_time'] = '2017-04-01 08:02:03';

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'notes',
            '?start_time=2017-04-01T08:00:00Z&stop_time=2017-04-02T08:00:00Z'
        );

        $notes = $hubstaff->notes();
        $this->assertTrue($notes->isEmpty());

        $this->assertSame(array_merge($this->info, [
            'note_utc_fetched_until' => '2017-04-02 07:58:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function notes_it_it_will_use_valid_period_when_already_fetched_data()
    {
        $data = [
            'notes' => [],
        ];
        $this->settings['start_time'] = '2017-04-01 08:02:03';
        $this->info['note_utc_fetched_until'] = '2017-08-03 09:28:00';

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'notes',
            '?start_time=2017-08-03T09:30:00Z&stop_time=2017-08-04T09:30:00Z'
        );

        $notes = $hubstaff->notes();
        $this->assertTrue($notes->isEmpty());

        $this->assertSame(array_merge($this->info, [
            'note_utc_fetched_until' => '2017-08-04 09:28:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function notes_it_gets_notes_only_up_to_30_minutes_before()
    {
        $data = [
            'notes' => [],
        ];

        $now = Carbon::parse('2017-08-22 10:01:02', 'UTC');
        Carbon::setTestNow($now);

        $this->settings['start_time'] = '2017-08-22 09:01:02';

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'notes',
            '?start_time=2017-08-22T09:00:00Z&stop_time=2017-08-22T09:30:00Z'
        );

        $notes = $hubstaff->notes();
        $this->assertTrue($notes->isEmpty());

        $this->assertSame(array_merge($this->info, [
            'note_utc_fetched_until' => '2017-08-22 09:28:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function notes_it_wont_try_to_fetch_notes_when_starting_time_in_future()
    {
        $now = Carbon::parse('2017-08-22 10:01:02', 'UTC');
        Carbon::setTestNow($now);

        $this->settings['start_time'] = '2017-08-31 09:01:02'; // time in future

        $hubstaff = $this->setNoFetchingHubstaffMock();

        $notes = $hubstaff->notes();
        $this->assertTrue($notes->isEmpty());
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function notes_it_gets_valid_collection_when_notes_exist()
    {
        $hubstaff_notes = [
            [
                'id' => 3413241,
                'description' => 'WWW-14',
                'task_id' => null,
                'time_slot' => '2017-08-01T05:00:00Z',
                'recorded_at' => '2017-08-01T05:00:17Z',
                'user_id' => 2131,
                'project_id' => 182931,
            ],
            [
                'id' => 3413217,
                'description' => 'X SAMPLE DESCRIPTION',
                'task_id' => null,
                'time_slot' => '2017-08-03T02:00:00Z',
                'recorded_at' => '2017-08-09T08:00:17Z',
                'user_id' => 2131,
                'project_id' => 182931,
            ],
        ];

        $this->settings['start_time'] = '2017-04-01 08:02:03';

        $data = [
            'notes' => $hubstaff_notes,
        ];

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'notes',
            '?start_time=2017-04-01T08:00:00Z&stop_time=2017-04-02T08:00:00Z'
        );

        $notes = $hubstaff->notes();

        $this->verifyNotes($notes, $hubstaff_notes);

        $this->assertSame(array_merge($this->info, [
            'note_utc_fetched_until' => '2017-04-02 07:58:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function notes_it_gets_valid_collection_when_there_are_177_notes()
    {
        $sample_note = [
            'id' => 3413241,
            'description' => 'WWW-14',
            'task_id' => null,
            'time_slot' => '2017-08-01T05:00:00Z',
            'recorded_at' => '2017-08-01T05:00:17Z',
            'user_id' => 2131,
            'project_id' => 182931,
        ];

        $hubstaff_notes = [];

        for ($i = 0; $i < 177; ++$i) {
            $note = $sample_note;
            $note['id'] = $i;
            $note['description'] = 'Note with ' . $i . ' index';
            $note['user_id'] += $i;
            $note['project_id'] += $i + 100;
            $hubstaff_notes[] = $note;
        }

        $this->settings['start_time'] = '2017-04-01 08:02:03';

        $data = [
            'notes' => $hubstaff_notes,
        ];

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'notes',
            '?start_time=2017-04-01T08:00:00Z&stop_time=2017-04-02T08:00:00Z'
        );

        $notes = $hubstaff->notes();

        $this->verifyNotes($notes, $hubstaff_notes);

        $this->assertSame(array_merge($this->info, [
            'note_utc_fetched_until' => '2017-04-02 07:58:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function activities_it_gets_empty_collection_when_no_notes_fetched_to_given_period()
    {
        $data = [
            'activities' => [],
        ];

        $this->settings['start_time'] = '2017-04-01 08:02:03';
        // it does not match full expected period - should be 2017-04-02 07:58:00
        $this->info['note_utc_fetched_until'] = '2017-04-02 06:58:00';

        $hubstaff = $this->setNoFetchingHubstaffMock();
        $hubstaff->shouldReceive('notesAreMissing')->once()->passthru();

        $activities = $hubstaff->activities();
        $this->assertTrue($activities->isEmpty());

        $this->assertSame(array_merge($this->info), $hubstaff->getInfo());
    }

    /** @test */
    public function activities_it_gets_empty_collection_when_no_activities_and_use_24_hours_period()
    {
        $data = [
            'activities' => [],
        ];

        $this->settings['start_time'] = '2017-04-01 08:02:03';
        $this->info['note_utc_fetched_until'] = '2017-04-02 07:58:00';

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'activities',
            '?start_time=2017-04-01T08:00:00Z&stop_time=2017-04-02T08:00:00Z'
        );

        $activities = $hubstaff->activities();
        $this->assertTrue($activities->isEmpty());

        $this->assertSame(array_merge($this->info, [
            'activity_utc_fetched_until' => '2017-04-02 07:58:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function activities_it_it_will_use_valid_period_when_already_fetched_data()
    {
        $data = [
            'activities' => [],
        ];
        $this->settings['start_time'] = '2017-04-01 08:02:03';
        $this->info['note_utc_fetched_until'] = '2017-08-04 09:28:00';
        $this->info['activity_utc_fetched_until'] = '2017-08-03 09:28:00';

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'activities',
            '?start_time=2017-08-03T09:30:00Z&stop_time=2017-08-04T09:30:00Z'
        );

        $activities = $hubstaff->activities();
        $this->assertTrue($activities->isEmpty());

        $this->assertSame(array_merge($this->info, [
            'activity_utc_fetched_until' => '2017-08-04 09:28:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function activities_it_gets_activities_only_up_to_30_minutes_before()
    {
        $data = [
            'activities' => [],
        ];

        $now = Carbon::parse('2017-08-22 10:01:02', 'UTC');
        Carbon::setTestNow($now);

        $this->settings['start_time'] = '2017-08-22 09:01:02';
        $this->info['note_utc_fetched_until'] = '2017-08-22 09:28:00';

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'activities',
            '?start_time=2017-08-22T09:00:00Z&stop_time=2017-08-22T09:30:00Z'
        );

        $activities = $hubstaff->activities();
        $this->assertTrue($activities->isEmpty());

        $this->assertSame(array_merge($this->info, [
            'activity_utc_fetched_until' => '2017-08-22 09:28:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function activities_it_wont_try_to_fetch_activities_when_starting_time_in_future()
    {
        $now = Carbon::parse('2017-08-22 10:01:02', 'UTC');
        Carbon::setTestNow($now);

        $this->settings['start_time'] = '2017-08-31 09:01:02'; // time in future
        $this->info['note_utc_fetched_until'] = '2017-08-22 09:28:00';

        $hubstaff = $this->setNoFetchingHubstaffMock();
        $hubstaff->shouldNotReceive('notesAreMissing');

        $activities = $hubstaff->activities();
        $this->assertTrue($activities->isEmpty());
        $this->assertSame($this->info, $hubstaff->getInfo());
    }

    /** @test */
    public function activities_it_gets_valid_collection_when_activities_exist()
    {
        $hubstaff_activities = [
            [
                'id' => 4341231,
                'time_slot' => '2017-08-01T08:10:00Z',
                'starts_at' => '2017-08-01T08:19:47Z',
                'user_id' => 453112,
                'project_id' => 12321,
                'task_id' => 421,
                'keyboard' => 3,
                'mouse' => 18,
                'overall' => 13,
                'tracked' => 13,
                'paid' => false,
            ],
            [
                'id' => '4123a23',
                'time_slot' => '2017-08-03T08:10:00Z',
                'starts_at' => '2017-08-03T08:17:47Z',
                'user_id' => 341231,
                'project_id' => 41231,
                'task_id' => null,
                'keyboard' => 6,
                'mouse' => 19,
                'overall' => 18,
                'tracked' => 300,
                'paid' => true,
            ],
        ];

        $this->settings['start_time'] = '2017-04-01 08:02:03';
        $this->info['note_utc_fetched_until'] = '2017-04-02 07:58:00';

        $data = [
            'activities' => $hubstaff_activities,
        ];

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'activities',
            '?start_time=2017-04-01T08:00:00Z&stop_time=2017-04-02T08:00:00Z'
        );

        $activities = $hubstaff->activities();

        $this->verifyActivities($activities, $hubstaff_activities);

        $this->assertSame(array_merge($this->info, [
            'activity_utc_fetched_until' => '2017-04-02 07:58:00',
        ]), $hubstaff->getInfo());
    }

    /** @test */
    public function activities_it_gets_valid_collection_when_there_are_135_activities()
    {
        $sample_activity = [
            'id' => 4341231,
            'time_slot' => '2017-08-01T08:10:00Z',
            'starts_at' => '2017-08-01T08:19:47Z',
            'user_id' => 453112,
            'project_id' => 12321,
            'task_id' => 421,
            'keyboard' => 3,
            'mouse' => 18,
            'overall' => 13,
            'tracked' => 13,
            'paid' => false,
        ];

        $hubstaff_activities = [];

        for ($i = 0; $i < 135; ++$i) {
            $activity = $sample_activity;
            $activity['id'] = $i;
            $activity['user_id'] += $i;
            $activity['project_id'] += $i + 100;
            $activity['task_id'] += $i + 3;
            $activity['keyboard'] = mt_rand(0, 100);
            $activity['mouse'] = mt_rand(0, 100);
            $activity['overall'] = mt_rand(100, 200);
            $activity['tracked'] = mt_rand(200, 500);
            $hubstaff_activities[] = $activity;
        }

        $this->settings['start_time'] = '2017-04-01 08:02:03';
        $this->info['note_utc_fetched_until'] = '017-04-02 07:58:00';

        $data = [
            'activities' => $hubstaff_activities,
        ];

        $hubstaff = $this->setHubstaffClientMock(
            $data,
            'activities',
            '?start_time=2017-04-01T08:00:00Z&stop_time=2017-04-02T08:00:00Z'
        );

        $activities = $hubstaff->activities();

        $this->verifyActivities($activities, $hubstaff_activities);

        $this->assertSame(array_merge($this->info, [
            'activity_utc_fetched_until' => '2017-04-02 07:58:00',
        ]), $hubstaff->getInfo());
    }

    protected function verifyProjects(Collection $object_projects, array $expected_projects)
    {
        $this->assertCount(count($expected_projects), $object_projects);
        $object_projects->each(function ($project, $key) use ($expected_projects) {
            /* @var Project $project */
            $this->assertTrue($project instanceof Project);
            $this->assertSame($expected_projects[$key]['id'], $project->getExternalId());
            $this->assertSame($expected_projects[$key]['name'], $project->getExternalName());
        });
    }

    protected function verifyUsers(Collection $object_users, array $expected_users)
    {
        $this->assertCount(count($expected_users), $object_users);
        $object_users->each(function ($user, $key) use ($expected_users) {
            /* @var User $user */
            $this->assertTrue($user instanceof User);
            $this->assertSame($expected_users[$key]['id'], $user->getExternalId());
            $this->assertSame($expected_users[$key]['name'], $user->getExternalName());
            $this->assertSame($expected_users[$key]['email'], $user->getExternalEmail());
        });
    }

    protected function verifyNotes(Collection $object_notes, array $expected_notes)
    {
        $this->assertCount(count($expected_notes), $object_notes);
        $object_notes->each(function ($note, $key) use ($expected_notes) {
            /* @var Note $note */
            $this->assertTrue($note instanceof Note);
            $this->assertSame($expected_notes[$key]['id'], $note->getExternalId());
            $this->assertSame($expected_notes[$key]['project_id'], $note->getExternalProjectId());
            $this->assertSame($expected_notes[$key]['user_id'], $note->getExternalUserId());
            $this->assertSame($expected_notes[$key]['description'], $note->getContent());
            $this->assertSame(
                trim(str_replace(
                    ['T', 'Z'],
                    [' '],
                    $expected_notes[$key]['recorded_at']
                )),
                $note->getUtcRecordedAt()->toDateTimeString()
            );
        });
    }

    protected function verifyActivities(Collection $object_activities, array $expected_activities)
    {
        $this->assertCount(count($expected_activities), $object_activities);
        $object_activities->each(function ($activity, $key) use ($expected_activities) {
            /* @var Activity $activity */

            $starts_at = Carbon::parse(trim(str_replace(
                ['T', 'Z'],
                [' '],
                $expected_activities[$key]['starts_at']
            )), 'UTC');

            $this->assertTrue($activity instanceof Activity);
            $this->assertSame($expected_activities[$key]['id'], $activity->getExternalId());
            $this->assertSame(
                $expected_activities[$key]['project_id'],
                $activity->getExternalProjectId()
            );
            $this->assertSame(
                $expected_activities[$key]['user_id'],
                $activity->getExternalUserId()
            );
            $this->assertSame(
                $expected_activities[$key]['tracked'],
                $activity->getTrackedSeconds()
            );
            $this->assertSame(
                $expected_activities[$key]['overall'],
                $activity->getActivitySeconds()
            );
            $this->assertSame(
                $starts_at->toDateTimeString(),
                $activity->getUtcStartedAt()->toDateTimeString()
            );
            $this->assertSame(null, $activity->getNote());
            $this->assertSame((clone $starts_at)->addSeconds($expected_activities[$key]['tracked'])
                ->toDateTimeString(), $activity->getUtcFinishedAt()->toDateTimeString());
            $this->assertSame(null, $activity->getTimeTrackingNoteId());
        });
    }

    /**
     * Set hubstaff mock.
     *
     * @param array $data
     * @param $url
     * @param string $additional
     *
     * @return Hubstaff|m\Mock
     */
    protected function setHubstaffClientMock(array $data, $url, $additional = '')
    {
        // set up mocks - we don't want to connect to hubstaff API (no testing API) - we want to
        // return mocked data and make sure everything will be as it should

        $client_mock = m::mock(stdClass::class);

        $headers = [
            'headers' => [
                'App-Token' => $this->settings['app_token'],
                'Auth-Token' => $this->settings['auth_token'],
            ],
        ];

        $data_parts = array_chunk($data[$url], 100);

        $additional .= ($additional ? '&' : '?');

        // if no data, we create just one element to have empty data
        if (! $data_parts) {
            $data_parts[0] = [];
        }

        $response_mock = m::mock('stClass');

        for ($i = 0, $c = count($data_parts); $i < $c; ++$i) {
            $response_mock->shouldReceive('getBody')->once()->withNoArgs()
                ->andReturn(json_encode([$url => $data_parts[$i]]));
            $client_mock->shouldReceive('request')->once()
                ->with(
                    'GET',
                    $this->hubstaff_host . $url . $additional . 'offset=' . $i * 100,
                    $headers
                )->andReturn($response_mock);
        }

        $hubstaff_mock =
            m::mock(Hubstaff::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hubstaff_mock->__construct('abc', $this->settings, $this->info);

        $times = count($data_parts);

        $hubstaff_mock->shouldReceive('httpClient')->times($times)->andReturn($client_mock);

        return $hubstaff_mock;
    }

    /**
     * Set hubstaff mock that won't fetch any data.
     *
     * @return m\Mock
     */
    protected function setNoFetchingHubstaffMock()
    {
        $hubstaff_mock =
            m::mock(Hubstaff::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hubstaff_mock->__construct('abc', $this->settings, $this->info);

        $hubstaff_mock->shouldNotReceive('httpClient');

        return $hubstaff_mock;
    }
}
