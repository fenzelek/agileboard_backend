<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Models\Dto\NotificationUserPing\GetRecipientId;

use App\Modules\Interaction\Models\Dto\NotificationUserPingDTO;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationUserPingTest extends TestCase
{
    use DatabaseTransactions;
    use NotificationUserPingTrait;

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with one interaction to user
     *
     * @test
     * @Expectation return Notification Ping with valid recipient id
     */
    public function addNotifications_commentWithOneInteractionToUser()
    {
        //GIVEN
        $recipientId = 111;
        $interaction_ping = $this->mockInteractionPing($recipientId);

        $interaction = $this->mockInteraction();

        $notification_ping = new NotificationUserPingDTO($interaction, $interaction_ping);

        //WHEN
        $result = $notification_ping->getRecipientId();

        //THEN
        $this->assertEquals($recipientId, $result);
    }
}
