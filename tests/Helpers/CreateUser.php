<?php

namespace Tests\Helpers;

use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Company;
use App\Models\Db\ModPrice;
use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Db\Transaction;
use App\Models\Other\ContractType;
use App\Models\Other\DepartmentType;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\UserCompanyStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

trait CreateUser
{
    /**
     * Testing user e-mail.
     *
     * @var string
     */
    protected $userEmail;

    /**
     * Testing user password.
     *
     * @var string
     */
    protected $userPassword;

    /**
     * User.
     *
     * @var User|null
     */
    protected $user;

    /**
     * Creates user for tests.
     *
     * @param int $deleted
     * @param int $activated
     *
     * @return $this
     */
    protected function createUser($deleted = 0, $activated = 1)
    {
        $this->userEmail = 'useremail@example.com';
        $this->userPassword = 'testpassword';

        $this->user = factory(User::class)->create([
            'email' => $this->userEmail,
            'password' => $this->userPassword,
            'deleted' => $deleted,
            'activated' => $activated,
        ]);

        return $this;

    }

    protected function createAndBeUser(): User
    {
        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);
        return $this->user;
    }

    protected function createNewUser(array $attributes = []): User
    {
        return factory(User::class)->create($attributes);
    }

    protected function setAuthUser($company): void
    {
        $auth = $this->app->make(Guard::class);
        $auth->setUser($this->user);
        $this->user->setSelectedCompany($company->id, Role::findByName(RoleType::ADMIN));
        //$this->user->setSystemRole();
    }

    protected function assignUsersToCompany(
        SupportCollection $users,
        Company $company,
        $roleSlug = RoleType::DEVELOPER,
        $status = UserCompanyStatus::APPROVED
    ) {
        $users->each(function ($user) use ($company, $roleSlug, $status) {
            $user->companies()->save(
                $company,
                ['role_id' => Role::findByName($roleSlug)->id, 'status' => $status]
            );
        });
    }

    protected function createCompanyWithRoleAndPackage($roleType, $package, $package_expiration_date = null)
    {
        return $this->createCompanyWithRole($roleType, UserCompanyStatus::APPROVED, [], $package, $package_expiration_date);
    }

    protected function createCompanyWithRole($roleType, $status = UserCompanyStatus::APPROVED, array $extras = [], $package = null, $package_expiration_date = null): Company
    {
        $company = factory(Company::class)->create();

        if ($package) {
            $package_id = Package::where('slug', $package)->first()->id;
        } else {
            $package_id = Package::findDefault()->id;
        }

        $transaction = Transaction::create();

        ModPrice::where(function ($q) use ($package_id) {
            $q->where('package_id', $package_id);
            $q->where('default', 1);
            $q->where('currency', 'PLN');
        })->orWhere(function ($q) {
            $q->orWhereNull('package_id');
            $q->where('default', 1);
            $q->where('currency', 'PLN');
        })->get()->each(function ($mod) use ($company, $package_expiration_date, $transaction) {
            $history = new CompanyModuleHistory();
            $history->company_id = $company->id;
            $history->module_id = $mod->moduleMod->module_id;
            $history->module_mod_id = $mod->module_mod_id;
            $history->package_id = $mod->package_id;
            $history->new_value = $mod->moduleMod->value;
            $history->start_date = $package_expiration_date ?
                    Carbon::createFromFormat('Y-m-d H:i:s', $package_expiration_date->toDateTimeString())->subDays($mod->days)
                    : null;
            $history->expiration_date = $mod->package_id ? $package_expiration_date : null;
            $history->price = $mod->price;
            $history->currency = $mod->currency;
            $history->transaction_id = $transaction->id;
            $history->status = CompanyModuleHistory::STATUS_USED;
            $history->save();

            $module = new CompanyModule();
            $module->company_id = $company->id;
            $module->module_id = $mod->moduleMod->module_id;
            $module->package_id = $mod->package_id;
            $module->value = $mod->moduleMod->value;
            $module->expiration_date = $mod->package_id ? $package_expiration_date : null;
            $module->save();
        });

        $this->setCompanyRole($company, $this->user, $roleType, $status, $extras);

        return $company;
    }

    protected function setCompanyRole(Company $company, User $user, $roleType, $status = UserCompanyStatus::APPROVED, array $extras = [])
    {
        $userCompany = new UserCompany();
        $userCompany->user_id = $user->id;
        $userCompany->company_id = $company->id;
        $userCompany->role_id = Role::findByName($roleType)->id;
        $userCompany->status = $status;

        foreach ($extras as $field => $value) {
            $userCompany->$field = $value;
        }
        $userCompany->save();
    }

    /**
     * Format single user into array.
     *
     * @param User $user
     *
     * @return array
     */
    protected function formatUser(User $user)
    {
        $user = $user->toArray();
        $user = array_intersect_key($user, array_flip([
            'id',
            'email',
            'first_name',
            'last_name',
            'role_id',
            'avatar',
            'deleted',
            'activated',
        ]));

        $user['activated'] = (bool) $user['activated'];
        $user['deleted'] = (bool) $user['deleted'];
        if (! isset($user['avatar'])) {
            $user['avatar'] = '';
        }

        return $user;
    }

    /**
     * Format collection of users into array.
     *
     * @param Collection $users
     *
     * @return array
     */
    protected function formatUsers(Collection $users)
    {
        $result = [];
        foreach ($users as $user) {
            $result[] = $this->formatUser($user);
        }

        return $result;
    }

    protected function setSuperAdminPermissionForUser()
    {
        if (empty($this->user)) {
            return;
        }

        $this->user->is_superadmin = true;
        $this->user->save();
    }
}
