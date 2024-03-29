<?php

return [
    /*
     * Directory where new modules will be created
     */
    'directory' => 'app/Modules',

    /*
     * Namespace for new modules
     */
    'namespace' => 'App\\Modules',

    /*
     * Stubs settings
     */
    'stubs' => [
        /*
         * Path where all stubs groups are located
         */
        'path' => base_path('resources/stubs/modular'),

        /*
         * Default stub groups used for module and files - they must match
         * keys from stub_groups
         */
        'module_default_group' => 'default',
        'files_default_group' => 'submodule',
    ],

    /*
     * Creating migration settings
     */
    'module_migrations' => [
        /*
         * Available types of migrations stubs to use
         */
        'types' => [
            'default' => 'migration.php.stub',
            'create' => 'migration_create.php.stub',
            'edit' => 'migration_edit.php.stub',
        ],
        /*
         * Default migration type (if none specified)
         */
        'default_type' => 'default',

        /*
         * Path (inside module) where migrations file should be created
         */
        'path' => 'Database/Migrations',
    ],

    /*
     * Module seeding settings
     */
    'module_seeding' => [
        /*
         * Seeder filename
         */
        'file' => 'Database/Seeds/{class}DatabaseSeeder.php',

        /*
         * Seeder namespace (it will be automatically prefixed with modules
         * namespace)
         */
        'namespace' => 'Database\\Seeds',
    ],

    /*
     * Module routing settings
     */
    'module_routing' => [
        /*
         * General routing file path and name (inside module)
         */
        'file' => 'Http/routes.php',

        /*
         * API routing file path and name (inside module)
         */
        'api_file' => 'routes/api.php',

        /*
         * Web routing file path and name (inside module)
         */
        'web_file' => 'routes/web.php',

        /*
         * Routing group controller namespace (this namespace will be
         * automatically added to all controllers defined inside above routing
         * file)
         */
        'route_group_namespace' => 'Http\\Controllers',
    ],

    /*
     * Settings for module model factories
     */
    'module_factories' => [
        /*
         * Model factory file path and name (inside module)
         */
        'file' => 'Database/Factories/{class}ModelFactory.php',
    ],

    /*
     * Settings for module service providers
     */
    'module_service_providers' => [

        /*
         * Service provider file path and name (inside module)
         */
        'file' => 'Providers/{class}ServiceProvider.php',

        /*
         * Service provider namespace (it will be automatically prefixed with modules
         * namespace)
         */
        'namespace' => 'Providers',
    ],

    /*
     * Settings for module creation
     */
    'module_make' => [
        /*
         * Whether after creating new module this file should be filled with new
         * module name
         */
        'auto_add' => true,

        /*
         * Pattern what should be searched in this file to add here new module
         * (don't change it unless you know what you are doing)
         */
        'pattern' => "#(modules'\s*=>\s*\[\s*)(.*)(^\s*\/\/\s* end of modules \(don't remove this comment\)\s*])#sm",

        /*
         * Module template - what will be added in this file when new module
         * is created
         */
        'module_template' => "        '{class}' => ['active' => true, 'routes' => true,],\n",
    ],

    /*
     * List of available modules in format:
     * 'moduleName' => ['active' => true, 'routes' => true, 'factories' => true, 'provider' => true],
     * ('active', `routes' `factories`, `provider` are optional but when
     * used and filled correctly they will improve performance.
     *
     * If you fill them manually extra checks won't be done. So for example if
     * you set `provider` => false and this module has service provider, it
     * won't be loaded unless you change it to `provider` => true
     */
    'modules' => [
        'User' => ['active' => true, 'routes' => true],
        'CalendarAvailability' => ['active' => true, 'routes' => true],
        'Company' => ['active' => true, 'routes' => true],
        'Project' => ['active' => true, 'routes' => true],
        'Notification' => ['active' => true, 'routes' => true],
        'Contractor' => ['active' => true, 'routes' => true],
        'Sale' => ['active' => true, 'routes' => true],
        'SaleInvoice' => ['active' => true, 'routes' => true],
        'SaleReport' => ['active' => true, 'routes' => true],
        'SaleOther' => ['active' => true, 'routes' => true],
        'CashFlow' => ['active' => true, 'routes' => true],
        'Knowledge' => ['active' => true, 'routes' => true],
        'Agile' => ['active' => true, 'routes' => true],
        'Integration' => ['active' => true, 'routes' => true],
        'Gantt' => ['active' => true, 'routes' => true],
        'TimeTracker' => ['active' => true, 'routes' => true],
        'Interaction' => ['active' => true, 'routes' => false],
        'Involved' => ['active' => true, 'routes' => true,],
        // end of modules (don't remove this comment)
    ],

    /*
     * Here we define what directories and what files should be created for
     * each stub groups. By default directory is the same as stub group, however
     * we could define another using stub_directory (see submodule group)
     */
    'stubs_groups' => [
        'default' => [
            'directories' => [
                'Traits',
                'Services',
                'Http/Controllers',
                'Http/Requests',
                'Http/Middleware',
                'Database/Migrations',
                'Database/Seeds',
                'Database/Factories',
            ],
            'files' => [
                'Services/.gitkeep' => '.gitkeep.stub',
                'Middleware/.gitkeep' => '.gitkeep.stub',
                'Traits/.gitkeep' => '.gitkeep.stub',
                'Http/Controllers/.gitkeep' => '.gitkeep.stub',
                'Http/Requests/.gitkeep' => '.gitkeep.stub',
                'Database/Migrations/.gitkeep' => '.gitkeep.stub',
                'Database/Seeds/.gitkeep' => '.gitkeep.stub',
                'Database/Factories/.gitkeep' => '.gitkeep.stub',
                'Http/Controllers/{class}Controller.php' => 'Controller.php.stub',
                'Http/Requests/{class}.php' => 'Request.php.stub',
                'Http/routes.php' => 'routes.php.stub',
                'Database/Seeds/{class}DatabaseSeeder.php' => 'DatabaseSeeder.php.stub',
                'Database/Factories/{class}ModelFactory.php' => 'ModelFactory.php.stub',
            ],
        ],
        'submodule' => [
            'stub_directory' => 'default',
            'files' => [
                'Http/Controllers/{class}.php' => 'Controller.php.stub',
                'Database/Seeds/{class}DatabaseSeeder.php' => 'DatabaseSeeder.php.stub',
            ],
        ],
        'model' => [
            'stub_directory' => 'default',
            'files' => [
                'Models/{class}.php' => 'Model.php.stub',
            ],
        ],
        'controller' => [
            'stub_directory' => 'default',
            'files' => [
                'Http/Controllers/{class}.php' => 'Controller.php.stub',
            ],
        ],
        'service' => [
            'stub_directory' => 'default',
            'files' => [
                'Services/{class}.php' => 'Service.php.stub',
            ],
        ],
        'factory' => [
            'stub_directory' => 'default',
            'files' => [
                'Database/Factories/{class}ModelFactory.php' => 'ModelFactory.php.stub',
            ],
        ],
        'provider' => [
            'stub_directory' => 'default',
            'files' => [
                'Providers/{class}ServiceProvider.php' => 'ServiceProvider.php.stub',
            ],
        ],
        'exception' => [
            'stub_directory' => 'default',
            'files' => [
                'Exceptions/{class}Exception.php' => 'Exception.php.stub',
            ],
        ],
    ],

    /*
     * Separators for replacements
     */
    'separators' => [
        'start' => '{',
        'end' => '}',
    ],
];
