<?php

namespace App\Modules\TimeTracker\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Db\User;
use App\Modules\TimeTracker\Http\Requests\Contracts\GetScreenshotsQueryData;
use App\Modules\TimeTracker\Http\Requests\GetOwnScreenshotsRequest;
use App\Modules\TimeTracker\Http\Requests\GetScreenshotsQuery;
use App\Modules\TimeTracker\Http\Resources\ActivityCollection;
use App\Modules\TimeTracker\Services\Screenshots;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

class ScreenshotController extends Controller
{
    public function index(GetScreenshotsQuery $request, Screenshots $service)
    {
        $screenshots = $service->get($request);

        return new ActivityCollection($screenshots);
    }

    public function indexOwn(GetOwnScreenshotsRequest $request, Screenshots $service, Guard $guard)
    {
        $screenshots_query_data = $this->provideScreensQueryData($request, $guard->user());
        $screenshots = $service->get($screenshots_query_data);

        return new ActivityCollection($screenshots);
    }

    /**
     * @param GetOwnScreenshotsRequest $request
     * @param User|Authenticatable $user
     * @return GetScreenshotsQueryData
     */
    private function provideScreensQueryData(GetOwnScreenshotsRequest $request, User $user): GetScreenshotsQueryData
    {
        return new class($request, $user) implements GetScreenshotsQueryData {
            private GetOwnScreenshotsRequest $request;
            private User $user;

            public function __construct(GetOwnScreenshotsRequest $request, User $user)
            {
                $this->request = $request;
                $this->user = $user;
            }

            public function getDate(): string
            {
                return $this->request->getDate();
            }

            public function getUserId(): int
            {
                return $this->user->id;
            }

            public function getSelectedCompanyId(): int
            {
                return $this->request->getSelectedCompanyId();
            }

            public function getProjectId(): ?int
            {
                return $this->request->getProjectId();
            }
        };
    }
}
