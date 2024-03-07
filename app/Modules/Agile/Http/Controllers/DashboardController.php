<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\WidgetDashboard;
use App\Modules\Agile\Services\DashboardService;
use Illuminate\Contracts\Auth\Guard;

class DashboardController extends Controller
{
    public function index(DashboardService $service, Guard $auth)
    {
        $widgets = $service->getWidgets($auth->user());

        return new WidgetDashboard($widgets);
    }
}
