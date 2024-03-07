<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectPermissionController;

trait TestTrait
{
    /**
     * @return array
     */
    protected function getResponseStructure()
    {
        return [
            'data' => [
                'ticket_create' => [
                    'roles' => [
                        '*' => ['name', 'value'],
                    ],
                    'relations' => [
                        '*' => ['name', 'value'],
                    ],
                ],
                'ticket_update' => [
                    'roles' => [
                        '*' => ['name', 'value'],
                    ],
                    'relations' => [
                        '*' => ['name', 'value'],
                    ],
                ],
                'ticket_destroy' => [
                    'roles' => [
                        '*' => ['name', 'value'],
                    ],
                    'relations' => [
                        '*' => ['name', 'value'],
                    ],
                ],
                'ticket_comment_create' => [
                    'roles' => [
                        '*' => ['name', 'value'],
                    ],
                    'relations' => [
                        '*' => ['name', 'value'],
                    ],
                ],
                'ticket_comment_update' => [
                    'roles' => [
                        '*' => ['name', 'value'],
                    ],
                    'relations' => [
                        '*' => ['name', 'value'],
                    ],
                ],
                'ticket_comment_destroy' => [
                    'roles' => [
                        '*' => ['name', 'value'],
                    ],
                    'relations' => [
                        '*' => ['name', 'value'],
                    ],
                ],
                'owner_ticket_show' => [
                    '*' => ['name', 'value'],
                ],
                'admin_ticket_show' => [
                    '*' => ['name', 'value'],
                ],
                'developer_ticket_show' => [
                    '*' => ['name', 'value'],
                ],
                'client_ticket_show' => [
                    '*' => ['name', 'value'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'ticket_create' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_update' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_destroy' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_comment_create' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_comment_update' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                    ['name' => 'reporter', 'value' => true],
                ],
            ],
            'ticket_comment_destroy' => [
                'roles' => [
                    ['name' => 'owner', 'value' => true],
                    ['name' => 'admin', 'value' => true],
                ],
                'relations' => [
                ],
            ],
            'owner_ticket_show' => [
                ['name' => 'all', 'value' => false],
                ['name' => 'reporter', 'value' => false],
                ['name' => 'assigned', 'value' => true],
                ['name' => 'not_assigned', 'value' => false],
            ],
            'admin_ticket_show' => [
                ['name' => 'all', 'value' => false],
                ['name' => 'reporter', 'value' => true],
                ['name' => 'assigned', 'value' => false],
                ['name' => 'not_assigned', 'value' => false],
            ],
            'user_ticket_show' => [
                ['name' => 'all', 'value' => false],
                ['name' => 'reporter', 'value' => false],
                ['name' => 'assigned', 'value' => false],
                ['name' => 'not_assigned', 'value' => true],
            ],
            'client_ticket_show' => [
                ['name' => 'all', 'value' => true],
                ['name' => 'reporter', 'value' => true],
                ['name' => 'assigned', 'value' => true],
                ['name' => 'not_assigned', 'value' => true],
            ],
        ];
    }
}
