<?php

namespace App\Http\Resources;

use App\Models\Db\Project;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @property Project[] $resource
 */
class YourProjects extends ResourceCollection
{
    public $collects = YourProject::class;

}
