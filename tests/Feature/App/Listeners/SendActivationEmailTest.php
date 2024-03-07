<?php

namespace Tests\Feature\App\Listeners;

use App\Listeners\SendActivationEmail;
use App\Models\Db\User;
use App;
use App\Modules\User\Events\ActivationTokenWasRequested;
use App\Modules\User\Events\UserWasCreated;
use App\Notifications\Activation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Notification;
use Tests\TestCase;

class SendActivationEmailTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_doesnt_send_activation_email_when_user_is_activated()
    {
        Notification::fake();

        $user = factory(User::class)->create(['activated' => 1]);

        $listener = App::make(SendActivationEmail::class);
        $listener->handle(new UserWasCreated($user));

        Notification::assertNotSentTo($user, Activation::class);
    }

    /** @test */
    public function it_sends_activation_email_when_user_is_not_activated()
    {
        Notification::fake();
        $activateHash = 'sample_activation_hash';
        $url = 'http://sample.example.com/url/com';

        $user = factory(User::class)->create(['activated' => 0, 'activate_hash' => $activateHash]);

        $listener = App::make(SendActivationEmail::class);
        $listener->handle(new UserWasCreated($user, $url));

        Notification::assertSentTo(
            $user,
            Activation::class,
            function ($notification, $channels) use ($url) {
                return $channels == ['mail'] && $notification->url == $url;
            }
        );
    }

    /** @test */
    public function it_sends_activation_email_when_user_is_not_activated_for_resend_activation_token()
    {
        Notification::fake();
        $activateHash = 'sample_activation_hash';
        $url = 'http://sample.example.com/url/com';

        $user = factory(User::class)->create(['activated' => 0, 'activate_hash' => $activateHash]);

        $listener = App::make(SendActivationEmail::class);
        $listener->handle(new ActivationTokenWasRequested($user, $url));

        Notification::assertSentTo(
            $user,
            Activation::class,
            function ($notification, $channels) use ($url) {
                return $channels == ['mail'] && $notification->url == $url;
            }
        );
    }
}
