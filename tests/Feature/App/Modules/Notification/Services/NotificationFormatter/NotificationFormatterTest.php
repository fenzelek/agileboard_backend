<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Services\NotificationFormatter;

use App\Models\Db\Ticket;
use App\Models\Other\NotificationType;
use App\Modules\Notification\Models\Dto\Notification;
use App\Modules\Notification\Services\NotificationFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Modules\Notification\Models\DatabaseNotification;
use Tests\TestCase;

class NotificationFormatterTest extends TestCase
{
    use DatabaseTransactions;
    use NotificationFormatterTrait;

    private NotificationFormatter $formatter;

    public function setUp(): void
    {
        parent::setUp();
        Ticket::unsetEventDispatcher();
        $this->formatter = $this->app->make(NotificationFormatter::class);
        DatabaseNotification::query()->delete();
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Source Ticket of interaction was deleted
     * @test
     */
    public function format_sourceTicketOfInteractionWasDeleted_ShouldReturnCorrectlyFormattedData(): void
    {
        //GIVEN
        $data = $this->prepareDataForTicketSourceOfInteractionWasDeletedTest();
        $notification = $data['notification'];
        $expected_data = $data['expected_data'];

        //WHEN
        $result = $this->formatter->format(collect([$notification]));

        //THEN
        /** @var Notification $result_notification */
        $result_notification = $result->first();

        $this->assertCount(1, $result);
        $this->assertSame($notification->id, $result_notification->getId());
        $this->assertSame(NotificationType::INTERACTION, $result_notification->getType());
        $this->assertSame($notification->read_at->toDateTimeString(), $result_notification->getReadAt()->toDateTimeString());
        $this->assertSame($notification->created_at->toDateTimeString(), $result_notification->getCreatedAt()->toDateTimeString());
        $this->assertSame($expected_data, $result_notification->getData());
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Source Knowledge Page of interaction was deleted
     * @test
     */
    public function format_sourceKnowledgePageOfInteractionWasDeleted_ShouldReturnCorrectlyFormattedData(): void
    {
        //GIVEN
        $data = $this->prepareDataForKnowledgePageSourceOfInteractionWasDeletedTest();
        $notification = $data['notification'];
        $expected_data = $data['expected_data'];

        //WHEN
        $result = $this->formatter->format(collect([$notification]));

        //THEN
        /** @var Notification $result_notification */
        $result_notification = $result->first();

        $this->assertCount(1, $result);
        $this->assertSame($notification->id, $result_notification->getId());
        $this->assertSame(NotificationType::INTERACTION, $result_notification->getType());
        $this->assertSame($notification->read_at->toDateTimeString(), $result_notification->getReadAt()->toDateTimeString());
        $this->assertSame($notification->created_at->toDateTimeString(), $result_notification->getCreatedAt()->toDateTimeString());
        $this->assertSame($expected_data, $result_notification->getData());
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Exists interaction notification
     * @test
     */
    public function format_WhenInteractionTypeOfNotification_ShouldReturnCorrectlyFormattedData(): void
    {
        //GIVEN
        $data = $this->prepareDataForInteractionNotificationTest();
        $notification = $data['notification'];
        $expected_data = $data['expected_data'];

        //WHEN
        $result = $this->formatter->format(collect([$notification]));

        //THEN
        /** @var Notification $result_notification */
        $result_notification = $result->first();

        $this->assertCount(1, $result);
        $this->assertSame($notification->id, $result_notification->getId());
        $this->assertSame(NotificationType::INTERACTION, $result_notification->getType());
        $this->assertSame($notification->read_at->toDateTimeString(), $result_notification->getReadAt()->toDateTimeString());
        $this->assertSame($notification->created_at->toDateTimeString(), $result_notification->getCreatedAt()->toDateTimeString());
        $this->assertSame($expected_data, $result_notification->getData());
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Interaction source does not exists
     *
     * @test
     */
    public function format_WhenInteractionSourceDoesNotExists_ShouldReturnCorrectlyFormattedData(): void
    {
        //GIVEN
        $data = $this->prepareDataForCaseWhenSourceDoesNotExists();
        $notification = $data['notification'];
        $expected_data = $data['expected_data'];

        //WHEN
        $result = $this->formatter->format(collect([$notification]));

        //THEN
        /** @var Notification $result_notification */
        $result_notification = $result->first();

        $this->assertCount(1, $result);
        $this->assertSame($expected_data, $result_notification->getData());
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Exists other type of notification
     *
     * @test
     */
    public function format_WhenOtherTypeOfNotification_ShouldReturnNotFormattedData(): void
    {
        //GIVEN
        $data = ['data' => 'test'];
        $type = 'other_type';
        $user = $this->createNewUser();
        $notification = $this->createNotification($user, $type, $data);

        //WHEN
        $result = $this->formatter->format(collect([$notification]));

        //THEN
        $this->assertCount(1, $result);
        $this->assertSame($type, $result->first()->getType());
        $this->assertSame($data, $result->first()->getData());
    }
}
