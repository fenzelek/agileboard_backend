<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Services\InteractionNotificationManager;

use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Modules\Notification\Models\DatabaseNotification;
use App\Modules\Notification\Models\Descriptors\FailReason;
use App\Modules\Notification\Services\InteractionNotificationManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InteractionNotificationManagerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractionNotificationManagerTrait;

    private InteractionNotificationManager $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(InteractionNotificationManager::class);
        Ticket::unsetEventDispatcher();
    }

    /**
     * @feature Notification
     * @scenario Send interaction notification
     * @case Interaction is valid
     *
     * @dataProvider validInteractionDataProvider
     *
     * @test
     */
    public function send_ShouldSaveInteractionNotificationToDatabase_WhenValidInteraction(
        string $event_type,
        string $action_type,
        string $document_type
    ): void {
        //GIVEN
        $interaction = $this->mockValidInteraction($event_type, $action_type, $document_type);
        /** @var User $recipient */
        $recipient = User::query()->findOrFail($interaction->getRecipientId());

        //When
        $result = $this->service->notify($interaction);
        /** @var DatabaseNotification $notification */
        $notification = $recipient->notifications()->first();

        //Then
        $this->assertTrue($result->success());
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $interaction->getProjectId(),
            'author_id' => $interaction->getAuthorId(),
            'action_type' => $interaction->getActionType(),
            'event_type' => $interaction->getEventType(),
            'source_type' => $interaction->getSourceType(),
            'source_id' => $interaction->getSourceId(),
            'ref' => $interaction->getRef(),
            'message' => $interaction->getMessage(),
        ], $notification->data);
        $this->assertSame($interaction->getSelectedCompanyId(), $notification->company_id);
    }

    /**
     * @feature Notification
     * @scenario Send interaction notification
     * @case Recipient does not exists
     *
     * @test
     */
    public function send_ShouldReturnFailResult_WhenRecipientDoesExists(): void
    {
        //GIVEN
        $interaction = $this->mockNotExistingRecipient();

        //WHEN
        $result = $this->service->notify($interaction);

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::RECIPIENT_DOES_NOT_EXISTS, $result->getFailReason());
    }

    /**
     * @feature Notification
     * @scenario Send interaction notification
     * @case Author does not exists
     *
     * @test
     */
    public function send_ShouldReturnFailResult_WhenAuthorDoesExists(): void
    {
        //GIVEN
        $interaction = $this->mockNotExistingAuthor();

        //WHEN
        $result = $this->service->notify($interaction);

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::AUTHOR_DOES_NOT_EXISTS, $result->getFailReason());
    }

    /**
     * @feature Notification
     * @scenario Send interaction notification
     * @case Document type is invalid
     *
     * @test
     */
    public function send_ShouldReturnFailResult_WhenDocumentTypeIsInvalid(): void
    {
        //GIVEN
        $interaction = $this->mockInvalidDocumentType();

        //WHEN
        $result = $this->service->notify($interaction);

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::INVALID_DOCUMENT_TYPE, $result->getFailReason());
    }

    /**
     * @feature Notification
     * @scenario Send interaction notification
     * @case Event type is invalid
     *
     * @test
     */
    public function send_ShouldReturnFailResult_WhenEventTypeIsInvalid(): void
    {
        //GIVEN
        $interaction = $this->mockInvalidEventType();

        //WHEN
        $result = $this->service->notify($interaction);

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::INVALID_EVENT_TYPE, $result->getFailReason());
    }

    /**
     * @feature Notification
     * @scenario Send interaction notification
     * @case Action type is invalid
     *
     * @test
     */
    public function send_ShouldReturnFailResult_WhenActionTypeIsInvalid(): void
    {
        //GIVEN
        $interaction = $this->mockInvalidActionType();

        //WHEN
        $result = $this->service->notify($interaction);

        //THEN
        $this->assertFalse($result->success());
        $this->assertSame(FailReason::INVALID_ACTION_TYPE, $result->getFailReason());
    }
}
