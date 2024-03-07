<?php

namespace Tests\Feature\App\Listeners;

use App\Listeners\SendNotifyEmailUserWasCreated;
use App\Models\Db\User;
use App;
use App\Modules\User\Events\UserWasCreated;
use App\Notifications\CreatedNewUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Notification;
use Tests\TestCase;

class SendNotifyEmailUserWasCreatedTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_doesnt_send_activation_email_when_user_is_activated()
    {
        Notification::fake();

        $user = factory(User::class)->create(['activated' => 1]);

        $listener = App::make(SendNotifyEmailUserWasCreated::class);
        $listener->handle(new UserWasCreated($user));

        Notification::assertSentTo(
            $user,
            CreatedNewUser::class,
            function ($notification, $channels) use ($user) {
                return $channels == ['mail'];
            }
        );
    }
}
