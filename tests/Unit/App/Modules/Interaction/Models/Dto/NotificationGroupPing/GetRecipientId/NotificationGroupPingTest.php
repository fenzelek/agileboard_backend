<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Models\Dto\NotificationGroupPing\GetRecipientId;

use App\Modules\Interaction\Models\Dto\NotificationGroupPingDTO;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationGroupPingTest extends TestCase
{
    use DatabaseTransactions;
    use NotificationGroupPingTrait;

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with one interaction to user
     *
     * @test
     * @Expectation return valid recipient id
     */
    public function addNotifications_commentWithOneInteractionToUser()
    {
        //GIVEN
        $user = $this->createNewUser();
        $interaction_ping = $this->mockInteractionPing();
        $interaction = $this->mockInteraction();

        $notification_ping = new NotificationGroupPingDTO($interaction, $interaction_ping, $user);

        //WHEN
        $result = $notification_ping->getRecipientId();

        //THEN
        $this->assertEquals($user->id, $result);
    }
}
