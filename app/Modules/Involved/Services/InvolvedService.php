<?php

declare(strict_types=1);

namespace App\Modules\Involved\Services;

use App\Interfaces\Involved\IHasInvolved;
use App\Interfaces\Involved\IInvolvedRequest;
use App\Models\Db\User;
use Illuminate\Support\Collection;

class InvolvedService
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /** @return Collection|User[] */
    public function getInvolvedUsers(IHasInvolved $model): Collection
    {
        return $this->user->newQuery()
            ->join('involved', 'involved.user_id', '=', 'users.id')
            ->where('involved.source_id', '=', $model->getKey())
            ->where('involved.source_type', '=', $model->getMorphClass())
            ->get();
    }

    public function syncInvolved(IInvolvedRequest $request, IHasInvolved $model)
    {
        $model->involved()->delete();
        $model->involved()->createMany($this->prepareToInsert($request));
    }

    public function deleteInvolved(IHasInvolved $model)
    {
        $model->involved()->delete();
    }

    public function getNewInvolvedIds(IInvolvedRequest $request, IHasInvolved $model): Collection
    {
        $involved_ids_in_database = $this->getInvolvedIdsInDatabase($request, $model);
        return collect(array_diff($request->getInvolvedIds(), $involved_ids_in_database)) ;
    }

    /** @return int[] */
    private function getInvolvedIdsInDatabase(IInvolvedRequest $request, IHasInvolved $model): array
    {
        return $model->involved()
            ->whereIn('user_id', $request->getInvolvedIds())
            ->select('user_id')
            ->pluck('user_id')
            ->toArray();
    }

    private function prepareToInsert(IInvolvedRequest $request): Collection
    {
        $involvedIds = new Collection();
        foreach ($request->getInvolvedIds() as $user_id) {
            $involvedIds->push(
                ['company_id' => $request->getSelectedCompanyId(),
                'project_id' => $request->getProjectId(),
                'user_id' => $user_id,]
            );
        }

        return $involvedIds;
    }
}
