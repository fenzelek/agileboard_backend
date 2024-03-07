<?php

namespace Tests\Feature\App\Modules\Gantt\Http\Controllers;

use Tests\Helpers\ProjectHelper;
use App\Helpers\ErrorCode;
use App\Models\Db\Project;
use App\Models\Db\User;
use App\Models\Db\Role;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkloadControllerTest extends TestCase
{
    use DatabaseTransactions, ProjectHelper;

    protected $start_date;
    protected $end_date;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->setRange();
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_validation_error()
    {
        $data = $this->prepareDataStory1();

        $response = $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id);

        $this->verifyResponseValidation($response, ['from']);
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_no_permissions_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::CLIENT);
        $data['project'][0] = factory(Project::class)->create(['company_id' => $company->id]);

        $now = Carbon::create();
        Carbon::setTestNow($now);

        $response = $this->get('/workload' . '/?selected_company_id=' . $company->id . '&from=' . $now);

        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_1()
    {
        $data = $this->prepareDataStory1();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(4)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(8)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(6)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(18)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][2]->id,
                            'name' => $data['project'][2]->name,
                            'color' => $data['project'][2]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][2]->id,
                            'name' => $data['sprints'][2]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][1]->id,
                    'email' => $data['user'][1]->email,
                    'first_name' => $data['user'][1]->first_name,
                    'last_name' => $data['user'][1]->last_name,
                    'avatar' => $data['user'][1]->avatar ? $data['user'][1]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(5)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(9)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][1]->id,
                            'name' => $data['project'][1]->name,
                            'color' => $data['project'][1]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][1]->id,
                            'name' => $data['sprints'][1]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(20)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(25)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][3]->id,
                            'name' => $data['project'][3]->name,
                            'color' => $data['project'][3]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][3]->id,
                            'name' => $data['sprints'][3]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][2]->id,
                    'email' => $data['user'][2]->email,
                    'first_name' => $data['user'][2]->first_name,
                    'last_name' => $data['user'][2]->last_name,
                    'avatar' => $data['user'][2]->avatar ? $data['user'][2]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(6)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(18)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][2]->id,
                            'name' => $data['project'][2]->name,
                            'color' => $data['project'][2]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][2]->id,
                            'name' => $data['sprints'][2]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(1)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(9)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][4]->id,
                            'name' => $data['project'][4]->name,
                            'color' => $data['project'][4]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][4]->id,
                            'name' => $data['sprints'][4]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][3]->id,
                    'email' => $data['user'][3]->email,
                    'first_name' => $data['user'][3]->first_name,
                    'last_name' => $data['user'][3]->last_name,
                    'avatar' => $data['user'][3]->avatar ? $data['user'][3]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(20)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(25)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][3]->id,
                            'name' => $data['project'][3]->name,
                            'color' => $data['project'][3]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][3]->id,
                            'name' => $data['sprints'][3]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->addWeekDays(8)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(9)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][5]->id,
                            'name' => $data['project'][5]->name,
                            'color' => $data['project'][5]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][5]->id,
                            'name' => $data['sprints'][5]->name,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_2()
    {
        $data = $this->prepareDataStory2();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'data' => [
                    [
                        'user' => [
                            'id' => $data['user'][0]->id,
                            'email' => $data['user'][0]->email,
                            'first_name' => $data['user'][0]->first_name,
                            'last_name' => $data['user'][0]->last_name,
                            'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                        ],
                        'workloads' => [
                            [
                                'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                                'end_at' => (clone($data['now']))->addWeekDays(12)->format('Y-m-d H:i:s'),
                                'rate' => 100,
                                'project' => [
                                    'id' => $data['project'][0]->id,
                                    'name' => $data['project'][0]->name,
                                    'color' => $data['project'][0]->color,
                                ],
                                'sprint' => [
                                    'id' => $data['sprints'][0]->id,
                                    'name' => $data['sprints'][0]->name,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_3()
    {
        $data = $this->prepareDataStory3();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'data' => [
                    [
                        'user' => [
                            'id' => $data['user'][0]->id,
                            'email' => $data['user'][0]->email,
                            'first_name' => $data['user'][0]->first_name,
                            'last_name' => $data['user'][0]->last_name,
                            'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                        ],
                        'workloads' => [
                            [
                                'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                                'end_at' => (clone($data['now']))->addWeekDays(10)->format('Y-m-d H:i:s'),
                                'rate' => 100,
                                'project' => [
                                    'id' => $data['project'][0]->id,
                                    'name' => $data['project'][0]->name,
                                    'color' => $data['project'][0]->color,
                                ],
                                'sprint' => [
                                    'id' => $data['sprints'][0]->id,
                                    'name' => $data['sprints'][0]->name,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_4()
    {
        $data = $this->prepareDataStory4();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(10)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing($this->missingUser($data['user'][1]))
            ->assertJsonMissing($this->missingUser($data['user'][2]))
            ->assertJsonMissing($this->missingUser($data['user'][3]));
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_5()
    {
        $data = $this->prepareDataStory5();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(10)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][1]->id,
                    'email' => $data['user'][1]->email,
                    'first_name' => $data['user'][1]->first_name,
                    'last_name' => $data['user'][1]->last_name,
                    'avatar' => $data['user'][1]->avatar ? $data['user'][1]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(10)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing($this->missingUser($data['user'][2]))
            ->assertJsonMissing($this->missingUser($data['user'][3]));
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_6()
    {
        $data = $this->prepareDataStory6();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->startOfWeek()->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(4)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing($this->missingUser($data['user'][1]))
            ->assertJsonMissing($this->missingUser($data['user'][2]))
            ->assertJsonMissing($this->missingUser($data['user'][3]));
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_7()
    {
        $data = $this->prepareDataStory7();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][1]->avatar : '',
                ],
                'workloads' => [],
            ]);
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_8()
    {
        $data = $this->prepareDataStory8();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->startOfWeek()->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(10)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing($this->missingUser($data['user'][1]))
            ->assertJsonMissing($this->missingUser($data['user'][2]))
            ->assertJsonMissing($this->missingUser($data['user'][3]));
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_9()
    {
        $data = $this->prepareDataStory9();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->startOfWeek()->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addWeekDays(6)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing($this->missingUser($data['user'][1]))
            ->assertJsonMissing($this->missingUser($data['user'][2]))
            ->assertJsonMissing($this->missingUser($data['user'][3]));
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_10()
    {
        $data = $this->prepareDataStory10();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addDays(4)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addDays(14)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][1]->id,
                            'name' => $data['sprints'][1]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->addDays(20)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addDays(25)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][2]->id,
                            'name' => $data['sprints'][2]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][1]->id,
                    'email' => $data['user'][1]->email,
                    'first_name' => $data['user'][1]->first_name,
                    'last_name' => $data['user'][1]->last_name,
                    'avatar' => $data['user'][1]->avatar ? $data['user'][1]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addDays(4)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addDays(14)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][1]->id,
                            'name' => $data['sprints'][1]->name,
                        ],
                    ],
                    [
                        'start_at' => (clone($data['now']))->addDays(20)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addDays(25)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][2]->id,
                            'name' => $data['sprints'][2]->name,
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing($this->missingUser($data['user'][2]))
            ->assertJsonMissing($this->missingUser($data['user'][3]));
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_11()
    {
        $data = $this->prepareDataStory11();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [],
            ])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][1]->id,
                    'email' => $data['user'][1]->email,
                    'first_name' => $data['user'][1]->first_name,
                    'last_name' => $data['user'][1]->last_name,
                    'avatar' => $data['user'][1]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [],
            ]);
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_12()
    {
        $data = $this->prepareDataStory12();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [],
            ])
            ->assertJsonMissing($this->missingUser($data['user'][1]));
    }

    /**
     * @group feature_modules_gantt_workload_controller
     * @test */
    public function index_success_story_13()
    {
        $data = $this->prepareDataStory13();

        $this->get('/workload' . '/?selected_company_id=' . $data['company'][0]->id . '&from=' . $data['now'])
            ->assertStatus(200)
            ->assertJsonFragment(['date_start' => $this->start_date])
            ->assertJsonFragment(['date_end' => $this->end_date])
            ->assertJsonFragment([
                'user' => [
                    'id' => $data['user'][0]->id,
                    'email' => $data['user'][0]->email,
                    'first_name' => $data['user'][0]->first_name,
                    'last_name' => $data['user'][0]->last_name,
                    'avatar' => $data['user'][0]->avatar ? $data['user'][0]->avatar : '',
                ],
                'workloads' => [
                    [
                        'start_at' => (clone($data['now']))->addDays(3)->format('Y-m-d H:i:s'),
                        'end_at' => (clone($data['now']))->addDays(4)->format('Y-m-d H:i:s'),
                        'rate' => 100,
                        'project' => [
                            'id' => $data['project'][0]->id,
                            'name' => $data['project'][0]->name,
                            'color' => $data['project'][0]->color,
                        ],
                        'sprint' => [
                            'id' => $data['sprints'][0]->id,
                            'name' => $data['sprints'][0]->name,
                        ],
                    ],
                ],
            ]);
    }

    protected function prepareDataStory1($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][2], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][3], RoleType::DEVELOPER);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');
        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-blue-800-bg');
        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-yellow-800-bg');
        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-green-800-bg');
        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-purple-800-bg');
        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-pink-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][2]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['project'][1]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][3]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['project'][2]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][4]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['project'][3]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][5]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            null,
            with(clone ($data['now']))->addWeekDays(4),
            with(clone ($data['now']))->addWeekDays(8),
            Sprint::INACTIVE
        );

        $data['sprints'] [] = $this->createSprint(
            $data['project'][1]->id,
            null,
            with(clone ($data['now']))->addWeekDays(5),
            with(clone ($data['now']))->addWeekDays(9),
            Sprint::INACTIVE
        );

        $data['sprints'] [] = $this->createSprint(
            $data['project'][2]->id,
            null,
            with(clone ($data['now']))->addWeekDays(6),
            with(clone ($data['now']))->addWeekDays(18),
            Sprint::INACTIVE
        );

        $data['sprints'] [] = $this->createSprint(
            $data['project'][3]->id,
            null,
            with(clone ($data['now']))->addWeekDays(20),
            with(clone ($data['now']))->addWeekDays(25),
            Sprint::INACTIVE
        );

        $data['sprints'] [] = $this->createSprint(
            $data['project'][4]->id,
            null,
            with(clone ($data['now']))->addWeekDays(1),
            with(clone ($data['now']))->addWeekDays(9),
            Sprint::INACTIVE
        );

        $data['sprints'] [] = $this->createSprint(
            $data['project'][5]->id,
            null,
            with(clone ($data['now']))->addWeekDays(8),
            with(clone ($data['now']))->addWeekDays(9),
            Sprint::INACTIVE
        );

        return $data;
    }

    protected function prepareDataStory2($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);
        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now'])),
            null,
            with(clone ($data['now']))->addWeekDays(12),
            Sprint::INACTIVE
        );

        return $data;
    }

    protected function prepareDataStory3($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);
        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now']))->addWeekDays(-11),
            null,
            with(clone ($data['now']))->addWeekDays(10),
            Sprint::INACTIVE
        );

        return $data;
    }

    protected function prepareDataStory4($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::ADMIN);
        $this->setCompanyRole($data['company'][0], $data['user'][2], RoleType::ADMIN);
        $this->setCompanyRole($data['company'][0], $data['user'][3], RoleType::ADMIN);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::SYSTEM_ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::CLIENT)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now'])),
            null,
            null,
            Sprint::INACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [10,15,19,30,2]);

        return $data;
    }

    protected function prepareDataStory5($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][2], RoleType::ADMIN);
        $this->setCompanyRole($data['company'][0], $data['user'][3], RoleType::ADMIN);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::SYSTEM_ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::CLIENT)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now'])),
            null,
            null,
            Sprint::INACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [10,15,19,30,2]);

        return $data;
    }

    protected function prepareDataStory6($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::ADMIN);
        $this->setCompanyRole($data['company'][0], $data['user'][2], RoleType::CLIENT);
        $this->setCompanyRole($data['company'][0], $data['user'][3], RoleType::SYSTEM_ADMIN);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::SYSTEM_ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::CLIENT)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now']))->addWeekDays(-6),
            null,
            null,
            Sprint::INACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [10,15,19,30,2]);

        return $data;
    }

    protected function prepareDataStory7($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            null,
            null,
            null,
            Sprint::INACTIVE
        );

        return $data;
    }

    protected function prepareDataStory8($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::ADMIN);
        $this->setCompanyRole($data['company'][0], $data['user'][2], RoleType::CLIENT);
        $this->setCompanyRole($data['company'][0], $data['user'][3], RoleType::SYSTEM_ADMIN);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::SYSTEM_ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::CLIENT)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            null,
            with(clone ($data['now'])),
            null,
            Sprint::ACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [10,15,19,30,2]);

        return $data;
    }

    protected function prepareDataStory9($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::ADMIN);
        $this->setCompanyRole($data['company'][0], $data['user'][2], RoleType::CLIENT);
        $this->setCompanyRole($data['company'][0], $data['user'][3], RoleType::SYSTEM_ADMIN);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::SYSTEM_ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::CLIENT)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            null,
            with(clone ($data['now']))->addDays(-6),
            null,
            Sprint::ACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [10,15,19,30,2]);

        return $data;
    }

    protected function prepareDataStory10($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();
        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][2], RoleType::CLIENT);
        $this->setCompanyRole($data['company'][0], $data['user'][3], RoleType::SYSTEM_ADMIN);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][2], ['role_id' => Role::findByName(RoleType::SYSTEM_ADMIN)->id]);
        $data['project'][0]->users()->attach($data['user'][3], ['role_id' => Role::findByName(RoleType::CLIENT)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now']))->addDays(-6),
            null,
            with(clone ($data['now']))->addDays(4),
            Sprint::INACTIVE
        );

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            null,
            with(clone ($data['now'])),
            null,
            Sprint::ACTIVE
        );

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now']))->addDays(20),
            null,
            with(clone ($data['now']))->addDays(25),
            Sprint::INACTIVE
        );

        $this->createTickets($data['sprints'][1]->id, $data['project'][0]->id, 5, [10,15,19,30,2]);

        return $data;
    }

    protected function prepareDataStory11($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::DEVELOPER);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            null,
            null,
            with(clone ($data['now']))->addDays(3),
            Sprint::ACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [0,0,0,0,0]);

        return $data;
    }

    protected function prepareDataStory12($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);
        $this->setCompanyRole($data['company'][0], $data['user'][1], RoleType::CLIENT);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $data['project'][0]->users()->attach($data['user'][1], ['role_id' => Role::findByName(RoleType::CLIENT)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now']))->addDays(3),
            null,
            null,
            Sprint::ACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [0,0,0,0,0]);

        return $data;
    }

    protected function prepareDataStory13($role = RoleType::ADMIN)
    {
        $data = $this->prepareBase($role);

        $data['user'] [] = factory(User::class)->create();

        $this->setCompanyRole($data['company'][0], $data['user'][0], RoleType::DEVELOPER);

        $data['project'] [] = $this->createProject($data['company'][0]->id, 'md-red-800-bg');

        $data['project'][0]->users()->attach($data['user'][0], ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $data['sprints'] [] = $this->createSprint(
            $data['project'][0]->id,
            with(clone ($data['now']))->addDays(3),
            null,
            null,
            Sprint::ACTIVE
        );

        $this->createTickets($data['sprints'][0]->id, $data['project'][0]->id, 5, [1,0,2,0,0]);

        return $data;
    }

    protected function prepareBase($role = RoleType::ADMIN)
    {
        $now = Carbon::parse('2018-07-02');
        Carbon::setTestNow($now);

        $data['user'] [] = $this->createUser()->user;
        $data['company'][] = $this->createCompanyWithRole($role);
        auth()->loginUsingId($this->user->id);

        $data['now'] = clone ($now);

        return $data;
    }

    protected function createProject($company_id, $color)
    {
        return factory(Project::class)->create([
            'company_id' => $company_id,
            'color' => $color,
            'time_tracking_visible_for_clients' => 0,
        ]);
    }

    protected function createSprint($project_id, $start_date, $activation_date, $close_date, $status)
    {
        return factory(Sprint::class)->create([
            'project_id' => $project_id,
            'priority' => 1,
            'name' => 'test 1',
            'status' => $status,
            'planned_activation' => $start_date,
            'activated_at' => $activation_date,
            'planned_closing' => $close_date,
        ]);
    }

    protected function setRange()
    {
        $now = Carbon::parse('2018-07-02');
        $this->start_date = with(clone ($now))->startOfWeek()->format('Y-m-d');
        $this->end_date = with(clone ($now))->endOfWeek()->addWeeks(5)->format('Y-m-d');
    }

    protected function calcEstimation(int $hours)
    {
        return 60 * 60 * $hours;
    }

    /**
     * @param $sprint_id
     * @param $project_id
     * @param $count
     * @param array $estimations
     */
    protected function createTickets($sprint_id, $project_id, $count, array $estimations)
    {
        for ($i = 0; $i < $count; $i++) {
            factory(Ticket::class)->create([
                'sprint_id' => $sprint_id,
                'estimate_time' => $this->calcEstimation($estimations[$i]),
                'project_id' => $project_id,
            ]);
        }
    }

    /**
     * @param $user
     * @return array
     */
    protected function missingUser($user)
    {
        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar' => $user->avatar ?: '',
            ],
        ];
    }
}
