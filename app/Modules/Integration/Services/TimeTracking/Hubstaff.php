<?php

namespace App\Modules\Integration\Services\TimeTracking;

use App\Models\Other\Integration\TimeTracking\Activity;
use App\Models\Other\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Project;
use App\Models\Other\Integration\TimeTracking\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Hubstaff extends TimeTracking
{
    /**
     * Host that will be used to connect to Hubstaff API.
     *
     * @var string
     */
    protected $host = 'https://api.hubstaff.com/v1/';

    /**
     * Name of key for storing last activity fetch date.
     *
     * @var string
     */
    protected $activity_fetched = 'activity_utc_fetched_until';

    /**
     * Name of key for storing last note fetch date.
     *
     * @var string
     */
    protected $note_fetched = 'note_utc_fetched_until';

    /**
     * @inheritdoc
     */
    public function projects()
    {
        return collect($this->fetchData('projects', 100))->map(function ($project) {
            return new Project($project->id, $project->name);
        });
    }

    /**
     * @inheritdoc
     */
    public function users()
    {
        return collect($this->fetchData('users', 100))->map(function ($user) {
            return new User($user->id, $user->email, $user->name);
        });
    }

    /**
     * @inheritdoc
     */
    public function notes()
    {
        [$start_time, $end_time] = $this->calculateDates($this->note_fetched);

        if ($this->shouldStop($start_time, $end_time)) {
            return collect();
        }

        $this->saveFetchedDate($this->note_fetched, $end_time);

        return collect($this->fetchData(
            'notes?start_time=' .
            $this->toHubstaffFormat($start_time) .
            '&stop_time=' . $this->toHubstaffFormat($end_time),
            100,
            'notes'
        ))->map(function ($note) {
            return new Note(
                $note->id,
                $note->project_id,
                $note->user_id,
                $note->description,
                $this->fromHubstaffFormat($note->recorded_at)
            );
        });
    }

    /**
     * @inheritdoc
     */
    public function activities()
    {
        [$start_time, $end_time] = $this->calculateDates($this->activity_fetched);

        if ($this->shouldStop($start_time, $end_time)) {
            Log::channel('time-tracking')->alert('Should stop', [
                'start_time' => $start_time,
                'end_time' => $end_time,
                'method' => __METHOD__,
            ]);

            return collect();
        }

        // if notes are missing, we should wait until they will be fetched otherwise it would be
        // impossible later to match activities to notes
        if ($this->notesAreMissing($end_time)) {
            Log::channel('time-tracking')->alert('Notes are missing', [
                'end_time' => $end_time,
                'method' => __METHOD__,
            ]);

            return collect();
        }

        $this->saveFetchedDate($this->activity_fetched, $end_time);

        return collect($this->fetchData(
            'activities?start_time=' .
            $this->toHubstaffFormat($start_time) .
            '&stop_time=' . $this->toHubstaffFormat($end_time),
            100,
            'activities'
        ))->map(function ($activity) {
            return new Activity(
                $activity->id,
                $activity->project_id,
                $activity->user_id,
                $activity->tracked,
                $activity->overall,
                $this->fromHubstaffFormat($activity->starts_at)
            );
        });
    }

    /**
     * Verify if notes were already fetched until given date.
     *
     * @param Carbon $end_time
     *
     * @return bool
     */
    protected function notesAreMissing(Carbon $end_time)
    {
        // subSecond() was added because for some reason this condition $note_fetched_utc->lt($fetched_end_date) was true when $note_fetched_utc and $fetched_end_date were equal
        $fetched_end_date = $this->getFetchedDate($end_time)->subSecond();
        $note_fetched_utc = Carbon::parse($this->info[$this->note_fetched], 'UTC');

        Log::channel('time-tracking')->alert('Checking if notes are missing', [
            'note_fetched' => $this->info[$this->note_fetched] ?? null,
            'note_fetched_utc' => $note_fetched_utc ?? null,
            'fetched_end_date' => $fetched_end_date,
            'method' => __METHOD__,
            'first_condition' => ! isset($this->info[$this->note_fetched]),
            'second_condition' => $note_fetched_utc->lt($fetched_end_date),
        ]);

        return ! isset($this->info[$this->note_fetched]) || $note_fetched_utc->lt($fetched_end_date);
    }

    /**
     * Verify whether parsing should be stopped.
     *
     * @param Carbon $start_time
     * @param Carbon $end_time
     *
     * @return bool
     */
    protected function shouldStop(Carbon $start_time, Carbon $end_time)
    {
        // if end time is in future it means we shouldn't start yet, we should wait a while until
        // it won't be in future any more. Same if end time is after start time
        return ($end_time->isFuture() || $end_time->lte($start_time));
    }

    /**
     * Calculate dates that should be used for later for getting data from external API.
     *
     * @param string $field_name
     *
     * @return array
     */
    protected function calculateDates($field_name)
    {
        $last_check_date = $this->getLastFetchedDate($field_name);
        // we assume we always will get notes from some time in past until max 30 minutes from now
        $start_time = $this->calculateStartingTime($last_check_date);
        $end_time = $this->getStartingPeriod((clone $this->now)->subMinutes(30));

        // if it's a bigger period we limit it to 24 hours to not have too much data to process
        // and not to run too many Hubstaff API queries
        if ($end_time->diffInHours($start_time) > 24) {
            $end_time = $this->getStartingPeriod((clone $start_time)->addHours(24));
        }

        return [$start_time, $end_time];
    }

    /**
     * Get last fetch date for given field.
     *
     * @param string $field
     *
     * @return Carbon|null
     */
    protected function getLastFetchedDate($field)
    {
        return $this->info && ! empty($this->info[$field]) ?
            Carbon::parse($this->info[$field]) : null;
    }

    /**
     * Converts time to Hubstaff format.
     *
     * @param Carbon $date
     *
     * @return string
     */
    protected function toHubstaffFormat(Carbon $date)
    {
        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Converts time from Hubstaff format.
     *
     * @param $date
     *
     * @return Carbon
     */
    protected function fromHubstaffFormat($date)
    {
        return Carbon::parse(trim(str_replace(['T', 'Z'], [' '], $date)), 'UTC');
    }

    /**
     * Calculate start time for given last date.
     *
     * @param Carbon|null $last_date
     *
     * @return Carbon
     */
    protected function calculateStartingTime(Carbon $last_date = null)
    {
        // for start time from config we subtract 10 minutes for cleaner implementation
        $start_time = $last_date ? clone($last_date) : $this->startTime()->subMinutes(10);

        // now we make sure that date we have will have set UTC timezone (we don't convert to UTC
        // here - we just assume we get date/times in UTC timezone)
        $start_time = Carbon::parse($start_time->toDateTimeString(), 'UTC');

        // here we calculate real starting time, we start always from beginning of 10 seconds and we
        // start from next 10 minutes, so we add 10 minutes
        $start_time = $this->getStartingPeriod($start_time)->addMinutes(10);

        return $start_time;
    }

    /**
     * Gets starting time for given date. It will ensure we start every time from beginning of
     * 10 minutes period, so 10:00, 10:10, 10:20 and so on.
     *
     * @param Carbon $date
     *
     * @return Carbon
     */
    protected function getStartingPeriod(Carbon $date)
    {
        $date = clone $date;
        $date->minute(intdiv($date->minute, 10) * 10)->second(0);

        return $date;
    }

    /**
     * Get Client that will be used to run requests.
     *
     * @return Client
     */
    protected function httpClient()
    {
        return new Client();
    }

    /**
     * Fetch remote data.
     *
     * @param string $url
     * @param int $limit
     * @param string|null $key
     *
     * @return array
     */
    protected function fetchData($url, $limit, $key = null)
    {
        $key = $key ?: $url;
        $results = [];
        $offset = 0;

        $url .= (str_contains($url, '?') ? '&' : '?');

        do {
            $response = $this->httpClient()->request(
                'GET',
                $this->host . $url . 'offset=' . $offset,
                [
                'headers' => [
                    'App-Token' => optional($this->settings)['app_token'],
                    'Auth-Token' => optional($this->settings)['auth_token'],
                ],
            ]
            );
            $last_results = json_decode((string) $response->getBody());

            if (property_exists($last_results, $key)) {
                $last_results = $last_results->$key;
            }

            $results = array_merge($results, $last_results);
            $offset += $limit;
        } while (count($last_results) == $limit);

        return $results;
    }

    /**
     * Get fetched date.
     *
     * @param Carbon $end_time
     *
     * @return Carbon
     */
    protected function getFetchedDate(Carbon $end_time)
    {
        // we subtract here 2 minutes to be sure we are not at the end of 10 minutes period
        // we could subtract here 3, 4, 5 and so on - we just make sure we are in the middle of
        // 10 minutes period
        return (clone $end_time)->subMinutes(2);
    }

    /**
     * Add date for saving until next run.
     *
     * @param string $field
     * @param Carbon $end_time
     */
    private function saveFetchedDate($field, Carbon $end_time)
    {
        $this->info[$field] = $this->getFetchedDate($end_time)->toDateTimeString();
    }
}
