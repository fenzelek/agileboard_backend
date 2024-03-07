<?php

namespace Tests\Unit\App\Modules\Interaction\Services\InteractionManager\AddNotifications;

use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Modules\Interaction\Contracts\IInteractionManager;
use App\Modules\Interaction\Models\Dto\NotificationUserPingDTO;
use App\Modules\Interaction\Services\InteractionManager;
use App\Modules\Interaction\Services\NotificationPingExtractor;
use Mockery as m;
use Tests\TestCase;

class InteractionManagerTest extends TestCase
{
    use InteractionManagerTrait;

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with one interaction to user
     *
     * @test
     * @Expectation return single Notification Ping
     */
    public function addNotifications_commentWithOneInteractionToUser()
    {
        //GIVEN
        $interaction_ping = $this->mockInteractionPing();
        $interaction = $this->mockInteraction(collect([$interaction_ping]));

        $notification_ping_extractor = m::mock(NotificationPingExtractor::class);
        $extract_expectation = $notification_ping_extractor->allows('extract')->andReturns(
            collect([new NotificationUserPingDTO($interaction, $interaction_ping)]));

        $interaction_notification_manager = m::mock(IInteractionNotificationManager::class);;
        $notify_expectation = $interaction_notification_manager->allows('notify');

        /** @var  IInteractionManager $interaction_manager */
        $interaction_manager = $this->app->make(IInteractionManager::class, [
            'notification_ping_extractor' => $notification_ping_extractor,
            'interaction_notification_manager' => $interaction_notification_manager,
        ]);

        //WHEN
        $interaction_manager->addNotifications($interaction);

        //THEN
        $extract_expectation->once();
        $notify_expectation->once();

    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with two interaction to user
     *
     * @test
     * @Expectation return two Notification Ping
     */
    public function addNotifications_commentWithTwoInteractionToUser()
    {
        //GIVEN
        $interaction_ping_1 = $this->mockInteractionPing();
        $interaction_ping_2 = $this->mockInteractionPing();
        $interaction_pings = [$interaction_ping_1, $interaction_ping_2];

        $interaction = $this->mockInteraction(collect($interaction_pings));

        $notification_ping_extractor = m::mock(NotificationPingExtractor::class);
        $extract_expectation = $notification_ping_extractor->allows('extract')->andReturns(collect([
                new NotificationUserPingDTO($interaction, $interaction_ping_1),
                new NotificationUserPingDTO($interaction, $interaction_ping_2)
        ]));

        $interaction_notification_manager = m::mock(IInteractionNotificationManager::class);;
        $notify_expectation = $interaction_notification_manager->allows('notify');


        /** @var  IInteractionManager $interaction_manager */
        $interaction_manager = $this->app->make(InteractionManager::class, [
            'notification_ping_extractor' => $notification_ping_extractor,
            'interaction_notification_manager' => $interaction_notification_manager,
        ]);

        //WHEN
        $interaction_manager->addNotifications($interaction);

        //THEN
        $extract_expectation->once();
        $notify_expectation->times(2);
    }
}
