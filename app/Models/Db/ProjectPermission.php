<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\Model;

class ProjectPermission extends Model
{
    const DEFAULT_PERMISSIONS = [
        'ticket_create' => [
            'roles' => [
                ['name' => 'owner', 'value' => true],
                ['name' => 'admin', 'value' => true],
                ['name' => 'developer', 'value' => true],
                ['name' => 'client', 'value' => true],
            ],
            'relations' => [
            ],
        ],
        'ticket_update' => [
            'roles' => [
                ['name' => 'owner', 'value' => true],
                ['name' => 'admin', 'value' => true],
                ['name' => 'developer', 'value' => true],
                ['name' => 'client', 'value' => true],
            ],
            'relations' => [
                ['name' => 'reporter', 'value' => true],
                ['name' => 'assigned', 'value' => false],
            ],
        ],
        'ticket_destroy' => [
            'roles' => [
                ['name' => 'owner', 'value' => true],
                ['name' => 'admin', 'value' => true],
                ['name' => 'developer', 'value' => false],
                ['name' => 'client', 'value' => false],
            ],
            'relations' => [
                ['name' => 'reporter', 'value' => true],
                ['name' => 'assigned', 'value' => false],
            ],
        ],

        'ticket_comment_create' => [
            'roles' => [
                ['name' => 'owner', 'value' => true],
                ['name' => 'admin', 'value' => true],
                ['name' => 'developer', 'value' => true],
                ['name' => 'client', 'value' => true],
            ],
            'relations' => [
            ],
        ],
        'ticket_comment_update' => [
            'roles' => [
                ['name' => 'owner', 'value' => true],
                ['name' => 'admin', 'value' => true],
                ['name' => 'developer', 'value' => false],
                ['name' => 'client', 'value' => false],
            ],
            'relations' => [
                ['name' => 'user', 'value' => true], // owner of ticket comment
            ],
        ],
        'ticket_comment_destroy' => [
            'roles' => [
                ['name' => 'owner', 'value' => true],
                ['name' => 'admin', 'value' => true],
                ['name' => 'developer', 'value' => false],
                ['name' => 'client', 'value' => false],
            ],
            'relations' => [
                ['name' => 'user', 'value' => true], // owner of ticket comment
            ],
        ],
        'owner_ticket_show' => [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ],
        'admin_ticket_show' => [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ],
        'developer_ticket_show' => [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ],
        'client_ticket_show' => [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ],
    ];

    public $incrementing = false;
    protected $primaryKey = 'project_id';
    protected $guarded = [];
    protected $casts = [
        'ticket_create' => 'array',
        'ticket_update' => 'array',
        'ticket_destroy' => 'array',
        'ticket_comment_create' => 'array',
        'ticket_comment_update' => 'array',
        'ticket_comment_destroy' => 'array',
        'owner_ticket_show' => 'array',
        'admin_ticket_show' => 'array',
        'developer_ticket_show' => 'array',
        'user_ticket_show' => 'array',
        'client_ticket_show' => 'array',
    ];

    public function setOwnerTicketShowAttribute($value)
    {
        $this->attributes['owner_ticket_show'] = $this->checkAllSelection('owner_ticket_show', $value);
    }

    public function setAdminTicketShowAttribute($value)
    {
        $this->attributes['admin_ticket_show'] = $this->checkAllSelection('admin_ticket_show', $value);
    }

    public function setDeveloperTicketShowAttribute($value)
    {
        $this->attributes['developer_ticket_show'] = $this->checkAllSelection('developer_ticket_show', $value);
    }

    public function setClientTicketShowAttribute($value)
    {
        $this->attributes['client_ticket_show'] = $this->checkAllSelection('client_ticket_show', $value);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function canCreateTicket(User $user)
    {
        return $this->checkPermissions((array) $this->ticket_create, $user);
    }

    /**
     * @param User $user
     * @param Ticket $ticket
     *
     * @return bool
     */
    public function canUpdateTicket(User $user, Ticket $ticket)
    {
        return $this->checkPermissions((array) $this->ticket_update, $user, $ticket);
    }

    /**
     * @param User $user
     * @param Ticket $ticket
     *
     * @return bool
     */
    public function canDestroyTicket(User $user, Ticket $ticket)
    {
        return $this->checkPermissions((array) $this->ticket_destroy, $user, $ticket);
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function canCreateTicketComment(User $user)
    {
        return $this->checkPermissions((array) $this->ticket_comment_create, $user);
    }

    /**
     * @param User $user
     *
     * @param TicketComment $ticketComment
     *
     * @return bool
     */
    public function canUpdateTicketComment(User $user, TicketComment $ticketComment)
    {
        return $this->checkPermissions((array) $this->ticket_comment_update, $user, $ticketComment);
    }

    /**
     * @param User $user
     *
     * @param bool $convert
     *
     * @return array
     */
    public function getTicketShowPermissions(User $user, $convert = true)
    {
        $role_in_project = $this->project->getRole($user)->name ?? null;

        $permissions = [];

        if ($role_in_project == 'owner') {
            $permissions = $this->owner_ticket_show;
        } elseif ($role_in_project == 'admin') {
            $permissions = $this->admin_ticket_show;
        } elseif ($role_in_project == 'developer') {
            $permissions = $this->developer_ticket_show;
        } elseif ($role_in_project == 'client') {
            $permissions = $this->client_ticket_show;
        }

        if ($convert) {
            $permissions = $this->convertPermissions($permissions);
        }

        return $permissions;
    }

    /**
     * @param User $user
     *
     * @param TicketComment $ticketComment
     *
     * @return bool
     */
    public function canDestroyTicketComment(User $user, TicketComment $ticketComment)
    {
        return $this->checkPermissions((array) $this->ticket_comment_destroy, $user, $ticketComment);
    }

    /**
     * @param array $permissions
     * @param User $user
     * @param Model $model
     *
     * @return bool
     */
    private function checkPermissions(array $permissions, User $user, Model $model = null)
    {
        if (isset($permissions['roles'])) {
            foreach ($this->convertPermissions($permissions['roles']) as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        if (isset($permissions['relations']) && ! empty($model)) {
            foreach ($this->convertPermissions($permissions['relations']) as $relation) {
                $relation_field = $relation . '_id';
                if ($model->{$relation_field} === $user->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $permissions
     *
     * @return array
     */
    private function convertPermissions(array $permissions)
    {
        $converted = [];
        foreach ($permissions as $permission) {
            $permission = (object) $permission;
            if (isset($permission->value) && $permission->value == true) {
                $converted[] = $permission->name;
            }
        }

        return $converted;
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @return string
     */
    private function checkAllSelection($attribute, $value): string
    {
        $key_of_all = array_search('all', array_column($value, 'name'));
        if (empty($this->{$attribute}) || $this->{$attribute} === $value || $key_of_all === false) {
            return json_encode($value);
        }

        $prev_all = $this->{$attribute}[$key_of_all]['value'];
        $current_all = $value[$key_of_all]['value'];
        $prev_sum = $this->getPermissionSum($this->{$attribute});
        $current_sum = $this->getPermissionSum($value);
        $increase = $current_sum > $prev_sum;
        $expected_count = count($this->{$attribute}) - 1;

        if (abs($prev_sum - $current_sum) > 1) {
            return json_encode($value);
        }

        if ($increase && ($current_all || $current_sum == $expected_count)) {
            $value = $this->setValueToAll($value, true);
        } elseif (! $increase && ! $current_all && $prev_all) {
            $value = $this->setValueToAll($value, false);
        } elseif (! $increase && $current_all) {
            $value[$key_of_all]['value'] = false;
        }

        return json_encode($value);
    }

    /**
     * @param $permissions
     *
     * @return int
     */
    private function getPermissionSum($permissions)
    {
        $sum = 0;
        foreach ($permissions as $permission) {
            $sum += (int) $permission['value'];
        }

        return $sum;
    }

    /**
     * @param array $permissions
     * @param bool $value
     *
     * @return array
     */
    private function setValueToAll(array $permissions, bool $value)
    {
        foreach ($permissions as $k => $permission) {
            $permissions[$k]['value'] = $value;
        }

        return $permissions;
    }
}
