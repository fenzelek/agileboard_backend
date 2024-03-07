<?php
declare(strict_types=1);

namespace App\Modules\Interaction\Services;

use App\Models\Other\Interaction\NotifiableGroupType;
use App\Modules\Interaction\Contracts\IUsersGroupMembers;
use Illuminate\Container\Container;

class GetProjectUsersFactory
{
    private Container $app;

    public function __construct(Container $app){

        $this->app = $app;
    }
    public function create(int $recipient_group_type_id): IUsersGroupMembers
    {
        switch ($recipient_group_type_id)
        {
            case NotifiableGroupType::ALL;
                return $this->app->make(GetProjectUsers::class);

            case NotifiableGroupType::INVOLVED;
            //TODO KN case NotifiableGroupType::INVOLVED
        }
    }
}