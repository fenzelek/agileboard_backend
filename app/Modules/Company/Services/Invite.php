<?php

namespace App\Modules\Company\Services;

use App\Models\Db\Invitation;
use App\Models\Db\ModuleMod;
use App\Models\Other\ModuleType;
use App\Models\Other\InvitationStatus;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\User\Events\UserWasAssignedToCompany;
use App\Modules\User\Events\UserWasCreated;
use App\Notifications\ExistingUserInvitationCreated;
use App\Notifications\NewUserInvitationCreated;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use App\Models\Db\Company as CompanyModel;

class Invite
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Invitation
     */
    protected $invitation;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * Invite constructor.
     *
     * @param Application $app
     * @param User $user
     * @param Invitation $invitation
     * @param CompanyModel $company
     * @param Connection $db
     */
    public function __construct(
        Application $app,
        User $user,
        Invitation $invitation,
        CompanyModel $company,
        Connection $db
    ) {
        $this->user = $user;
        $this->invitation = $invitation;
        $this->company = $company;
        $this->app = $app;
        $this->db = $db;
    }

    /**
     * Add new invitation for company.
     *
     * @param Request $request
     * @param $companyId
     * @return array|mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public function add(Request $request, $companyId)
    {
        $user = $this->user->findByEmail($request->input('email'));

        // if user is already assigned to company with approved status we don't allow to create new
        // invitation
        if ($user && $user->companies()->wherePivot('status', UserCompanyStatus::APPROVED)
                ->find($companyId)) {
            return ['status' => false];
        }

        $company = $this->company->findOrFail($companyId);

        return $this->db->transaction(function () use ($company, $request, $user) {
            // check invitations settings
            $invite_enabled = $company->appSettings(ModuleType::GENERAL_INVITE_ENABLED);

            if ($invite_enabled) {
                $invitation = $this->createInvitation($company, $request);

                $initial_language = config('app.locale');
                trans()->setLocale($request->input('language', 'en'));

                if ($user) {
                    $user->notify(new ExistingUserInvitationCreated(
                        $company,
                        $invitation,
                        $request->input('url')
                    ));
                } else {
                    $invitation->notify(new NewUserInvitationCreated(
                        $company,
                        $invitation,
                        $request->input('url')
                    ));
                }

                trans()->setLocale($initial_language);
            } else {
                // if Invitation disabled, user should be added directly and activated
                if (! $user) {
                    $user = User::create($request->all());
                    $user->activated = true;
                    $user->save();

                    // assign user to company with role from invitations
                    $user->companies()->attach($company->id, [
                        'role_id' => $request->input('role_id'),
                        'status' => UserCompanyStatus::APPROVED,
                    ]);

                    $this->app['events']->dispatch(new UserWasCreated($user, null, $request->input('language', 'en')));
                    $this->app['events']->dispatch(new UserWasAssignedToCompany($user, $company->id));
                    $response = ['user' => $user];
                }
            }
            if (! isset($response)) {
                return ['status' => true];
            }
            $response['status'] = true;

            return $response;
        });
    }

    /**
     * Accept invitation.
     *
     * @param Invitation $invitation
     * @param Request $request
     *
     * @return bool
     */
    public function accept(Invitation $invitation, Request $request)
    {
        $user = $this->user->findByEmail($invitation->email);
        if ($user && $user->companies()->wherePivot('status', UserCompanyStatus::APPROVED)
                ->find($invitation->company_id)) {
            // if user is already assigned to company we don't allow to accept invitation
            return false;
        }

        return $this->db->transaction(function () use ($user, $invitation, $request) {

            // if no user exists we will create new one
            if (! $user) {
                $user = $this->user->newInstance();
                $user->email = $invitation->email;
                $user->password = $request->input('password');
                $user->first_name = $invitation->first_name;
                $user->last_name = $invitation->last_name;
                $user->activated = true;
                $user->save();
                $this->app['events']->dispatch(new UserWasCreated($user, null, $request->input('language', 'en')));
            }

            // because user might have been assigned to this company before with different role and
            // status, we need to first remove this connection
            $user->companies()->detach($invitation->company_id);

            // assign user to company with role from invitations
            $user->companies()->attach($invitation->company_id, [
                'role_id' => $invitation->role_id,
                'status' => UserCompanyStatus::APPROVED,
            ]);

            $this->app['events']->dispatch(new UserWasAssignedToCompany(
                $user,
                $invitation->company_id
            ));

            // change invitation status to approved
            $invitation->status = InvitationStatus::APPROVED;
            $invitation->save();

            return true;
        });
    }

    /**
     * Refuse invitation.
     *
     * @param Invitation $invitation
     *
     * @return Invitation
     */
    public function reject(Invitation $invitation)
    {
        $invitation->status = InvitationStatus::REJECTED;
        $invitation->save();

        return $invitation;
    }

    /**
     * @param $company_id
     * @return bool
     */
    public function cantAddUsers($company_id)
    {
        $company = $this->company->findOrFail($company_id);
        $setting = $company->appSettings(ModuleType::GENERAL_MULTIPLE_USERS);

        if ($setting == ModuleMod::UNLIMITED) {
            return false;
        }

        if ($setting > $company->users()->count()) {
            return false;
        }

        return true;
    }

    /**
     * Create new invitation for given company.
     *
     * @param CompanyModel $company
     * @param $request
     *
     * @return Invitation
     */
    protected function createInvitation(CompanyModel $company, $request)
    {
        $invitation = $this->invitation->newInstance();
        $invitation->inviting_user_id = $request->user()->id;
        $invitation->email = $request->input('email');
        $invitation->first_name = $request->input('first_name', '');
        $invitation->last_name = $request->input('last_name', '');
        $invitation->role_id = $request->input('role_id');
        $invitation->unique_hash = $this->getUniqueHash();
        $invitation->expiration_time = Carbon::now()
            ->addMinutes($this->app['config']->get('app_settings.invitations.expire_time'));
        $invitation->status = InvitationStatus::PENDING;

        $company->invitations()->save($invitation);

        return $invitation;
    }

    /**
     * Get unique invitation hash.
     *
     * @return string
     */
    protected function getUniqueHash()
    {
        do {
            $hash = time() . '_' . str_random(40);
        } while ($this->invitation->where('unique_hash', $hash)->first());

        return $hash;
    }
}
