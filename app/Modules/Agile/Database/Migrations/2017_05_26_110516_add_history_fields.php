<?php

use App\Models\Db\HistoryField;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddHistoryFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $type_data = [
            ['object_type' => 'ticket', 'field_name' => 'sprint_id'],
            ['object_type' => 'ticket', 'field_name' => 'status_id'],
            ['object_type' => 'ticket', 'field_name' => 'ticket_id'],
            ['object_type' => 'ticket', 'field_name' => 'name'],
            ['object_type' => 'ticket', 'field_name' => 'title'],
            ['object_type' => 'ticket', 'field_name' => 'type_id'],
            ['object_type' => 'ticket', 'field_name' => 'assigned_id'],
            ['object_type' => 'ticket', 'field_name' => 'reporter_id'],
            ['object_type' => 'ticket', 'field_name' => 'description'],
            ['object_type' => 'ticket', 'field_name' => 'estimate_time'],
            ['object_type' => 'ticket', 'field_name' => 'priority'],
            ['object_type' => 'ticket', 'field_name' => 'hidden'],
            ['object_type' => 'ticket', 'field_name' => 'created_at'],
            ['object_type' => 'ticket', 'field_name' => 'deleted_at'],
            ['object_type' => 'ticket', 'field_name' => 'story_id'],
            ['object_type' => 'ticket_comment', 'field_name' => 'user_id'],
            ['object_type' => 'ticket_comment', 'field_name' => 'text'],
            ['object_type' => 'ticket_comment', 'field_name' => 'created_at'],
        ];

        DB::transaction(function () use ($type_data) {
            foreach ($type_data as $item) {
                $field = new HistoryField();
                $field->object_type = $item['object_type'];
                $field->field_name = $item['field_name'];
                $field->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        HistoryField::truncate();
    }
}
