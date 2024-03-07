<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers;

use App\Models\Db\TicketType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class TicketTypeControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * This test is for checking API response structure.
     */
    public function testIndex()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->get('/ticket-types')
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [['id', 'name']],
            ])->isJson();
    }

    /** @test */
    public function index_response_data_correct()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        TicketType::whereRaw('1 = 1')->delete();

        $types = factory(TicketType::class, 5)->create();
        $this->get('/ticket-types')->assertResponseOk();

        $response_types = $this->decodeResponseJson()['data'];

        $this->assertSame(count($types), count($response_types));

        foreach ($types as $key => $role) {
            $role = $role->fresh();
            $this->assertSame($role->id, $response_types[$key]['id']);
            $this->assertSame($role->name, $response_types[$key]['name']);
        }
    }
}
