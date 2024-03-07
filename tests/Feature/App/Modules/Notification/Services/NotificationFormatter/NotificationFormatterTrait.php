<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Notification\Services\NotificationFormatter;

use App\Models\Db\Company;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourcePropertyType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Notification\Notifications\InteractionNotification;
use Carbon\Carbon;
use App\Modules\Notification\Models\DatabaseNotification;
use Illuminate\Support\Str;

trait NotificationFormatterTrait
{
    public function prepareDataForTicketSourceOfInteractionWasDeletedTest(): array
    {
        $author_first_name = 'Maciek';
        $author_last_name = 'Kowalski';
        $ticket_title = 'AB-123';
        $ticket_name = 'Page name';
        $interaction_author = $this->createAuthor($author_first_name, $author_last_name);
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $ticket = $this->createTicket($project->id, $ticket_name, $ticket_title);

        $dataForTicket = [
            'project_id' => $project->id,
            'author_id' => $interaction_author->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_DELETED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ];
        $type = InteractionNotification::class;
        $user = $this->createNewUser();
        $notification = $this->createNotification($user, $type, $dataForTicket, $company->id);

        $dataForTicket =  [
            'notification' => $notification,
            'expected_data' => [
                'project_id' => $project->id,
                'title' => $ticket_title . ' ' . $ticket_name,
                'action_type' => $dataForTicket['action_type'],
                'source_type' => $dataForTicket['source_type'],
                'event_type' => $dataForTicket['event_type'],
                'author_name' => $author_first_name . ' ' . $author_last_name,
                'source_properties' => [
                    ['type' => SourcePropertyType::TICKET, 'id' => (string) $ticket->id],
                ],
                'ref' => '',
                'message' => '',
            ],
        ];

        $ticket->delete();

        return $dataForTicket;
    }
    public function prepareDataForKnowledgePageSourceOfInteractionWasDeletedTest(): array
    {
        $author_first_name = 'Maciek';
        $author_last_name = 'Kowalski';
        $page_name = 'Page name';
        $interaction_author = $this->createAuthor($author_first_name, $author_last_name);
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $knowledge_page = $this->createKnowledgePage([
            'name' => $page_name,
            'project_id' => $project->id
        ]);

        $dataForTicket = [
            'project_id' => $project->id,
            'author_id' => $interaction_author->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_DELETED,
            'source_type' => SourceType::KNOWLEDGE_PAGE,
            'source_id' => $knowledge_page->id,
            'ref' => null,
            'message' => null,
        ];

        $type = InteractionNotification::class;
        $user = $this->createNewUser();
        $notification = $this->createNotification($user, $type, $dataForTicket, $company->id);

        $dataForTicket =  [
            'notification' => $notification,
            'expected_data' => [
                'project_id' => $project->id,
                'title' => $page_name,
                'action_type' => $dataForTicket['action_type'],
                'source_type' => $dataForTicket['source_type'],
                'event_type' => $dataForTicket['event_type'],
                'author_name' => $author_first_name . ' ' . $author_last_name,
                'source_properties' => [
                    ['type' => SourcePropertyType::KNOWLEDGE_PAGE, 'id' => (string) $knowledge_page->id],
                ],
                'ref' => '',
                'message' => '',
            ],
        ];

        $knowledge_page->delete();

        return $dataForTicket;
    }

    protected function prepareDataForInteractionNotificationTest(): array
    {
        $author_first_name = 'Maciek';
        $author_last_name = 'Kowalski';
        $ticket_title = 'AB-123';
        $ticket_name = 'Page name';
        $interaction_author = $this->createAuthor($author_first_name, $author_last_name);
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $ticket_comment = $this->createTicketComment($interaction_author->id, $project->id, $ticket_name, $ticket_title);

        $data = [
            'project_id' => $project->id,
            'author_id' => $interaction_author->id,
            'action_type' => ActionType::PING,
            'event_type' => InteractionEventType::TICKET_COMMENT_NEW,
            'source_type' => SourceType::TICKET_COMMENT,
            'source_id' => $ticket_comment->id,
            'ref' => '#comment',
            'message' => '<h1>Please check this @All</h1>',
        ];
        $type = InteractionNotification::class;
        $user = $this->createNewUser();
        $notification = $this->createNotification($user, $type, $data, $company->id);

        return [
            'notification' => $notification,
            'expected_data' => [
                'project_id' => $project->id,
                'title' => $ticket_title . ' ' . $ticket_name,
                'action_type' => $data['action_type'],
                'source_type' => $data['source_type'],
                'event_type' => $data['event_type'],
                'author_name' => $author_first_name . ' ' . $author_last_name,
                'source_properties' => [
                    ['type' => SourcePropertyType::TICKET, 'id' => (string) $ticket_comment->ticket_id],
                    ['type' => SourcePropertyType::TICKET_COMMENT, 'id' => (string) $ticket_comment->id],
                ],
                'ref' => '#comment',
                'message' => '<h1>Please check this @All</h1>',
            ],
        ];
    }

    protected function prepareDataForCaseWhenSourceDoesNotExists(): array
    {
        TicketComment::query()->delete();
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $author_first_name = 'Maciek';
        $author_last_name = 'Kowalski';
        $interaction_author = $this->createAuthor($author_first_name, $author_last_name);

        $data = [
            'project_id' => $project->id,
            'author_id' => $interaction_author->id,
            'action_type' => ActionType::PING,
            'event_type' => InteractionEventType::TICKET_COMMENT_NEW,
            'source_type' => SourceType::TICKET_COMMENT,
            'source_id' => 1,
            'ref' => '#comment',
            'message' => '<h1>Please check this @All</h1>',
        ];
        $type = InteractionNotification::class;
        $user = $this->createNewUser();
        $notification = $this->createNotification($user, $type, $data, $company->id);

        return [
            'notification' => $notification,
            'expected_data' => [
                'project_id' => $project->id,
                'title' => '',
                'action_type' => $data['action_type'],
                'source_type' => $data['source_type'],
                'event_type' => $data['event_type'],
                'author_name' => $author_first_name . ' ' . $author_last_name,
                'source_properties' => null,
                'ref' => '#comment',
                'message' => '<h1>Please check this @All</h1>',
            ],
        ];
    }

    protected function createAuthor(string $first_name='', string $last_name=''): User
    {
        return factory(User::class)->create([
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);
    }

    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    protected function createProject(int $company_id): Project
    {
        return factory(Project::class)->create(['company_id' => $company_id]);
    }

    protected function createTicketComment(int $user_id, int $project_id, string $ticket_name, string $ticket_title): TicketComment
    {
        return factory(TicketComment::class)->create([
            'user_id' => $user_id,
            'ticket_id' => factory(Ticket::class)->create([
                'project_id' => $project_id,
                'name' => $ticket_name,
                'title' => $ticket_title,
            ])->id,
        ]);
    }

    protected function createTicket(int $project_id, string $ticket_name, string $ticket_title): Ticket
    {
        return factory(Ticket::class)->create([
                'project_id' => $project_id,
                'name' => $ticket_name,
                'title' => $ticket_title,
        ]);
    }

    protected function createKnowledgePage(array $attributes=[]): KnowledgePage
    {
        return factory(KnowledgePage::class)->create($attributes);
    }

    protected function createNotification(User $user, string $type, array $data, ?int $company_id=null): DatabaseNotification
    {
        /** @var DatabaseNotification */
        return DatabaseNotification::query()->create([
            'id' => Str::uuid()->toString(),
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'type' => $type,
            'read_at' => Carbon::now(),
            'data' => $data,
            'company_id' => $company_id,
        ]);
    }
}
