<?php

namespace Tests\Helpers;

use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use App\Models\Db\User;
use Illuminate\Support\Collection;

trait ResponseHelper
{
    /**
     * Get expected response array for User model.
     *
     * @param User $user
     *
     * @return array
     */
    protected function getExpectedUserResponse(User $user)
    {
        $data = $this->getExpectedUserSimpleResponse($user);
        $user = $user->fresh();

        $data['activated'] = (bool) $user->activated;
        $data['deleted'] = (bool) $user->deleted;

        return $data;
    }

    /**
     * Get simple user response (no activated and deleted columns).
     *
     * @param User $user
     *
     * @return array
     */
    protected function getExpectedUserSimpleResponse(User $user)
    {
        return array_only($user->fresh()->attributesToArray(), [
            'id',
            'email',
            'first_name',
            'last_name',
            'avatar',
        ]);
    }

    /**
     * Get expected response array for TimeTrackingUser model.
     *
     * @param TimeTrackingUser $user
     *
     * @return array
     */
    protected function getExpectedTimeTrackingUserResponse(TimeTrackingUser $user)
    {
        return $user->fresh()->attributesToArray();
    }

    /**
     * Allow to verify data in response (no matter of order of items). 
     *
     * @param array $expected_indexes Indexes of collection items that should be in response
     * @param Collection $expected_response_items Response array of all items
     */
    protected function verifyDataResponse(array $expected_indexes, Collection $expected_response_items)
    {
        $response = $this->decodeResponseJson()['data'];

        $this->assertEquals(count($expected_indexes), count($response));

        foreach ($expected_indexes as $expected_index) {
            $expected_response_item = $expected_response_items[$expected_index];
            $found = false;
            foreach ($response as $response_record) {
                if ($response_record['id'] == $expected_response_item['id']) {
                    $found = true;
                    $this->assertEquals($expected_response_item, $response_record);
                }
            }
            $this->assertTrue(
                $found,
                'Failed to found record with index=' . $expected_index . ' in response'
            );
        }
    }
}
