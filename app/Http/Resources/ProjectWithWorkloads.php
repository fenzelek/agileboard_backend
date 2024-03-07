<?php

namespace App\Http\Resources;

class ProjectWithWorkloads extends AbstractResource
{
    protected $fields = ['id', 'name', 'color', 'workloads'];

    protected $ignoredRelationships = [
        'sprints',
    ];
}
