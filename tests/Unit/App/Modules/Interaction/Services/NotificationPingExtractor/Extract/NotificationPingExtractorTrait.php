<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Services\NotificationPingExtractor\Extract;

use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;
use App\Modules\Interaction\Contracts\IUsersGroupMembers;
use App\Modules\Interaction\Services\GetProjectUsers;
use App\Modules\Interaction\Services\GetProjectUsersFactory;
use Illuminate\Support\Collection;
use Mockery as m;

trait NotificationPingExtractorTrait
{
    private function mockInteractionPing(int $recipient_id, string $notifiable_type): IInteractionPing
    {
        $interaction_ping = m::mock(IInteractionPing::class);
        $interaction_ping->allows('getNotifiable')->andReturns($notifiable_type);
        $interaction_ping->allows('getRecipientId')->andReturns($recipient_id);

        return $interaction_ping;
    }

    private function mockInteraction(Collection $interaction_pings, int $author_id, int $get_author_id_times): IInteractionDTO
    {
        $interaction = m::mock(IInteractionDTO::class);
        $interaction->allows('getAuthorId')->times($get_author_id_times)->andReturns($author_id);
        $interaction->allows('getInteractionPings')->times(1)->andReturns($interaction_pings);

        return $interaction;
    }

    private function mockGetProjectUsers(Collection $group_members, int $get_times = 1): GetProjectUsers
    {
        $get_project_users = m::mock(GetProjectUsers::class);
        $get_project_users->allows('get')->times($get_times)->andReturns($group_members);

        return $get_project_users;
    }

    private function mockGetProjectUsersFactory(IUsersGroupMembers $users_group_members, int $create_times = 1): GetProjectUsersFactory
    {
        $get_project_users_factory = m::mock(GetProjectUsersFactory::class);
        $get_project_users_factory->allows('create')->times($create_times)->andReturns($users_group_members);

        return $get_project_users_factory;
    }
}
