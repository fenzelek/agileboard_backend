<?php

namespace Tests\Unit\App\Modules\Notification\Services;

use App\Helpers\EventTypes;
use App\Modules\Notification\Services\EventChannelsSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventChannelsSettingsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function get_settings_success()
    {
        $settings = (new EventChannelsSettings())->get(EventTypes::TICKET_DELETE);

        $this->assertSame([EventChannelsSettings::BROADCAST, EventChannelsSettings::MAIL, EventChannelsSettings::SLACK], $settings);
    }
}
