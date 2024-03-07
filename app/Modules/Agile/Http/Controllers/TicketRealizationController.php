<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Http\Resources\ProjectColorInfo;
use App\Http\Resources\TicketShort;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use Auth;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Agile\Http\Requests\TicketRealizationIndex;
use Carbon\Carbon;

class TicketRealizationController extends Controller
{
    /**
     * List users with realization tickets.
     *
     * @param TicketRealizationIndex $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(TicketRealizationIndex $request, User $user, Role $role)
    {
        $startDate = Carbon::parse($request->input('from'))->startOfWeek();
        $endDate = with(clone ($startDate))
            ->addDays($request->input('limit', 10) - 1);
        $company_id = Auth::user()->getSelectedCompanyId();

        $users = $user->active()
            ->allowed(null, [$role->findByName(RoleType::CLIENT)->id])
            ->with(['ticketRealization' => function ($q) use ($startDate, $endDate, $company_id) {
                $q->where(function ($q) use ($startDate, $endDate) {
                    $q->where(function ($qw) use ($startDate, $endDate) {
                        $qw->where('start_at', '>=', $startDate);
                        $qw->where('start_at', '<=', $endDate);
                    });
                    $q->orWhere(function ($qw) use ($startDate, $endDate) {
                        $qw->where('end_at', '>=', $startDate);
                        $qw->where('end_at', '<=', $endDate);
                    });
                    $q->orWhere(function ($qw) use ($startDate, $endDate) {
                        $qw->where('start_at', '<', $startDate);
                        $qw->where('end_at', '>', $endDate);
                    });
                });
                $q->whereHas('ticket', function ($q) use ($company_id) {
                    $q->whereHas('project', function ($qw) use ($company_id) {
                        $qw->where('company_id', $company_id);
                    });
                });
                $q->with('ticket.project');
            }])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return ApiResponse::responseOk(
            $users,
            200,
            [
                'date_start' => $startDate->format('Y-m-d'),
                'date_end' => $endDate->format('Y-m-d'),
            ],
            [],
            0,
            [
                Ticket::class => TicketShort::class,
                Project::class => ProjectColorInfo::class,
            ]
        );
    }
}
