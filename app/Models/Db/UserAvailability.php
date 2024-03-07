<?php

namespace App\Models\Db;

use App\Models\Other\UserAvailabilityStatusType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int user_id
 * @property ?string time_start
 * @property ?string time_stop
 * @property string day
 * @property int available
 * @property string description
 * @property int company_id
 * @property string status
 * @property bool overtime
 */
class UserAvailability extends Model
{
    private const OVERTIME = 'overtime';
    /**
     * {inheritdoc}.
     */
    public $timestamps = false;

    /**
     * @inheritdoc
     */
    protected $table = 'user_availability';

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'user_id',
        'day',
        'time_start',
        'time_stop',
        'available',
        'overtime',
        'status',
        'description',
        'company_id',
        'source',
    ];

    /**
     * Availability is assigned to specific user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Availability is assigned to company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * @param $query
     * @param int $companyId
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeByCompanyId($query, int $companyId)
    {
        return $query->where('company_id', '=', $companyId);
    }

    /**
     * @param $query
     * @param int $user_id
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeByUserId($query, int $user_id)
    {
        return $query->where('user_id', '=', $user_id);
    }

    /**
     * @param $query
     * @param string $day
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeForDay($query, string $day)
    {
        return $query->where('day', '=', $day);
    }

    /**
     * @param $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', UserAvailabilityStatusType::CONFIRMED);
    }

    public function isOvertime(){
        return $this->overtime || $this->description === self::OVERTIME;
    }

    /**
     * @param $query
     * @param string $start_time
     * @param string $stop_time
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeOverlap($query, string $start_time, string $stop_time)
    {
        return $query->where(function ($query) use ($start_time, $stop_time) {
            $query->where(function ($query) use ($start_time, $stop_time) {
                $query->whereBetween('time_start', [$start_time, $stop_time])
                    ->orWhereBetween('time_stop', [$start_time, $stop_time]);
            })->orWhere(function ($query) use ($start_time, $stop_time) {
                $query->where('time_start', '<', $start_time)->where('time_stop', '>', $stop_time);
            });
        });
    }

    public function scopeWhereDayIn(Builder $builder, array $days){
        $builder->whereIn('day', $days);
    }

    /**
     * @param  \Carbon\Carbon  $startDate
     * @param  Carbon  $endDate
     * @param $companyId
     * @return \Closure
     */
    public function scopeInPeriodDate(Builder $q, Carbon $startDate, Carbon $endDate)
    {
        $q->where('day', '>=', $startDate->format('Y-m-d'))
            ->where('day', '<=', $endDate->format('Y-m-d'));
    }
}
