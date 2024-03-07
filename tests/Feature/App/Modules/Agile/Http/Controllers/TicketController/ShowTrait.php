<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use App\Models\Db\Involved;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Illuminate\Support\Collection;

trait ShowTrait
{
    private function createInvolved($params = []): Involved
    {
        return factory(Involved::class)->create($params);
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    private function createNewProject(Company $company): Project
    {
        $project = factory(Project::class)->make();
        $project->company()->associate($company);
        $project->save();

        return $project;
    }

    /**
     * @param $project_id
     * @param $ticket_id
     * @param $company_id
     *
     * @return string
     */
    private function prepareUrl($project_id, $ticket_id, $company_id)
    {
        return "/projects/{$project_id}/tickets/{$ticket_id}?selected_company_id={$company_id}";
    }

    /**
     * @param $role_type
     */
    private function verifyTimeTrackingEntriesForRole($role_type)
    {
        $this->setProjectRole($this->project, $role_type);

        $ticket = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
        ]);

        $users = factory(User::class, 5)->create();
        $tracking_users = factory(TimeTrackingUser::class, 5)->create(['user_id' => null]);
        $tracking_activities =
            $this->createTimeTrackingActivities($tracking_users, $users, $ticket);
        $tt_tracking_activities = $this->createTimeTrackingActivity($users[0], $ticket);
        $url = "/projects/{$this->project->id}/tickets/{$ticket->id}/"
            . "?selected_company_id={$this->company->id}";

        $this->get($url)->seeStatusCode(200);

        $response_ticket = $this->decodeResponseJson()['data'];
        $expected_time_tracking_data = $this->getExpectedTrackingData(
            $tracking_activities,
            $tracking_users,
            $ticket,
            $tt_tracking_activities
        );

        $this->assertSame($ticket->id, $response_ticket['id']);

        if ($role_type == RoleType::CLIENT) {
            $this->assertTrue(!isset($response_ticket['time_tracking_summary']['data']));
            $this->assertTrue(!isset($response_ticket['stats']['data']));
        } else {
            $this->assertEquals(
                $expected_time_tracking_data,
                $response_ticket['time_tracking_summary']['data']
            );

            $tracked_summary = $expected_time_tracking_data[0]['tracked_sum']
                + $expected_time_tracking_data[1]['tracked_sum']
                + $expected_time_tracking_data[2]['tracked_sum'];
            $activity_summary = $expected_time_tracking_data[0]['activity_sum']
                + $expected_time_tracking_data[1]['activity_sum']
                + $expected_time_tracking_data[2]['activity_sum'];

            $activity_level = round($activity_summary / $tracked_summary * 100, 2);
            $time_usage = round($tracked_summary / $ticket->estimate_time * 100, 2);

            $this->assertSame([
                'tracked_summary' => $tracked_summary,
                'activity_summary' => $activity_summary,
                'activity_level' => $activity_level != floor($activity_level)
                    ? $activity_level
                    : (int)$activity_level,
                'time_usage' => $time_usage != floor($time_usage) ? $time_usage : (int)$time_usage,
            ], $response_ticket['stats']['data']);
        }
    }

    /**
     * @param $tracking_activities
     * @param $tracking_users
     * @param $ticket
     *
     * @return array
     */
    private function getExpectedTrackingData($tracking_activities, $tracking_users, $ticket, $tt_tracking_activities): array
    {
        return [
            [
                'time_tracking_user_id' => $tracking_activities[4]->time_tracking_user_id,
                'tracked_sum' => $tracking_activities[4]->tracked,
                'activity_sum' => $tracking_activities[4]->activity,
                'activity_level' => $this->calculateActivityLevel(
                    $tracking_activities[4]->tracked,
                    $tracking_activities[4]->activity
                ),
                'user_id' => $tracking_activities[4]->user_id,
                'user' => [
                    'data' => $this->getExpectedUserResponse($this->user),
                ],
                'time_tracking_user' => [
                    'data' => $this->getExpectedTimeTrackingUserResponse($tracking_users[3]),
                ],
                'ticket_id' => $ticket->id,
            ],
            [
                'time_tracking_user_id' => $tracking_activities[0]->time_tracking_user_id,
                'tracked_sum' => $tracking_activities[0]->tracked +
                    $tracking_activities[1]->tracked + $tt_tracking_activities->tracked,
                'activity_sum' => $tracking_activities[0]->activity +
                    $tracking_activities[1]->activity + $tt_tracking_activities->activity,
                'activity_level' => $this->calculateActivityLevel(
                    $tracking_activities[0]->tracked + $tracking_activities[1]->tracked +
                    $tt_tracking_activities->tracked,
                    $tracking_activities[0]->activity + $tracking_activities[1]->activity +
                    $tt_tracking_activities->activity
                ),
                'user_id' => $tracking_activities[0]->user_id,
                'user' => [
                    'data' => $this->getExpectedUserResponse(
                        User::find($tracking_activities[0]->user_id)
                    ),
                ],
                'time_tracking_user' => [
                    'data' => $this->getExpectedTimeTrackingUserResponse($tracking_users[0]),
                ],
                'ticket_id' => $ticket->id,
            ],
            [
                'time_tracking_user_id' => $tracking_activities[3]->time_tracking_user_id,
                'tracked_sum' => $tracking_activities[3]->tracked,
                'activity_sum' => $tracking_activities[3]->activity,
                'activity_level' => $this->calculateActivityLevel(
                    $tracking_activities[3]->tracked,
                    $tracking_activities[3]->activity
                ),
                'user_id' => null,
                'user' => [
                    'data' => null,
                ],
                'time_tracking_user' => [
                    'data' => $this->getExpectedTimeTrackingUserResponse($tracking_users[2]),
                ],
                'ticket_id' => $ticket->id,
            ],
        ];
    }

    /**
     * @param Collection $tracking_users
     * @param Collection $users
     * @param $ticket
     *
     * @return mixed
     */
    private function createTimeTrackingActivities(
        Collection $tracking_users,
        Collection $users,
        $ticket
    ) {
        $tracking_activities = factory(Activity::class, 10)->create([
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'time_tracking_user_id' => $tracking_users[4]->id + 1000,
            // non-existing time tracking user set here
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
        ]);

        $tracking_activities[0]->update([
            'time_tracking_user_id' => $tracking_users[0]->id,
            'user_id' => $users[0]->id,
            'ticket_id' => $ticket->id,
            'project_id' => $ticket->project->id,
            'tracked' => 300,
        ]);

        $tracking_activities[1]->update([
            'time_tracking_user_id' => $tracking_users[0]->id,
            'user_id' => $users[0]->id,
            'ticket_id' => $ticket->id,
            'project_id' => $ticket->project->id,
            'tracked' => 200,
        ]);

        $tracking_activities[3]->update([
            'time_tracking_user_id' => $tracking_users[2]->id,
            'user_id' => null,
            'ticket_id' => $ticket->id,
            'project_id' => $ticket->project->id,
            'tracked' => 100,
        ]);

        $tracking_activities[4]->update([
            'time_tracking_user_id' => $tracking_users[3]->id,
            'user_id' => $this->user->id,
            'ticket_id' => $ticket->id,
            'project_id' => $ticket->project->id,
            'tracked' => 5000,
            'activity' => 2000,
        ]);

        return $tracking_activities;
    }

    private function createTimeTrackingActivity(User $user, $ticket)
    {
        $tracking_activity = factory(Activity::class)->create([
            'user_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'time_tracking_user_id' => null,
            // non-existing time tracking user set here
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
        ]);

        $tracking_activity->update([
            'time_tracking_user_id' => null,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'project_id' => $ticket->project->id,
            'tracked' => 300,
        ]);

        return $tracking_activity;
    }
}