<?php

namespace App\Modules\User\Services;

use App\Modules\User\Events\UserWasCreated;
use Illuminate\Contracts\Events\Dispatcher as Event;
use Illuminate\Http\Request;
use DB;
use App\Models\Db\User as UserModel;
use App\Modules\User\Services\Storage as ServicesStorage;
use Illuminate\Database\Connection;

class User
{
    /**
     * @var Event
     */
    protected $event;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var ServicesStorage
     */
    protected $services_storage;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * User constructor.
     *
     * @param Event $event
     * @param UserModel $user
     * @param \App\Modules\User\Services\Storage $services_storage
     * @param Connection $db
     */
    public function __construct(
        Event $event,
        UserModel $user,
        ServicesStorage $services_storage,
        Connection $db
    ) {
        $this->event = $event;
        $this->user = $user;
        $this->services_storage = $services_storage;
        $this->db = $db;
    }

    /**
     * Create new user.
     *
     * @param Request $request
     *
     * @return UserModel
     */
    public function create(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // create user with activation hash
            $user =
                $this->user->fill($request->only('email', 'password', 'first_name', 'last_name', 'discount_code'));
            $user->activated = false;
            $user->save();
            $user->activate_hash = $user->id . '_' . time() . str_random(40);
            $user->save();

            // dispatch user created event
            $this->event->dispatch(new UserWasCreated($user, $request->input('url'), $request->input('language', 'en')));

            // return full user object
            return $user->fresh();
        });
    }

    /**
     * Check correct password for or get true if user is super user.
     *
     * @param UserModel $user
     * @param Request $request
     *
     * @return bool
     */
    public function checkPassword(UserModel $user, Request $request)
    {
        $credentials = [
            'email' => $user->email,
            'password' => $request->input('old_password'),
        ];

        return auth()->user()->isSystemAdmin() || auth()->validate($credentials);
    }

    /**
     * Update data for user.
     *
     * @param UserModel $user
     * @param Request $request
     *
     * @return UserModel
     * @throws \Exception
     */
    public function updateData(UserModel $user, Request $request)
    {
        $this->db->beginTransaction();

        try {
            if ($request->input('first_name')) {
                $user->first_name = trim($request->input('first_name'));
            }

            if ($request->input('last_name')) {
                $user->last_name = trim($request->input('last_name'));
            }

            if ($request->input('password')) {
                $user->password = $request->input('password');
            }

            // complete remove avatar
            if ($request->input('remove_avatar') == 1) {
                $this->services_storage->deleteFile('avatar', '', $user->avatar);
                $user->avatar = '';
            } else {
                if ($request->avatar) {
                    // remove old avatar
                    if ($user->avatar) {
                        $this->services_storage->deleteFile('avatar', '', $user->avatar);
                    }
                    // update new avatar
                    $user->avatar = $this->services_storage->updateAvatar($user, $request->avatar);
                }
            }

            $user->save();

            $this->db->commit();

            return $user;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
