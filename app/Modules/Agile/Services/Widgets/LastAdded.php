<?php

namespace App\Modules\Agile\Services\Widgets;

use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Modules\Agile\Contracts\IWidget;
use Illuminate\Support\Collection;

class LastAdded implements IWidget
{
    /**
     * @param User $user
     *
     * @return mixed
     */

    public function get(User $user): Collection
    {
        return Ticket::inCompany($user)
            ->with('stories', 'type')
            ->where('assigned_id', $user->id)
            ->createdInLastThreeMonths()
            ->orderBy('created_at')
            ->get();
    }

    public function getName(): string
    {
        return __('LastAdded');
    }
}
