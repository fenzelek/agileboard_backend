<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers;

use App\Models\Db\ErrorLog;
use App\Models\Db\Package;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class ErrorLogControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $now;
    protected $employee;
    protected $company;
    protected $errors;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->employee = factory(User::class)->create();

        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::ADMIN, Package::PREMIUM);
        $this->assignUsersToCompany(
            $this->employee->get(),
            $this->company,
            RoleType::EMPLOYEE
        );
        $this->createErrorLogs();
    }

    /** @test */
    public function index_it_shows_errors_list_with_success()
    {
        $this->get('errors?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure($this->errors_response_structure());

        $response = $this->response->getData()->data;
        $this->assertCount(5, $response);
    }

    /** @test */
    public function index_it_shows_errors_list_with_user_filer_with_success()
    {
        $this->get(
            'errors?selected_company_id=' . $this->company->id
            . '&user_id=' . $this->user->id
        )->seeStatusCode(200)
            ->seeJsonStructure($this->errors_response_structure());

        $response = $this->response->getData()->data;
        $this->assertCount(2, $response);
    }

    /** @test */
    public function index_it_shows_errors_list_with_json_filer_with_success()
    {
        $this->get(
            'errors?selected_company_id=' . $this->company->id
            . '&request[]=one'
            . '&request[]=two'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->errors_response_structure());

        $response = $this->response->getData()->data;
        $this->assertCount(3, $response);
    }

    /** @test */
    public function index_it_shows_errors_list_with_url_filer_with_success()
    {
        $this->get(
            'errors?selected_company_id=' . $this->company->id
            . '&url=receipts'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->errors_response_structure());

        $response = $this->response->getData()->data;
        $this->assertCount(2, $response);
    }

    /** @test */
    public function index_it_shows_errors_list_with_all_filers_with_success()
    {
        $this->get(
            'errors?selected_company_id=' . $this->company->id
            . '&user_id=' . $this->user->id
            . '&request[]=one'
            . '&request[]=two'
            . '&url=receipts'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->errors_response_structure());

        $response = $this->response->getData()->data;
        $this->assertCount(1, $response);
    }

    /** @test */
    public function index_it_verifies_request_and_response()
    {
        $this->get(
            'errors?selected_company_id=' . $this->company->id
            . '&user_id=' . $this->user->id
            . '&request[]= one '
            . '&request[]=two '
            . '&url=receipts'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->errors_response_structure());

        $response = $this->response->getData()->data;
        $this->assertCount(1, $response);

        $this->assertEquals('I can pass all tests. One two.', $response[0]->request);
        $this->assertEquals('I am response', $response[0]->response);
    }

    /** @test */
    public function index_employee_can_access_error_list()
    {
        $this->be($this->employee);

        $this->get('errors?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure($this->errors_response_structure());

        $response = $this->response->getData()->data;
        $this->assertCount(5, $response);
    }

    /** @test */
    public function index_developer_role_cant_access()
    {
        $developer = factory(User::class)->create();
        $this->assignUsersToCompany(
            $developer->get(),
            $this->company,
            RoleType::DEVELOPER
        );
        $this->be($developer);

        $this->get('errors?selected_company_id=' . $this->company->id)
            ->seeStatusCode(401);
    }

    /** @test */
    public function index_it_returns_empty_list()
    {
        $this->get(
            'errors?selected_company_id=' . $this->company->id
            . '&user_id=' . $this->user->id
            . '&request[]=oane'
            . '&request[]=tawo'
        )->seeStatusCode(200);

        $response = $this->response->getData()->data;
        $this->assertCount(0, $response);
    }

    /** @test */
    public function index_it_throws_error_if_url_is_wrong()
    {
        $this->get(
            'errors?selected_company_id=' . $this->company->id
            . '&url=receiptsss'
        )->seeStatusCode(422);

        $this->verifyValidationResponse(['url']);
    }

    /** @test */
    public function destroy_it_deletes_all_errors_in_company_as_admin_with_success()
    {
        $this->assertCount(6, ErrorLog::all());

        $this->delete('errors?selected_company_id=' . $this->company->id)
            ->seeStatusCode(204);

        // Only error log not in company wasn't deleted
        $this->assertCount(1, ErrorLog::all());
        // Checking count of deleted error logs
        $this->assertCount(5, ErrorLog::onlyTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_all_errors_in_company_as_employee_with_success()
    {
        $this->be($this->employee);

        $this->assertCount(6, ErrorLog::all());

        $this->delete('errors?selected_company_id=' . $this->company->id)
            ->seeStatusCode(204);

        // Only error log not in company wasn't deleted
        $this->assertCount(1, ErrorLog::all());
        // Checking count of deleted error logs
        $this->assertCount(5, ErrorLog::onlyTrashed()->get());
    }

    protected function createErrorLogs()
    {
        // Create 6 error logs but only 5 in company
        $this->errors = factory(ErrorLog::class)->create();
        $this->errors = factory(ErrorLog::class)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'request' => 'I can pass only user id test.',
            'url' => '',
            'response' => 'I am response',
        ]);
        $this->errors = factory(ErrorLog::class)->create([
            'company_id' => $this->company->id,
            'user_id' => (int) $this->user->id + 1,
            'request' => 'I can pass only json test, one.',
            'url' => '',
            'response' => 'I am response',
        ]);
        $this->errors = factory(ErrorLog::class)->create([
            'company_id' => $this->company->id,
            'user_id' => (int) $this->user->id + 1,
            'request' => 'I can pass only json test, two.',
            'url' => '',
            'response' => 'I am response',
        ]);
        $this->errors = factory(ErrorLog::class)->create([
            'company_id' => $this->company->id,
            'user_id' => (int) $this->user->id + 1,
            'request' => 'I can pass only url test.',
            'url' => 'http://someDomain.xx/receipts/',
            'response' => 'I am response',
        ]);
        $this->errors = factory(ErrorLog::class)->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'request' => 'I can pass all tests. One two.',
            'url' => 'http://someDomain.xx/receipts/',
            'response' => 'I am response',
        ]);
    }

    protected function errors_response_structure()
    {
        return [
            'data' => [
                [
                    'id',
                    'company_id',
                    'user_id',
                    'transaction_number',
                    'url',
                    'method',
                    'request',
                    'status_code',
                    'response',
                    'request_date',
                    'created_at',
                    'updated_at',
                    'user' => [
                        'data' => [
                            'id',
                            'email',
                            'first_name',
                            'last_name',
                            'avatar',
                            'activated',
                            'deleted',
                        ],
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                    'links',
                ],
            ],
        ];
    }
}
