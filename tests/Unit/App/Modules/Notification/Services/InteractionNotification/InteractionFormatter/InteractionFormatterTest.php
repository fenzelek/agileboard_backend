<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Notification\Services\InteractionNotification\InteractionFormatter;

use App\Interfaces\Interactions\IInteractionable;
use App\Models\Db\Ticket;
use App\Modules\Notification\Exceptions\MissingNotificationKeyException;
use App\Modules\Notification\Models\Dto\SourceProperty;
use App\Modules\Notification\Services\InteractionNotification\InteractionFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InteractionFormatterTest extends TestCase
{
    use DatabaseTransactions;
    use InteractionFormatterTrait;

    private InteractionFormatter $service;

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Notification data correct format
     *
     * @dataProvider correctFormatDataProvider
     *
     * @test
     */
    public function format_WhenSourceTypeIsTicketComment_ShouldReturnFormattedNotification(
        string $action_type,
        string $event_type,
        string $expected_title,
        string $source_type,
        callable $source_model_resolver,
        callable $expected_source_properties_resolver
    ): void {
        //GIVEN
        $author_first_name = 'Pawel';
        $author_last_name = 'Kowal';
        $ref = 'Ref';
        $message = '<h1>Message</h1>';
        $company = $this->createCompany();
        $project = $this->createProject($company->id);
        $author = $this->createAuthor($author_first_name, $author_last_name);
        /** @var IInteractionable $source_model */
        $source_model = $source_model_resolver();
        /** @var SourceProperty[] $expected_source_properties */
        $expected_source_properties = $expected_source_properties_resolver($source_model);

        //WHEN
        $result = $this->service->format([
            'project_id' => $project->id,
            'event_type' => $event_type,
            'action_type' => $action_type,
            'source_type' => $source_type,
            'source_id' => $source_model->id,
            'author_id' => $author->id,
            'ref' => $ref,
            'message' => $message,
        ], $company->id);

        //THEN
        $this->assertSame($author_first_name . ' ' . $author_last_name, $result->getAuthorName());
        $this->assertSame($source_type, $result->getSourceType());
        $this->assertSame($message, $result->getMessage());
        $this->assertSame($event_type, $result->getEventType());
        $this->assertSame($action_type, $result->getActionType());
        $this->assertSame($expected_title, $result->getTitle());
        $this->assertSame($ref, $result->getRef());
        $this->assertSame(count($expected_source_properties), count($result->getSourceProperties()));
        $i=0;
        foreach ($result->getSourceProperties() as $source_property) {
            $this->assertSame($source_property->getId(), $expected_source_properties[$i]->getId());
            $this->assertSame($source_property->getType(), $expected_source_properties[$i]->getType());
            $i++;
        }
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Notification data has missing key
     *
     * @dataProvider missingKeyDataProvider
     *
     * @test
     */
    public function format_WhenSomeKeyMissing_ShouldThrowMissingKeyException(array $data): void
    {
        $this->expectException(MissingNotificationKeyException::class);
        $this->service->format($data, 1);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Ticket::unsetEventDispatcher();
        $this->service = $this->app->make(InteractionFormatter::class);
    }
}
