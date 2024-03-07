<?php

namespace Tests\Feature\App\Modules\User\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Notifications\ResetPassword;
use Carbon\Carbon;
use Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\Channels\MailChannel;
//use MailThief\Testing\InteractsWithMail;
use Notification;
use App;
use Mail;
use Tests\BrowserKitTestCase;

class PasswordControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    //use InteractsWithMail;

    protected $testUrl = 'http://example.com/:token/?email=:email';

    public function testSendResetEmail_withoutData()
    {
        Notification::fake();
        $this->createUser();
        $this->post('/password/reset', ['language' => 'aa']);

        $this->verifyValidationResponse(['email', 'url', 'language']);
        Notification::assertNotSentTo($this->user, ResetPassword::class);
    }

    public function testSendResetEmail_withInvalidEmail()
    {
        Notification::fake();
        $this->createUser();
        $this->post('/password/reset', [
            'email' => $this->userEmail . 'xxx',
            'url' => $this->testUrl,
        ]);

        $this->verifyErrorResponse(404, ErrorCode::PASSWORD_NO_USER_FOUND);
        Notification::assertNotSentTo($this->user, ResetPassword::class);
    }

    public function testSendResetEmail_whenUserDeleted()
    {
        Notification::fake();
        $this->createUser(1, 1);

        $this->post('/password/reset', [
            'email' => $this->userEmail,
            'url' => $this->testUrl,
        ]);

        $this->verifyErrorResponse(404, ErrorCode::PASSWORD_NO_USER_FOUND);
        Notification::assertNotSentTo($this->user, ResetPassword::class);
    }

    public function testSendResetEmail_whenUserNotActivated()
    {
        Notification::fake();
        $this->createUser(0, 0);

        $this->post('/password/reset', [
            'email' => $this->userEmail,
            'url' => $this->testUrl,
        ]);

        $this->verifyErrorResponse(404, ErrorCode::PASSWORD_NO_USER_FOUND);
        Notification::assertNotSentTo($this->user, ResetPassword::class);
    }

    public function testSendResetEmail_withValidEmail()
    {
        Notification::fake();
        $this->createUser();

        $this->post('/password/reset', [
            'email' => $this->userEmail,
            'url' => $this->testUrl,
            'language' => 'pl',
        ])->seeStatusCode(201);

        $token = \DB::table('password_resets')->first()->token;

        // first we verify whether valid notification has been triggered
        Notification::assertSentTo(
            $this->user,
            ResetPassword::class,
            function ($notification, $channels) use ($token) {
                return $channels == ['mail'] && Hash::check($notification->token, $token);
            }
        );

        // it seems it cannot be tested like this any more in Laravel 5.5

        // now we send same notification manually (MailChannel)
//        $mailChannel = App::make(MailChannel::class);
//        $mailChannel->send($this->user, new ResetPassword($token));
//        // now we verify this message
//        $this->seeMessageFor($this->userEmail);
//        $this->seeMessageFrom(config('mail.from.address'));
//        $this->seeMessageWithSubject(trans('emails.reset_password.subject'));
//        $this->assertTrue($this->lastMessage()->contains(str_replace([':email', ':token'],
//            [urlencode($this->userEmail), $token], $this->testUrl)));
    }

    public function testReset_withNoData()
    {
        $this->createUser();

        $this->put('/password/reset', []);

        $this->verifyValidationResponse(['token', 'email', 'password']);
    }

    public function testReset_withValidData()
    {
        $this->createUser();
        $token = $this->createPasswordToken();

        $newPassword = 'test00';

        $this->put('/password/reset', [
            'email' => $this->userEmail,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => 'test00',
        ])->seeStatusCode(200)->isJson();

        // make sure password was really saved and user can use it
        $this->assertFalse(auth()->check());
        auth()->attempt([
            'email' => $this->userEmail,
            'password' => $newPassword,
        ]);
        $this->assertTrue(auth()->check());
    }

    public function testReset_withExpiredToken()
    {
        $this->createUser();
        $token = $this->createPasswordToken(true);

        $newPassword = 'test00';

        $this->put('/password/reset', [
            'email' => $this->userEmail,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => 'test00',
        ]);

        $this->verifyErrorResponse(422, ErrorCode::PASSWORD_INVALID_TOKEN);
    }

    public function testReset_withInvalidEmail()
    {
        $this->createUser();
        $token = $this->createPasswordToken();

        $newPassword = 'test00';

        $this->put('/password/reset', [
            'email' => $this->userEmail . 'a',
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => 'test00',
        ]);

        $this->verifyErrorResponse(404, ErrorCode::PASSWORD_NO_USER_FOUND);
    }

    public function testReset_withInvalidPassword()
    {
        $this->createUser();
        $token = $this->createPasswordToken();

        $newPassword = 'test00';

        $this->put('/password/reset', [
            'email' => $this->userEmail . 'a',
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => 'test00',
        ]);

        $this->verifyErrorResponse(404, ErrorCode::PASSWORD_NO_USER_FOUND);
    }

    public function testReset_whenUserDeleted()
    {
        $this->createUser(1, 1);
        $token = $this->createPasswordToken();

        $newPassword = 'test00';

        $this->put('/password/reset', [
            'email' => $this->userEmail,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => 'test00',
        ]);

        $this->verifyErrorResponse(404, ErrorCode::PASSWORD_NO_USER_FOUND);
    }

    public function testReset_whenUserNotActivated()
    {
        $this->createUser(1, 0);
        $token = $this->createPasswordToken();

        $newPassword = 'test00';

        $this->put('/password/reset', [
            'email' => $this->userEmail,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => 'test00',
        ]);

        $this->verifyErrorResponse(404, ErrorCode::PASSWORD_NO_USER_FOUND);
    }

    protected function createPasswordToken($expired = false)
    {
        $token = str_random();
        $date = Carbon::now();
        if ($expired) {
            $date->subMinutes(config('auth.passwords.users.expire') + 1);
        }

        \DB::table('password_resets')->insert([
            'email' => $this->userEmail,
            'token' => bcrypt($token),
            'created_at' => $date->format('Y-m-d H:i:s'),
        ]);

        return $token;
    }
}
