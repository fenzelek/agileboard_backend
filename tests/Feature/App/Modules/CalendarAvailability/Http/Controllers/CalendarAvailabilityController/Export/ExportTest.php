<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers\CalendarAvailabilityController\Export;

use App\Models\Db\Company;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use DatabaseTransactions;
    use ExportTrait;

    private Company $company;

    /**
     * @test
     * @dataProvider authorizedRoleTypeProvider
     */
    public function export_WhenUserHasRoleAllowedToExport_shouldReturnSuccess(string $role_type): void
    {
        //Given
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00'));
        $this->prepareData($role_type);
        $params = [
            'selected_company_id' => $this->company->id,
            'from' => Carbon::now()->startOfDay()->toDateString(),
        ];

        //When
        $response = $this->getJson(
            route('availabilities.export') . '?' . Arr::query($params)
        );

        //Then
        $response->assertOk();
        $response->assertDownload();
    }

    /**
     * @test
     * @dataProvider unauthorizedRoleTypeProvider
     */
    public function export_WhenUserHasNotRoleAllowedToExport_shouldReturnUnauthorized(string $role_type): void
    {
        //Given
        $this->company = $this->createCompanyWithRole($role_type);
        $params = [
            'selected_company_id' => $this->company->id,
            'from' => Carbon::now()->startOfDay()->toDateString(),
        ];

        //When
        $response = $this->getJson(
            route('availabilities.export') . '?' . Arr::query($params)
        );

        //Then
        $response->assertUnauthorized();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
    }
}
