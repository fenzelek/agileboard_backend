<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Services\NotificationPingExtractor\Extract;

use App\Interfaces\Interactions\INotificationPingDTO;
use App\Models\Other\Interaction\NotifiableGroupType;
use App\Models\Other\Interaction\NotifiableType;
use App\Modules\Interaction\Models\Dto\NotificationUserPingDTO;
use App\Modules\Interaction\Services\NotificationPingExtractor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class NotificationPingExtractorTest extends TestCase
{
    use NotificationPingExtractorTrait;
    use DatabaseTransactions;

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with one interaction to user
     *
     * @test
     * @Expectation return single Notification Ping
     */
    public function extract_commentWithOneInteractionToUser()
    {
        //GIVEN
        $interaction_ping = $this->mockInteractionPing(22, NotifiableType::USER);

        $interaction = $this->mockInteraction(collect([$interaction_ping]), 11, 1);

        /** @var NotificationPingExtractor $notification_ping_extractor */
        $notification_ping_extractor = $this->app->make(NotificationPingExtractor::class, [
            'interaction' => $interaction,
        ]);

        //WHEN
        $result = $notification_ping_extractor->extract($interaction);

        //THEN
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(NotificationUserPingDTO::class, $result->first());

        /** @var NotificationUserPingDTO $notification_ping */
        $notification_ping = $result->first();
        $this->assertEquals(11, $notification_ping->getAuthorId());
        $this->assertEquals(22, $notification_ping->getRecipientId());
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with two interaction to user
     *
     * @test
     * @Expectation return valid two Notification Ping
     */
    public function extract_commentWithTwoInteractionToUser()
    {
        //GIVEN
        $interaction_ping_1 = $this->mockInteractionPing(22, NotifiableType::USER);
        $interaction_ping_2 = $this->mockInteractionPing(33, NotifiableType::USER);

        $interaction = $this->mockInteraction(collect([$interaction_ping_1, $interaction_ping_2]), 11, 2);

        /** @var NotificationPingExtractor $notification_ping_extractor */
        $notification_ping_extractor = $this->app->make(NotificationPingExtractor::class, [
            'interaction' => $interaction,
        ]);

        //WHEN
        $result = $notification_ping_extractor->extract($interaction);

        //THEN
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertInstanceOf(NotificationUserPingDTO::class, $result->first());

        /** @var NotificationUserPingDTO $notification_ping */
        $notification_ping = $result->first();
        $this->assertEquals(11, $notification_ping->getAuthorId());
        $this->assertEquals(22, $notification_ping->getRecipientId());

        /** @var NotificationUserPingDTO $notification_ping */
        $notification_ping = $result->last();
        $this->assertEquals(11, $notification_ping->getAuthorId());
        $this->assertEquals(33, $notification_ping->getRecipientId());
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with one interaction to group all in project
     *
     * @test
     * @Expectation return valid two Notification Ping
     */
    public function extract_commentWithOneInteractionToGroupAllInProject()
    {
        //GIVEN
        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();
        $author_id = 11;

        $interaction_ping = $this->mockInteractionPing(NotifiableGroupType::ALL, NotifiableType::GROUP);
        $interaction = $this->mockInteraction(collect([$interaction_ping]), $author_id, 2);

        $get_project_users = $this->mockGetProjectUsers(collect([$user_1, $user_2]));
        $get_project_users_factory = $this->mockGetProjectUsersFactory($get_project_users);

        /** @var NotificationPingExtractor $notification_ping_extractor */
        $notification_ping_extractor = $this->app->make(NotificationPingExtractor::class, [
            'interaction' => $interaction,
            'get_project_users_factory' => $get_project_users_factory
        ]);

        //WHEN
        $result = $notification_ping_extractor->extract($interaction);

        //THEN
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        /** @var INotificationPingDTO $notification_ping */
        $notification_ping = $result->first();
        $this->assertInstanceOf(INotificationPingDTO::class, $notification_ping);
        $this->assertEquals($author_id, $notification_ping->getAuthorId());
        $this->assertEquals($user_1->id, $notification_ping->getRecipientId());

        /** @var INotificationPingDTO $notification_ping */
        $notification_ping = $result->last();
        $this->assertEquals($author_id, $notification_ping->getAuthorId());
        $this->assertEquals($user_2->id, $notification_ping->getRecipientId());
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with one interaction to group all in project and one to user
     *
     * @test
     * @Expectation return valid three Notification Ping
     */
    public function extract_commentWithOneInteractionToGroupAllInProjectAndOneToUser()
    {
        //GIVEN
        $project_user_1 = $this->createNewUser();
        $project_user_2 = $this->createNewUser();
        $author_id = 11;
        $direct_user_id = 22;

        $interaction_group_ping = $this->mockInteractionPing(NotifiableGroupType::ALL, NotifiableType::GROUP);
        $interaction_user_ping = $this->mockInteractionPing($direct_user_id, NotifiableType::USER);

        $interaction = $this->mockInteraction(collect([$interaction_group_ping, $interaction_user_ping]), $author_id, 3);

        $get_project_users = $this->mockGetProjectUsers(collect([$project_user_1, $project_user_2]));
        $get_project_users_factory = $this->mockGetProjectUsersFactory($get_project_users);

        /** @var NotificationPingExtractor $notification_ping_extractor */
        $notification_ping_extractor = $this->app->make(NotificationPingExtractor::class, [
            'interaction' => $interaction,
            'get_project_users_factory' => $get_project_users_factory
        ]);

        //WHEN
        $result = $notification_ping_extractor->extract($interaction);

        //THEN
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);

        /** @var INotificationPingDTO $notification_ping */
        $notification_ping = $result[0];
        $this->assertInstanceOf(INotificationPingDTO::class, $notification_ping);
        $this->assertEquals($author_id, $notification_ping->getAuthorId());
        $this->assertEquals($project_user_1->id, $notification_ping->getRecipientId());

        /** @var INotificationPingDTO $notification_ping */
        $notification_ping = $result[1];
        $this->assertEquals($author_id, $notification_ping->getAuthorId());
        $this->assertEquals($project_user_2->id, $notification_ping->getRecipientId());

        /** @var INotificationPingDTO $notification_ping */
        $notification_ping = $result[2];
        $this->assertEquals($author_id, $notification_ping->getAuthorId());
        $this->assertEquals($direct_user_id, $notification_ping->getRecipientId());
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with no interaction
     *
     * @test
     * @Expectation return empty notification collection
     */
    public function extract_commentWithCommentWithNoInteraction()
    {
        //GIVEN
        $interaction = $this->mockInteraction(collect([]), 0, 0);

        $get_project_users = $this->mockGetProjectUsers(collect([]), 0);
        $get_project_users_factory = $this->mockGetProjectUsersFactory($get_project_users, 0);

        /** @var NotificationPingExtractor $notification_ping_extractor */
        $notification_ping_extractor = $this->app->make(NotificationPingExtractor::class, [
            'interaction' => $interaction,
            'get_project_users_factory' => $get_project_users_factory
        ]);

        //WHEN
        $result = $notification_ping_extractor->extract($interaction);

        //THEN
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }
}
