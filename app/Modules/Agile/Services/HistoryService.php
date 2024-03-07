<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\TicketType;
use App\Models\Db\User;
use App\Models\Db\Story;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HistoryService
{
    const TICKET = 'ticket';
    const TICKET_COMMENT = 'ticket_comment';

    /**
     * Get change from sync.
     *
     * @param $resource_id
     * @param $object_id
     * @param $object_type
     * @param $field_name
     * @param $before_ids
     * @param $after_ids
     */
    public static function sync($resource_id, $object_id, $object_type, $field_name, $before_ids, $after_ids)
    {
        foreach ($before_ids as $id) {
            if (! in_array($id, $after_ids)) {
                self::addToTable($resource_id, $object_id, $id, null, $object_type, $field_name);
            }
        }

        foreach ($after_ids as $id) {
            if (! in_array($id, $before_ids)) {
                self::addToTable($resource_id, $object_id, null, $id, $object_type, $field_name);
            }
        }
    }

    /**
     * Add history.
     *
     * @param $resource_id
     * @param $object_id
     * @param $object_type
     * @param $before
     * @param $after
     */
    public static function add($resource_id, $object_id, $object_type, $before, $after)
    {
        if ($before && $after) {
            foreach ($before as $name => $value) {
                self::addToTable($resource_id, $object_id, $value, $after[$name], $object_type, $name);
            }
        } elseif ($before) {
            foreach ($before as $name => $value) {
                self::addToTable($resource_id, $object_id, $value, null, $object_type, $name);
            }
        } elseif ($after) {
            foreach ($after as $name => $value) {
                self::addToTable($resource_id, $object_id, null, $value, $object_type, $name);
            }
        }
    }

    /**
     * Add to table.
     *
     * @param $resource_id
     * @param $object_id
     * @param $value_before
     * @param $value_after
     * @param $object_type
     * @param $name
     */
    private static function addToTable(
        $resource_id,
        $object_id,
        $value_before,
        $value_after,
        $object_type,
        $name
    ) {
        if ($field_id = HistoryField::getId($object_type, $name)) {
            History::create([
                'user_id' => Auth::id(),
                'resource_id' => $resource_id,
                'object_id' => $object_id,
                'field_id' => $field_id,
                'value_before' => $value_before,
                'label_before' => self::getLabel($value_before, $object_type, $name),
                'value_after' => $value_after,
                'label_after' => self::getLabel($value_after, $object_type, $name),
                'created_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Get label.
     *
     * @param $value
     * @param $object_type
     * @param $name
     *
     * @return null
     */
    private static function getLabel($value, $object_type, $name)
    {
        if ($object_type == self::TICKET) {
            switch ($name) {
                case 'sprint_id':
                    if ($value == 0) {
                        return 'Backlog';
                    }
                    $sprint = Sprint::find($value);

                    return $sprint ? $sprint->name : null;
                    break;
                case 'status_id':
                    return Status::findOrFail($value)->name;
                    break;
                case 'type_id':
                    return TicketType::findOrFail($value)->name;
                    break;
                case 'assigned_id':
                    $user = User::find($value);

                    return $user ? $user->first_name . ' ' . $user->last_name : null;
                    break;
                case 'reporter_id':
                    $user = User::find($value);

                    return $user ? $user->first_name . ' ' . $user->last_name : null;
                    break;
                case 'story_id':
                    $story = Story::find($value);

                    return $story ? $story->name : null;
                    break;
            }
        }

        return;
    }
}
