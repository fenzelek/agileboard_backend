<?php

namespace App\Modules\Integration\Services\TimeTracking\Processor;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\User;
use App\Modules\Integration\Services\TimeTracking\UserMatcher;
use Illuminate\Support\Collection;
use App\Models\Other\Integration\TimeTracking\User as TimeTrackingObjectUser;

class UsersProcessor
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Collection
     */
    protected $mappings;

    /**
     * @var UserMatcher
     */
    protected $user_matcher;

    /**
     * UsersProcessor constructor.
     *
     * @param User $user
     * @param UserMatcher $user_matcher
     */
    public function __construct(User $user, UserMatcher $user_matcher)
    {
        $this->mappings = collect();
        $this->user = $user;
        $this->user_matcher = $user_matcher;
    }

    /**
     * Save users.
     *
     * @param Integration $integration
     * @param Collection $users
     *
     * @return Collection
     */
    public function save(Integration $integration, Collection $users)
    {
        $users->each(function ($user) use ($integration) {
            /** @var TimeTrackingObjectUser $user */
            $user_model = $this->user->where('integration_id', $integration->id)
                ->where('external_user_id', $user->getExternalId())->first();

            // if user already exists we will update its name and e-mail
            if ($user_model) {
                $user_model->update([
                    'external_user_email' => $user->getExternalEmail(),
                    'external_user_name' => $user->getExternalName(),
                ]);
                // and we try to find user match
                $this->user_matcher->process($user_model);
            } else {
                // otherwise new user will be created
                $user_model = $this->user->create([
                    'integration_id' => $integration->id,
                    'user_id' => $this->findMatchingUserId($user, $integration),
                    'external_user_id' => $user->getExternalId(),
                    'external_user_email' => $user->getExternalEmail(),
                    'external_user_name' => $user->getExternalName(),
                ]);
            }

            $this->mappings->put($user->getExternalId(), $user_model->id);
        });

        return $this->mappings;
    }

    /**
     * Get id of system user based on given user object.
     *
     * @param TimeTrackingObjectUser $user
     * @param $integration
     *
     * @return int|null
     */
    protected function findMatchingUserId(TimeTrackingObjectUser $user, $integration)
    {
        return $this->user_matcher->findMatchingUserId($user->getExternalEmail(), $integration);
    }
}
