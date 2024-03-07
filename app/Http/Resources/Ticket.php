<?php

namespace App\Http\Resources;

use App\Models\Other\RoleType;

class Ticket extends AbstractResource
{
    // this transformer is required for TicketController@show - probably bug in ApiResponse
    protected $fields = '*';

    /**
     * @inheritdoc
     */
    protected $ignoredRelationships = ['project'];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['deleted_at'] = $this->deleted_at ? $this->deleted_at->toDateTimeString() : null;
        $data['scheduled_time_start'] = $this->scheduled_time_start ? $this->scheduled_time_start->toDateTimeString() : null;
        $data['scheduled_time_end'] = $this->scheduled_time_end ? $this->scheduled_time_end->toDateTimeString() : null;

        if (array_key_exists('time_tracking_summary', $data)) {
            //access
            $access = collect(auth()->user()->getRoles())
                ->intersect([RoleType::OWNER, RoleType::ADMIN, RoleType::DEVELOPER])->isNotEmpty();

            if ($access || $this->project->time_tracking_visible_for_clients) {
                $data['stats']['data'] = [
                    'tracked_summary' => 0,
                    'activity_summary' => 0,
                ];

                foreach ($data['time_tracking_summary']['data'] as $k => $v) {
                    $data['time_tracking_summary']['data'][$k]['tracked_sum'] =
                        (int) $data['time_tracking_summary']['data'][$k]['tracked_sum'];
                    $data['time_tracking_summary']['data'][$k]['activity_sum'] =
                        (int) $data['time_tracking_summary']['data'][$k]['activity_sum'];

                    $data['stats']['data']['tracked_summary'] +=
                        (int) $data['time_tracking_summary']['data'][$k]['tracked_sum'];
                    $data['stats']['data']['activity_summary'] +=
                        (int) $data['time_tracking_summary']['data'][$k]['activity_sum'];
                }

                $data['stats']['data']['activity_level'] =
                    activity_level(
                        $data['stats']['data']['tracked_summary'],
                        $data['stats']['data']['activity_summary']
                    );

                $data['stats']['data']['time_usage'] =
                    activity_level(
                        $this->estimate_time,
                        $data['stats']['data']['tracked_summary']
                    );
            } else {
                unset($data['time_tracking_summary']);
            }
        }

        return $data;
    }
}
