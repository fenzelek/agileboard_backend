<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\Processor\ActivitiesProcessor;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Activity as ActivityModel;
use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Db\Integration\TimeTracking\Project;
use App\Models\Db\Integration\TimeTracking\User;
use App\Models\Db\Ticket;
use App\Models\Other\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\NoteMatcher;
use App\Modules\Integration\Services\TimeTracking\Processor\ActivitiesProcessor;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use stdClass;
use Tests\BrowserKitTestCase;
use Mockery as m;
use Tests\Helpers\ActivityHelper;

class SaveTest extends BrowserKitTestCase
{
    use ActivityHelper;

    /** @test */
    public function it_doesnt_do_anything_when_activity_already_exists()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $integration = new Integration(['id' => 523]);

        $activity_fields = $this->getActivityFields();

        $activities = collect([
            $this->createActivity($activity_fields),
        ]);

        $builder = m::mock(stdClass::class);

        // 1st record should be updated
        $activity->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder);
        $builder->shouldReceive('where')->once()
            ->with('external_activity_id', $activity_fields['id'])
            ->andReturn($builder);
        $builder->shouldReceive('first')->once()->withNoArgs()->andReturn(3);

        $processor = new ActivitiesProcessor($note_matcher, $activity, $ticket, $note);

        $processor->save($integration, $activities, collect(), collect());
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_when_activity_has_note_set()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $integration = new Integration(['id' => 523]);

        $activity_fields = $this->getActivityFields();

        $activities = collect([
            $this->createActivity($activity_fields, 'sample note'),
        ]);

        $builder = m::mock(stdClass::class);

        // 1st record should be updated
        $activity->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder);
        $builder->shouldReceive('where')->once()
            ->with('external_activity_id', $activity_fields['id'])
            ->andReturn($builder);
        $builder->shouldReceive('first')->once()->withNoArgs()->andReturn(null);

        $processor = new ActivitiesProcessor($note_matcher, $activity, $ticket, $note);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Handling direct notes not implemented yet');

        $processor->save($integration, $activities, collect(), collect());
    }

    /** @test */
    public function it_saves_new_activities()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $integration = new Integration(['id' => 523]);

        $activity_fields = $this->getActivityFields();

        $activities = collect([
            $this->createActivity($activity_fields),
        ]);

        $builder = m::mock(stdClass::class);

        // 1st record should be updated
        $activity->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder);
        $builder->shouldReceive('where')->once()
            ->with('external_activity_id', $activity_fields['id'])
            ->andReturn($builder);
        $builder->shouldReceive('first')->once()->withNoArgs()->andReturn(null);

        $processor = m::mock(ActivitiesProcessor::class)->makePartial();
        $processor->__construct($note_matcher, $activity, $ticket, $note);

        $builder_1 = m::mock(stdClass::class);
        $builder_2 = m::mock(stdClass::class);

        $activity_fields_2 = [
            'id' => 4341231,
            'time_slot' => '2017-08-02 08:10:00',
            'starts_at' => '2017-08-02 08:00:00',
            'user_id' => 612,
            'project_id' => 715,
            'task_id' => 426,
            'keyboard' => 8,
            'mouse' => 12,
            'overall' => 15,
            'tracked' => 30,
            'paid' => false,
        ];

        $time_tracking_note_id = 880123;
        $activities_collection = collect([
            $this->createActivity($activity_fields),
            $this->createActivity($activity_fields_2),
        ]);
        $activities_collection[1]->setTimeTrackingNoteId($time_tracking_note_id);

        $collection = collect([1, 2]);

        // activitiesWithNotes methods
        $note->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_1);
        $builder_1->shouldReceive('where')->once()
            ->with('external_project_id', $activity_fields['project_id'])->andReturn($builder_1);
        $builder_1->shouldReceive('where')->once()
            ->with('external_user_id', $activity_fields['user_id'])->andReturn($builder_1);
        $builder_1->shouldReceive('where')->once()
            ->with('utc_recorded_at', '<', $activity_fields['starts_at'])->andReturn($builder_1);
        $builder_1->shouldReceive('latest')->once()->with('utc_recorded_at')->andReturn($builder_1);
        $builder_1->shouldReceive('first')->once()->withNoArgs()->andReturn(null);

        $note->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_2);
        $builder_2->shouldReceive('where')->once()
            ->with('external_project_id', $activity_fields['project_id'])->andReturn($builder_2);
        $builder_2->shouldReceive('where')->once()
            ->with('external_user_id', $activity_fields['user_id'])->andReturn($builder_2);
        $builder_2->shouldReceive('where')->once()
            ->with('utc_recorded_at', '>=', $activity_fields['starts_at'])->andReturn($builder_2);
        $builder_2->shouldReceive('where')->once()
            ->with('utc_recorded_at', '<=', '2017-08-01 08:10:00')->andReturn($builder_2);
        $builder_2->shouldReceive('oldest')->once()->with('utc_recorded_at')->andReturn($builder_2);
        $builder_2->shouldReceive('get')->once()->withNoArgs()->andReturn($collection);

        $note_matcher->shouldReceive('find')->with(m::on(function ($arg) use ($activity_fields) {
            return $arg instanceof Activity && $arg->getExternalId() == $activity_fields['id'];
        }), null, m::on(function ($arg) use ($collection) {
            return $arg instanceof Collection && $arg->all() === $collection->all();
        }))->andReturn($activities_collection);

        // now verifying create and mocking some properties of 1st model
        $time_tracking_user = new User(['user_id' => 7812]);
        $time_tracking_project = new Project(['project_id' => 79111]);

        $activity_model = m::mock(ActivityModel::class)->makePartial();
        $activity_model->shouldReceive('setAttribute')->once()
            ->with('timeTrackingUser', m::on(function ($arg) {
                return $arg instanceof User && $arg->user_id == 7812;
            }))
            ->andSet('timeTrackingUser', $time_tracking_user)
            ->passthru();
        $activity_model->shouldReceive('setAttribute')->once()
            ->with('timeTrackingProject', m::on(function ($arg) {
                return $arg instanceof Project && $arg->project_id == 79111;
            }))
            ->andSet('timeTrackingProject', $time_tracking_project)
            ->passthru();

        $activity_model->timeTrackingUser = $time_tracking_user;
        $activity_model->timeTrackingProject = $time_tracking_project;

        $activity->shouldReceive('create')->once()->with([
            'integration_id' => 523,
            'external_activity_id' => $activity_fields['id'],
            'time_tracking_user_id' => 2057,
            'time_tracking_project_id' => 1023,
            'time_tracking_note_id' => null,
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'utc_started_at' => Carbon::parse($activity_fields['starts_at'], 'UTC'),
            'utc_finished_at' => Carbon::parse($activity_fields['starts_at'], 'UTC')
                ->addSeconds($activity_fields['tracked']),
            'tracked' => $activity_fields['tracked'],
            'activity' => $activity_fields['overall'],
            'comment' => '',
        ])->andReturn($activity_model);

        $processor->shouldReceive('findTicket')->once()->andReturn(null);

        $activity_model->shouldReceive('fill')->once()->with([
            'user_id' => 7812,
            'project_id' => 79111,
        ]);

        $activity_model->shouldReceive('fill')->once()->with([
            'ticket_id' => null,
        ]);

        $activity_model->shouldReceive('save')->once()->withNoArgs();

        // now verifying create and mocking some properties of 2nd model
        $time_tracking_user_2 = new User(['user_id' => 9123]);
        $time_tracking_project_2 = new Project(['project_id' => 90123]);

        $activity_model_2 = m::mock(ActivityModel::class)->makePartial();
        $activity_model_2->shouldReceive('setAttribute')->once()
            ->with('timeTrackingUser', m::on(function ($arg) {
                return $arg instanceof User && $arg->user_id == 9123;
            }))
            ->andSet('timeTrackingUser', $time_tracking_user_2)
            ->passthru();
        $activity_model_2->shouldReceive('setAttribute')->once()
            ->with('timeTrackingProject', m::on(function ($arg) {
                return $arg instanceof Project && $arg->project_id == 90123;
            }))
            ->andSet('timeTrackingProject', $time_tracking_project_2)
            ->passthru();

        $activity_model_2->timeTrackingUser = $time_tracking_user_2;
        $activity_model_2->timeTrackingProject = $time_tracking_project_2;

        $activity->shouldReceive('create')->once()->with([
            'integration_id' => 523,
            'external_activity_id' => $activity_fields_2['id'],
            'time_tracking_user_id' => 512312,
            'time_tracking_project_id' => 5412435,
            'time_tracking_note_id' => $time_tracking_note_id,
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'utc_started_at' => Carbon::parse($activity_fields_2['starts_at'], 'UTC'),
            'utc_finished_at' => Carbon::parse($activity_fields_2['starts_at'], 'UTC')
                ->addSeconds($activity_fields_2['tracked']),
            'tracked' => $activity_fields_2['tracked'],
            'activity' => $activity_fields_2['overall'],
            'comment' => '',
        ])->andReturn($activity_model_2);

        $sample_ticket = new stdClass();
        $sample_ticket->id = 777;

        $processor->shouldReceive('findTicket')->once()->andReturn($sample_ticket);

        $activity_model_2->shouldReceive('fill')->once()->with([
            'user_id' => 9123,
            'project_id' => 90123,
        ]);

        $activity_model_2->shouldReceive('fill')->once()->with([
            'ticket_id' => 777,
        ]);

        $activity_model_2->shouldReceive('save')->once()->withNoArgs();

        $processor->save(
            $integration,
            $activities,
            collect([
                $activity_fields['user_id'] => 2057,
                $activity_fields_2['user_id'] => 512312,
            ]),
            collect([
                $activity_fields['project_id'] => 1023,
                $activity_fields_2['project_id'] => 5412435,
            ])
        );
        $this->assertTrue(true);
    }
}
