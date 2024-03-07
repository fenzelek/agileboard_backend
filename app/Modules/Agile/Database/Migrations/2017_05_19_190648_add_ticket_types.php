<?php

use App\Models\Db\TicketType;
use Illuminate\Database\Migrations\Migration;

class AddTicketTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $type_data = [
            [
                'id' => 1,
                'name' => 'Bug',
            ],
            [
                'id' => 2,
                'name' => 'Task',
            ],
        ];

        foreach ($type_data as $item) {
            $invoice_format = new TicketType();
            $invoice_format->id = $item['id'];
            $invoice_format->name = $item['name'];
            $invoice_format->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        TicketType::truncate();
    }
}
