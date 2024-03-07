<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Notification\Services\InteractionNotification\InteractionFormatter;

use App\Interfaces\Interactions\IInteractionable;
use App\Models\Db\Company;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourcePropertyType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Notification\Models\Dto\SourceProperty;

trait InteractionFormatterTrait
{
    public function correctFormatDataProvider(): array
    {
        return [
            'New ticket and interaction' => [
                'action_type' => ActionType::PING,
                'event_type' => InteractionEventType::TICKET_NEW,
                'expected_title' => 'AB-1292 name test',
                'source_type' => SourceType::TICKET,
                'source_model_resolver' => function (): IInteractionable {
                    return factory(Ticket::class)->create(['title' => 'AB-1292', 'name' => 'name test']);
                },
                'expected_source_properties_resolver' => function (Ticket $ticket): array {
                    return [
                        new SourceProperty(SourcePropertyType::TICKET, $ticket->id),
                    ];
                },
            ],
            'New ticket comment and interaction' => [
                'action_type' => ActionType::PING,
                'event_type' => InteractionEventType::TICKET_COMMENT_NEW,
                'expected_title' => 'AB-1292 name test',
                'source_type' => SourceType::TICKET_COMMENT,
                'source_model_resolver' => function (): IInteractionable {
                    return factory(TicketComment::class)->create([
                        'ticket_id' => factory(Ticket::class)->create(['title' => 'AB-1292', 'name' => 'name test']),
                    ]);
                },
                'expected_source_properties_resolver' => function (TicketComment $ticket_comment): array {
                    return [
                        new SourceProperty(SourcePropertyType::TICKET, $ticket_comment->ticket_id),
                        new SourceProperty(SourcePropertyType::TICKET_COMMENT, $ticket_comment->id),
                    ];
                },
            ],
            'New knowledge page and interaction' => [
                'action_type' => ActionType::PING,
                'event_type' => InteractionEventType::KNOWLEDGE_PAGE_NEW,
                'expected_title' => 'Title test',
                'source_type' => SourceType::KNOWLEDGE_PAGE,
                'source_model_resolver' => function (): IInteractionable {
                    return factory(KnowledgePage::class)->create(['name' => 'Title test']);
                },
                'expected_source_properties_resolver' => function (KnowledgePage $knowledge_page): array {
                    return [
                        new SourceProperty(SourcePropertyType::KNOWLEDGE_PAGE, $knowledge_page->id),
                    ];
                },
            ],
            'New knowledge page comment and interaction' => [
                'action_type' => ActionType::PING,
                'event_type' => InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW,
                'expected_title' => 'Title test',
                'source_type' => SourceType::KNOWLEDGE_PAGE_COMMENT,
                'source_model_resolver' => function (): IInteractionable {
                    return factory(KnowledgePageComment::class)->create([
                        'knowledge_page_id' => factory(KnowledgePage::class)->create(['name' => 'Title test']),
                    ]);
                },
                'expected_source_properties_resolver' => function (KnowledgePageComment $knowledge_page_comment): array {
                    return [
                        new SourceProperty(SourcePropertyType::KNOWLEDGE_PAGE, $knowledge_page_comment->knowledge_page_id),
                        new SourceProperty(SourcePropertyType::KNOWLEDGE_PAGE_COMMENT, $knowledge_page_comment->id),
                    ];
                },
            ],
        ];
    }

    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    protected function createProject(int $company_id): Project
    {
        return factory(Project::class)->create(['company_id' => $company_id]);
    }

    protected function createAuthor(string $first_name, string $last_name): User
    {
        return factory(User::class)->create([
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);
    }

    public function missingKeyDataProvider(): array
    {
        return [
            [
                [
                    'action_type' => 'action',
                    'event_type' => 'event',
                    'source_type' => 'source',
                    'source_id' => 1,
                    'author_id' => 5,
                    'message' => 'message',
                ],
            ],
            [
                [
                    'project_id' => 1,
                    'action_type' => 'action',
                    'event_type' => 'event',
                    'source_type' => 'source',
                    'source_id' => 1,
                    'author_id' => 5,
                    'ref' => '#ref',
                ],
            ],
            [
                [
                    'action_type' => 'action',
                    'event_type' => 'event',
                    'source_type' => 'source',
                    'source_id' => 1,
                    'message' => 'message',
                    'ref' => '#ref',
                ],
            ],
            [
                [
                    'author_id' => 5,
                    'message' => 'message',
                    'ref' => '#ref',
                ],
            ],
        ];
    }
}
