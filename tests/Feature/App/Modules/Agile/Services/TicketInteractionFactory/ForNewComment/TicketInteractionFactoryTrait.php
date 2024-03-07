<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Services\TicketInteractionFactory\ForNewComment;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\TicketComment;
use App\Models\Other\Interaction\NotifiableType;
use App\Modules\Agile\Http\Requests\InteractionPingRequest;
use App\Modules\Knowledge\Contracts\ICommentCreateRequest;
use Illuminate\Support\Collection;
use Mockery as m;
use Mockery\MockInterface;

trait TicketInteractionFactoryTrait
{
    protected function createCompany(array $attributes = []): Company
    {
        return factory(Company::class)->create($attributes);
    }

    protected function createProject(array $attributes = []): Project
    {
        return factory(Project::class)->create($attributes);
    }

    protected function createComment(array $attributes = []): TicketComment
    {
        return factory(TicketComment::class)->create($attributes);
    }

    protected function mockCreateCommentRequest(int $project_id, int $recipient_id, bool $with_interaction_pings=true): ICommentCreateRequest
    {
        $request = m::mock(ICommentCreateRequest::class);

        $request->shouldReceive('getProjectId')->andReturn($project_id);
        $request->shouldReceive('getSelectedCompanyId')->andReturn(Project::find($project_id)->company_id);
        if ($with_interaction_pings)
        {
            $request->shouldReceive('getInteractionPings')->andReturn(
                collect([$this->mockInteractionPing($recipient_id)])
            );

            return $request;
        }

        $request->shouldReceive('getInteractionPings')->andReturn(New Collection());
        return $request;
    }

    /**
     * @return InteractionPingRequest|MockInterface
     */
    protected function mockInteractionPing(int $recipient_id): InteractionPingRequest
    {
        $interaction_ping = m::mock(InteractionPingRequest::class);
        $interaction_ping->shouldReceive('getRef')->andReturn('Frontend ref');
        $interaction_ping->shouldReceive('getMessage')->andReturn('Interaction message');
        $interaction_ping->shouldReceive('getNotifiable')->andReturn(NotifiableType::USER);
        $interaction_ping->shouldReceive('getRecipientId')->andReturn($recipient_id);

        return $interaction_ping;
    }
}
