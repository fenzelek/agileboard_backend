<?php

namespace App\Modules\Agile\Services\Widgets;

use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Modules\Agile\Contracts\IWidget;
use Illuminate\Support\Collection;

class YourTasks implements IWidget
{
    /**
     * @param User $user
     *
     * @return mixed
     */
    public function get(User $user): Collection
    {
        $data = Ticket::inCompany($user)
            ->with('stories', 'type')
            ->notDone()
            ->activeSprint()
            ->where('assigned_id', $user->id)
            ->orderBy('tickets.id')
            ->get();

        return $data;
    }

    public function getName(): string
    {
        return __('YourTasks');
    }
}
