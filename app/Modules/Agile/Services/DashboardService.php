<?php

namespace App\Modules\Agile\Services;

use App\Http\Resources\LastAddedList as LastAddedListResource;
use App\Http\Resources\YourProjects as YourProjectsResource;
use App\Http\Resources\YourTasks as YourTasksResource;
use App\Models\Db\User;
use App\Modules\Agile\Contracts\IWidget;
use App\Modules\Agile\Models\WidgetDTO;
use App\Modules\Agile\Services\Widgets\LastAdded;
use App\Modules\Agile\Services\Widgets\YourProjects;
use App\Modules\Agile\Services\Widgets\YourTasks;

class DashboardService
{
    private array $widgets = [
        YourProjectsResource::class => YourProjects::class,
        YourTasksResource::class => YourTasks::class,
        LastAddedListResource::class => LastAdded::class,
    ];

    /**
     * @param User $user
     *
     * @return WidgetDTO[]
     */
    public function getWidgets(User $user)
    {
        $widgets = [];
        foreach ($this->widgets as $resource => $widget) {
            /** @var IWidget $widget */
            $widget = new $widget();
            $widgets[$resource] = new WidgetDTO($widget->getName(), $widget->get($user));
        }

        return $widgets;
    }
}
