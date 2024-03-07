<?php

namespace App\Models\Db;

use App\Interfaces\CompanyInterface;
use App\Models\CustomCollections\UsersCollection;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use App\Modules\User\Traits\Active;
use App\Modules\User\Traits\Allowed;
use App\Modules\User\Traits\Fillable;
use App\Modules\User\Traits\Removeable;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Notifications\Traits\Notifiable;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\Roleable as RoleableContract;
use App\Services\Mnabialek\LaravelAuthorize\Traits\Roleable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property Collection $userCompanies
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property Collection|UserAvailability[] $availabilities
 * @property Collection|Activity[] $activities
 * @method static Builder|User newQuery()
 * @method static Builder|User active()
 * @method static Builder|User inSelectedCompany(int $selected_company_id, string $role_type)
 * @method static Builder|User withAvailabilities(Carbon $start_date, Carbon $end_date, int $company_id)
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    RoleableContract,
    CompanyInterface,
    JWTSubject
{
    use Authenticatable, Authorizable, CanResetPassword, Notifiable;

    use Allowed, Fillable, Removeable, Active;

    use Roleable;

    /**
     * User selected project for current request.
     *
     * @var Project|null
     */
    public $selected_project;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'discount_code',
        'avatar',
        'deleted',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * User system role.
     *
     * @var null
     */
    protected $system_role;

    /**
     * User selected company id for current request.
     *
     * @var null
     */
    protected $selected_company_id;

    /**
     * Selected role for selected company.
     *
     * @var Role|null
     */
    protected $selected_company_role;

    public function getCompanyId()
    {
        return $this->getSelectedCompanyId();
    }

    // relationships

    /**
     * User can be assigned to multiple projects.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projects()
    {
        // There are child classes which exted this model and they need to have the pivot name defined (or it will fuck up)
        return $this->belongsToMany(Project::class, 'project_user', 'user_id')
            ->withTimestamps()
            ->withPivot(['user_id', 'project_id', 'role_id']);
    }

    /**
     * User can declare multiple availabilities.
     *
     * @return HasMany
     */
    public function availabilities()
    {
        return $this->hasMany(UserAvailability::class)
            ->orderBy('user_id', 'ASC')
            ->orderBy('day', 'ASC')
            ->orderBy('time_start', 'ASC');
    }

    /**
     * User has multiple ticket realizations.
     *
     * @return HasMany
     */
    public function ticketRealization()
    {
        return $this->hasMany(TicketRealization::class)->orderBy('start_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'user_id');
    }

    /**
     * User can be assigned to multiple companies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'user_company',
            'user_id',
            'company_id'
        )->withPivot(['role_id', 'status']);
    }

    /**
     * User can own multiple companies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ownedCompanies()
    {
        return $this->companies()
            ->where('role_id', Role::findByName(RoleType::OWNER)->id);
    }

    // scopes

    /**
     * Loading availabilities relationship with date constraints.
     *
     * @param $query
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param $companyId
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeWithAvailabilities(
        $query,
        Carbon $startDate,
        Carbon $endDate,
        $companyId
    ) {
        return $query->with([
            'availabilities' => fn ($query) => $query->companyId((int) $companyId)->inPeriodDate($startDate, $endDate),
        ]);
    }

    public function scopeHasAvailabilities(
        Builder $query,
        Carbon $startDate,
        Carbon $endDate,
        $companyId
    ) {
        return $query->whereHas('availabilities', fn ($query) => $query->companyId((int) $companyId)->inPeriodDate($startDate, $endDate));
    }


    public function scopeDoesntHaveAvailabilities(
        Builder $query,
        Carbon $startDate,
        Carbon $endDate,
        $companyId
    ) {
        return $query->whereDoesntHave('availabilities', fn ($query) => $query->companyId((int) $companyId)->inPeriodDate($startDate, $endDate));
    }
    /**
     * Loading users by ids.
     *
     * @param $query
     * @param array $users_ids
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeByIds($query, array $users_ids)
    {
        return $query->whereIn('id', $users_ids);
    }

    public function scopeBySelectedCompanyDepartment($query, int $selected_company_id, string $department)
    {
        return $query->whereHas('userCompanies', function ($query) use ($selected_company_id, $department) {
            $query->where('company_id', $selected_company_id)
                ->where('status', UserCompanyStatus::APPROVED)
                ->where('department', $department);
        });
    }

    public function scopeWithSelectedUserCompany(Builder $query, int $selected_company_id)
    {
        return $query->with([
            'userCompanies' => function ($query) use ($selected_company_id) {
                return $query->where('company_id', $selected_company_id)
                    ->where('status', UserCompanyStatus::APPROVED);
            },
        ]);
    }

    public function scopeInSelectedCompany(Builder $query, int $selected_company_id, ?string $role=null): Builder
    {
        return $query->whereHas('companies', function ($query) use ($selected_company_id, $role) {
            $query->where('companies.id', $selected_company_id)
                ->where('status', UserCompanyStatus::APPROVED);

            if ($role) {
                $query->where('role_id', Role::findByName($role)->id);
            }
        });
    }

    // accessors, mutators

    // functions

    /**
     * Set user's system role.
     */
    public function setSystemRole()
    {
        if ($this->is_superadmin) {
            $this->system_role = RoleType::SYSTEM_ADMIN;
        } else {
            $this->system_role = RoleType::SYSTEM_USER;
        }
    }

    /**
     * Get role attribute (system role).
     *
     * @return string
     */
    public function getRoleAttribute()
    {
        return $this->system_role;
    }

    /**
     * Set selected company id for user.
     *
     * @param int $companyId
     * @param Role|null $role
     */
    public function setSelectedCompany($companyId, Role $role = null)
    {
        $this->selected_company_id = $companyId;
        $this->selected_company_role = $role;
    }

    /**
     * Set selected project for user.
     *
     * @param Project $project
     */
    public function setSelectedProject(Project $project)
    {
        $this->selected_project = $project;
    }

    /**
     * Get selected company id.
     *
     * @return null|int
     */
    public function getSelectedCompanyId()
    {
        return (int) $this->selected_company_id;
    }

    /**
     * @return mixed|null
     */
    public function getCurrentCompanyRole()
    {
        // if company is selected by user, we will assume initially this role will be used
        if ($this->selectedUserCompany) {
            if ($this->hasCustomApiRoleForRequest()) {
                // if custom role was used, we will use this role
                return $this->selected_company_role->name;
            }
            if ($this->selectedUserCompany->role) {
                // otherwise valid company role is assigned to user
                return $this->selectedUserCompany->role->name;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRoles()
    {
        $roles = [$this->role];

        $additional_role = $this->getCurrentCompanyRole();

        // if project is selected by user (it does not apply to all project actions), we will assign
        // him project role. But if he is not assigned to this project we won't assign him any
        // company or project role
        if ($this->selected_project) {
            $selected = $this->selectedProjectsUser->where('project_id', $this->selected_project->id)->first();

            if ($selected && $selected->role) {
                $additional_role = $selected->role->name;
            } else {
                $additional_role = null;
            }
        }

        if ($additional_role) {
            $roles[] = $additional_role;
        }

        //check blockade company by limits in package
        if ($this->selectedUserCompany && null !== $this->selectedUserCompany->company->blockade_company) {
            if (count($roles) == 2 && $roles[1] != RoleType::OWNER) {
                $roles = [$this->role];
            }
        }

        return $roles;
    }

    /**
     * Get role that user has in given company.
     *
     * @param Company $company
     *
     * @return Role|null
     */
    public function getCompanyRole(Company $company)
    {
        if ($user_company = $this->userCompanies()->inCompany($company)->first()) {
            return $user_company->role;
        }

        return $user_company;
    }

    /**
     * User has assigned multiple user companies (no matter of status).
     *
     * @return HasMany
     */
    public function userCompanies()
    {
        return $this->hasMany(UserCompany::class, 'user_id');
    }

    /**
     * User has assigned multiple user involved.
     */
    public function involved(): HasMany
    {
        return $this->hasMany(Involved::class, 'user_id');
    }

    /**
     * User has one selected user company at one time (only approved status).
     *
     * @return HasOne
     */
    public function selectedUserCompany()
    {
        return $this->hasOne(UserCompany::class, 'user_id')
            ->inCompany($this)
            ->where('status', UserCompanyStatus::APPROVED);
    }

    /**
     * Verify if user is admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole(RoleType::ADMIN);
    }

    /**
     * Verify if user is admin.
     *
     * @return bool
     */
    public function isOwner()
    {
        return (bool) in_array(RoleType::OWNER, $this->getRoles());
    }

    /**
     * Verify if user is admin or owner.
     *
     * @return bool
     */
    public function isOwnerOrAdmin()
    {
        return collect($this->getRoles())->intersect([RoleType::OWNER, RoleType::ADMIN])
            ->isNotEmpty();
    }

    /**
     * Verify if user is admin or owner.
     *
     * @return bool
     */
    public function isOwnerOrAdminInCurrentCompany()
    {
        $role = $this->getCurrentCompanyRole();

        return $role == RoleType::OWNER || $role == RoleType::ADMIN;
    }

    /**
     * Verify if user is admin.
     *
     * @return bool
     */
    public function isSystemAdmin()
    {
        return (bool) in_array(RoleType::SYSTEM_ADMIN, $this->getRoles());
    }

    /**
     * Find user by e-mail.
     *
     * @param string $email
     * @param bool $soft
     *
     * @return User|null
     */
    public static function findByEmail($email, $soft = true)
    {
        $query = self::where('email', $email);
        if ($soft) {
            return $query->first();
        }

        return $query->firstOrFail();
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     *
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Verify whether user is activated.
     *
     * @return bool
     */
    public function isActivated()
    {
        return (bool) $this->activated;
    }

    /**
     * Activate user account.
     */
    public function activate()
    {
        $this->activated = true;
        $this->save();
    }

    /**
     * User has one selected company at one time.
     *
     * @return Company
     * @throws AuthorizationException
     */
    public function selectedCompany()
    {
        if (empty($this->getSelectedCompanyId()) || empty($this->selectedUserCompany)) {
            throw new AuthorizationException();
        }

        return $this->selectedUserCompany->company;
    }

    /**
     * User can be assigned to many files.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function files()
    {
        return $this->morphedByMany(File::class, 'permissionable', 'permission_user')
            ->withTimestamps();
    }

    /**
     * User can be assigned to many pages in Knowledge.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function knowledgePages()
    {
        return $this->morphedByMany(KnowledgePage::class, 'permissionable', 'permission_user');
    }

    /**
     * Get user role ID in project.
     *
     * @param Project $project
     *
     * @return int
     */
    public function getRoleInProject($project)
    {
        $user_project = $this->projects->find($project->id);

        if (! $user_project) {
            return;
        }

        return $user_project->pivot->role_id;
    }

    /**
     * Verify if user is admin/owner in project.
     *
     * @param Project $project
     *
     * @return bool
     */
    public function managerInProject(Project $project)
    {
        return $this->hasProjectRole($project, [RoleType::ADMIN, RoleType::OWNER]);
    }

    public function clientInProject($project)
    {
        return $this->hasProjectRole($project, RoleType::CLIENT);
    }

    /**
     * Verify whether use is assigned to given project.
     *
     * @param Project $project
     *
     * @return bool
     */
    public function isAssignedToProject(Project $project)
    {
        return $project->users()->where('user_id', $this->id)->first() !== null;
    }

    /**
     * Verify whether user is assigned to given company with Approved status.
     *
     * @param Company $company
     *
     * @return bool
     */
    public function isApprovedInCompany(Company $company)
    {
        return $company->users()->where('user_id', $this->id)
                ->where('status', UserCompanyStatus::APPROVED)->first() !== null;
    }

    /**
     * @inheritdoc
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @inheritdoc
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Custom Users Collection.
     *
     * @param array $models
     * @return UsersCollection|\Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new UsersCollection($models);
    }

    /**
     * Verify whether user has any of given roles for project.
     *
     * @param Project $project
     * @param array|string $roles
     *
     * @return bool
     */
    public function hasProjectRole(Project $project, $roles)
    {
        $roles = (array) $roles;

        $project_role = Role::find($this->getRoleInProject($project));
        if (! $project_role) {
            return false;
        }

        $project_role_name = $project_role->name;

        foreach ($roles as $role) {
            if ($project_role_name == $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * User has one selected project user at one time.
     *
     * @return HasMany
     */
    public function selectedProjectsUser()
    {
        return $this->hasMany(ProjectUser::class, 'user_id');
        //   ->where('project_id', $this->selected_project->id);
    }

    /**
     * Verify whether custom API request role was selected for current request.
     *
     * @return bool
     */
    public function hasCustomApiRoleForRequest()
    {
        return $this->selected_company_role && collect([
                RoleType::API_USER,
                RoleType::API_COMPANY,
            ])->containsStrict($this->selected_company_role->name);
    }
}
