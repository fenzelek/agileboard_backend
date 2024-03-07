<?php

return [
    /**
     * Super roles - in role mode, those roles will be automatically added to
     * other roles, and in permission mode for those roles won't
     * be made any detailed checks - in case if user is assigned to any of those
     * roles it will be assumed he has permission. If you leave it empty, no
     * super roles will be used.
     */
    'super_roles' => [
        'admin',
    ],

    /**
     * Name of virtual role for not logged user.
     */
    'guest_role_name' => 'anonymous',

    /**
     * Available permissions for roles (those are by default used by
     * PermissionConfigHandler unless you create custom handler that will read
     * them for example from database).
     */
    'permissions' => [

        /**
         * List of all available permissions in system. You should keep it
         * up-to-date. Format of those permissions is:
         * name of group in your Policy class . controller method name.
         */
        'available' => [
            'sample.store',
            // you can remove this - this is only sample permission
        ],

        /**
         * Assignment of above permissions to user roles
         * If for any role you set only '*' as permission it means all
         * permissions will be available for this role.
         */
        'roles' => [
            /**
             * Admin role.
             */
            'admin' => [
                '*', // all permissions (don't add anything into this array)
            ],

            /**
             * This should match the value you set as `guest_role_name`.
             * It contains allowed permissions for not logged users.
             */
            'anonymous' => [

            ],
            /**
             * Below you can specify any other roles permissions.
             */
            'user' => [
                // you can remove this - this is only sample permission
                'sample.store',
            ],
        ],
    ],

    /**
     * Module bindings (don't touch them unless you want write custom permission handling).
     */
    'bindings' => [
        App\Services\Mnabialek\LaravelAuthorize\Contracts\Permissionable::class => App\Services\Mnabialek\LaravelAuthorize\Services\Permission::class,
        App\Services\Mnabialek\LaravelAuthorize\Contracts\PermissionHandler::class => App\Services\Mnabialek\LaravelAuthorize\Services\ConfigPermissionHandler::class,
    ],
];
