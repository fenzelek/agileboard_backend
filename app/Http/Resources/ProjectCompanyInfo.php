<?php

namespace App\Http\Resources;

class ProjectCompanyInfo extends AbstractResource
{
    protected $fields = ['id', 'company_id'];

    protected $ignoredRelationships = ['company'];
}
