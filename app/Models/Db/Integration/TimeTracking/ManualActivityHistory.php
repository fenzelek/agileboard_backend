<?php

namespace App\Models\Db\Integration\TimeTracking;

use App\Models\Db\Model;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User as ModelUser;

class ManualActivityHistory extends Model
{
    protected $table = 'time_tracking_manual_activity_history';

    protected $casts = [
        'from' => 'datetime',
        'to' => 'datetime',
    ];

    /**
     * Time tracking activity belongs to single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ownerUser()
    {
        return $this->belongsTo(ModelUser::class, 'user_id');
    }

    /**
     * Time tracking activity belongs to single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function authorUser()
    {
        return $this->belongsTo(ModelUser::class, 'author_id');
    }

    /**
     * Time tracking activity belongs to single project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Project
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Time tracking activity belongs to single ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
