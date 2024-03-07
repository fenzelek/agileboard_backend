<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Services\InteractionNotificationManager;

use App\Interfaces\Interactions\INotificationPingDTO;
use App\Models\Db\Company;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourceType;
use Illuminate\Database\Eloquent\Model;

trait InteractionNotificationManagerTrait
{
    public function validInteractionDataProvider(): array
    {
        return [
            [InteractionEventType::TICKET_COMMENT_NEW, ActionType::PING, SourceType::TICKET_COMMENT],
            [InteractionEventType::TICKET_COMMENT_EDIT, ActionType::PING, SourceType::TICKET_COMMENT],
            [InteractionEventType::TICKET_NEW, ActionType::PING, SourceType::TICKET],
            [InteractionEventType::TICKET_NEW, ActionType::PING, SourceType::TICKET],
            [InteractionEventType::KNOWLEDGE_PAGE_NEW, ActionType::PING, SourceType::KNOWLEDGE_PAGE],
            [InteractionEventType::KNOWLEDGE_PAGE_EDIT, ActionType::PING, SourceType::KNOWLEDGE_PAGE],
            [InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW, ActionType::PING, SourceType::KNOWLEDGE_PAGE_COMMENT],
            [InteractionEventType::KNOWLEDGE_PAGE_COMMENT_EDIT, ActionType::PING, SourceType::KNOWLEDGE_PAGE_COMMENT],
        ];
    }

    private function mockInvalidDocumentType(): INotificationPingDTO
    {
        $mock = \Mockery::mock(INotificationPingDTO::class);
        $mock->shouldReceive('getSourceType')->andReturn('random_bla_bla_bla');

        return $mock;
    }

    private function mockInvalidActionType(): INotificationPingDTO
    {
        $mock = \Mockery::mock(INotificationPingDTO::class);
        $mock->shouldReceive('getSourceType')->andReturn(SourceType::TICKET);
        $mock->shouldReceive('getActionType')->andReturn('ranodm_adsad');
        $mock->shouldReceive('getEventType')->andReturn(InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW);

        return $mock;
    }

    private function mockInvalidEventType(): INotificationPingDTO
    {
        $mock = \Mockery::mock(INotificationPingDTO::class);
        $mock->shouldReceive('getSourceType')->andReturn(SourceType::TICKET);
        $mock->shouldReceive('getActionType')->andReturn(ActionType::COMMENT);
        $mock->shouldReceive('getEventType')->andReturn('asas_sada');

        return $mock;
    }

    private function mockValidInteraction(string $event_type, string $action_type, string $document_type): INotificationPingDTO
    {
        $author = factory(User::class)->create();
        $project = factory(Project::class)->create();
        $recipient = factory(User::class)->create();
        $document = $this->createSourceByType($document_type);
        $company = factory(Company::class)->create();

        $mock = \Mockery::mock(INotificationPingDTO::class);
        $mock->shouldReceive('getSelectedCompanyId')->andReturn($company->id);
        $mock->shouldReceive('getRecipientId')->andReturn($recipient->id);
        $mock->shouldReceive('getAuthorId')->andReturn($author->id);
        $mock->shouldReceive('getSourceType')->andReturn($document_type);
        $mock->shouldReceive('getSourceId')->andReturn($document->id);
        $mock->shouldReceive('getActionType')->andReturn($action_type);
        $mock->shouldReceive('getEventType')->andReturn($event_type);
        $mock->shouldReceive('getProjectId')->andReturn($project->id);
        $mock->shouldReceive('getRef')->andReturn('#ref');
        $mock->shouldReceive('getMessage')->andReturn(null);

        return $mock;
    }

    private function mockNotExistingRecipient(): INotificationPingDTO
    {
        User::query()->delete();
        $user = factory(User::class)->create();
        $ticket_comment = factory(TicketComment::class)->create();
        $company = factory(Company::class)->create();

        $mock = \Mockery::mock(INotificationPingDTO::class);
        $mock->shouldReceive('getSelectedCompanyId')->andReturn($company->id);
        $mock->shouldReceive('getSourceType')->andReturn(SourceType::TICKET_COMMENT);
        $mock->shouldReceive('getSourceId')->andReturn($ticket_comment->id);
        $mock->shouldReceive('getActionType')->andReturn(ActionType::COMMENT);
        $mock->shouldReceive('getEventType')->andReturn(InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW);

        $mock->shouldReceive('getRecipientId')->andReturn($user->id+1);
        $mock->shouldReceive('getAuthorId')->andReturn($user->id);

        return $mock;
    }

    private function mockNotExistingAuthor(): INotificationPingDTO
    {
        User::query()->delete();
        $user = factory(User::class)->create();
        $ticket_comment = factory(TicketComment::class)->create();

        $mock = \Mockery::mock(INotificationPingDTO::class);
        $mock->shouldReceive('getSourceType')->andReturn(SourceType::TICKET_COMMENT);
        $mock->shouldReceive('getSourceId')->andReturn($ticket_comment->id);
        $mock->shouldReceive('getActionType')->andReturn(ActionType::COMMENT);
        $mock->shouldReceive('getEventType')->andReturn(InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW);
        $mock->shouldReceive('getRecipientId')->andReturn($user->id);
        $mock->shouldReceive('getAuthorId')->andReturn($user->id+1);

        return $mock;
    }

    private function mockNotExistingDocument(): INotificationPingDTO
    {
        TicketComment::query()->delete();
        $author = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $mock = \Mockery::mock(INotificationPingDTO::class);
        $mock->shouldReceive('getActionType')->andReturn(ActionType::COMMENT);
        $mock->shouldReceive('getEventType')->andReturn(InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW);
        $mock->shouldReceive('getRecipientId')->andReturn($recipient->id);
        $mock->shouldReceive('getAuthorId')->andReturn($author->id);
        $mock->shouldReceive('getSourceType')->andReturn(SourceType::TICKET_COMMENT);
        $mock->shouldReceive('getSourceId')->andReturn(1);

        return $mock;
    }

    private function createSourceByType(string $document_type): Model
    {
        switch ($document_type) {
            case SourceType::TICKET:
                return factory(Ticket::class)->create();
            case SourceType::TICKET_COMMENT:
                return factory(TicketComment::class)->create();
            case SourceType::KNOWLEDGE_PAGE:
                return factory(KnowledgePage::class)->create();
            case SourceType::KNOWLEDGE_PAGE_COMMENT:
                return factory(KnowledgePageComment::class)->create();
        }
        throw new \UnexpectedValueException('Unexpected document type');
    }
}
