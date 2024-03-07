<?php

namespace App\Http\Resources;

use App\Models\Other\RoleType;

class SprintWithDetails extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = '*';

    /**
     * @inheritdoc
     */
    protected $ignoredRelationships = [
        'timeTrackingGeneralSummary',
        'ticketsGeneralSummary',
        'project',
    ];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['planned_activation'] = $this->planned_activation ? $this->planned_activation->toDateTimeString() : null;
        $data['planned_closing'] = $this->planned_closing ? $this->planned_closing->toDateTimeString() : null;
        $data['activated_at'] = $this->activated_at ? $this->activated_at->toDateTimeString() : null;
        $data['closed_at'] = $this->closed_at ? $this->closed_at->toDateTimeString() : null;

        if (! in_array($request->input('stats'), ['min', 'all'])) {
            return $data;
        }

        $time_tracking_summary = $this->timeTrackingGeneralSummary->first();
        $tickets_summary = $this->ticketsGeneralSummary->first();

        $stats = isset($data['stats']) ? $data['stats'] : null;

        $data['stats'] = [
            'data' => [
                'tickets_count' => $tickets_summary ? $tickets_summary->tickets_count : 0,
                'tickets_estimate_time' => (int) ($tickets_summary ? $tickets_summary->tickets_estimate_time : 0),
            ],
        ];

        //access
        $access = collect($request->user()->getRoles())
            ->intersect([RoleType::OWNER, RoleType::ADMIN, RoleType::DEVELOPER])->isNotEmpty();

        if ($access || $this->project->time_tracking_visible_for_clients) {
            $data['stats']['data']['tracked_summary'] =
                (int) ($time_tracking_summary ? $time_tracking_summary->tracked_sum : 0);
            $data['stats']['data']['activity_summary'] =
                (int) ($time_tracking_summary ? $time_tracking_summary->activity_sum : 0);

            $data['stats']['data']['activity_level'] =
                activity_level(
                    $data['stats']['data']['tracked_summary'],
                    $data['stats']['data']['activity_summary']
                );

            $data['stats']['data']['time_usage'] =
                activity_level(
                    $data['stats']['data']['tickets_estimate_time'],
                    $data['stats']['data']['tracked_summary']
                );

            if ($stats) {
                $data['stats']['data']['un_started_estimations'] = (int) $stats['un_started_estimations'];
                $data['stats']['data']['expected_final'] = (int) $stats['expected_final'];
                $data['stats']['data']['estimation_left'] =
                    (int) $data['stats']['data']['tickets_estimate_time'] - $data['stats']['data']['tracked_summary'];
                $data['stats']['data']['attitude_to_initial'] =
                    activity_level($data['stats']['data']['tickets_estimate_time'], $stats['expected_final']);
            }
        }

        $data['locked'] = $this->locked;

        return $data;
    }
}
