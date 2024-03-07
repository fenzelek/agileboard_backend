<?php

namespace App\Providers;

use App\Modules\Agile\Http\Controllers\DashboardController;
use App\Modules\Agile\Http\Controllers\ReportController;
use App\Modules\Agile\Http\Controllers\SprintController;
use App\Modules\Agile\Http\Controllers\StatusController;
use App\Modules\Agile\Http\Controllers\TicketCommentController;
use App\Modules\Agile\Http\Controllers\TicketController;
use App\Modules\Agile\Http\Controllers\TicketRealizationController;
use App\Modules\Agile\Http\Controllers\TicketTypeController;
use App\Modules\CalendarAvailability\Http\Controllers\CalendarAvailabilityController;
use App\Modules\CalendarAvailability\Http\Controllers\CalendarDayOffController;
use App\Modules\CashFlow\Http\Controllers\CashFlowController;
use App\Modules\Company\Http\Controllers\ClipboardController;
use App\Modules\Company\Http\Controllers\CompanyController;
use App\Modules\Company\Http\Controllers\CompanyServiceController;
use App\Modules\Company\Http\Controllers\CompanyServiceUnitController;
use App\Modules\Company\Http\Controllers\ModuleController;
use App\Modules\Company\Http\Controllers\PackageController;
use App\Modules\Company\Http\Controllers\PaymentController;
use App\Modules\Company\Http\Controllers\TaxOfficeController;
use App\Modules\Company\Http\Controllers\TokenController;
use App\Modules\Company\Http\Controllers\UserController as CompanyUserController;
use App\Modules\Company\Http\Controllers\InvitationController;
use App\Modules\Company\Http\Controllers\VatReleaseReasonController;
use App\Modules\Contractor\Http\Controllers\ContractorController;
use App\Modules\Integration\Http\Controllers\IntegrationController;
use App\Modules\Integration\Http\Controllers\IntegrationProviderController;
use App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;
use App\Modules\Integration\Http\Controllers\TimeTrackingProjectController;
use App\Modules\Integration\Http\Controllers\TimeTrackingUserController;
use App\Modules\Knowledge\Http\Controllers\KnowledgeDirectoryController;
use App\Modules\Knowledge\Http\Controllers\KnowledgePageCommentController;
use App\Modules\Knowledge\Http\Controllers\KnowledgePageController;
use App\Modules\Project\Http\Controllers\ProjectPermissionController;
use App\Modules\Project\Http\Controllers\StoryController;
use App\Modules\Sale\Http\Controllers\PaymentMethodsController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceCorrectionTypeController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceFilterController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceMarginProcedureController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceReverseChargeController;
use App\Modules\SaleInvoice\Http\Controllers\JpkController;
use App\Modules\SaleInvoice\Http\Controllers\JpkDetailsController;
use App\Modules\SaleOther\Http\Controllers\ErrorLogController;
use App\Modules\SaleOther\Http\Controllers\OnlineSaleController;
use App\Modules\SaleOther\Http\Controllers\ReceiptController;
use App\Modules\SaleReport\Http\Controllers\CashFlowReportController;
use App\Modules\SaleReport\Http\Controllers\InvoiceReportController;
use App\Modules\TimeTracker\Http\Controllers\ScreenshotController;
use App\Modules\TimeTracker\Http\Controllers\TimeTrackerController;
use App\Modules\User\Http\Controllers\AuthController;
use App\Modules\User\Http\Controllers\RoleController;
use App\Modules\User\Http\Controllers\UserController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceFormatController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceTypeController;
use App\Modules\SaleInvoice\Http\Controllers\InvoiceSettingsController;
use App\Modules\Sale\Http\Controllers\VatRateController;
use App\Modules\SaleReport\Http\Controllers\SaleReportController;
use App\Modules\SaleInvoice\Http\Controllers\InvoicePaymentsController;
use App\Modules\Project\Http\Controllers\ProjectController;
use App\Modules\Project\Http\Controllers\UserController as ProjectUserController;
use App\Modules\Project\Http\Controllers\FileController;
use App\Modules\Gantt\Http\Controllers\WorkloadController;
use App\Policies\AuthControllerPolicy;
use App\Policies\CalendarAvailabilityControllerPolicy;
use App\Policies\CalendarDayOffControllerPolicy;
use App\Policies\CashFlowControllerPolicy;
use App\Policies\ClipboardControllerPolicy;
use App\Policies\Company\TokenControllerPolicy;
use App\Policies\CompanyControllerPolicy;
use App\Policies\CompanyServiceControllerPolicy;
use App\Policies\CompanyServiceUnitControllerPolicy;
use App\Policies\CompanyUserControllerPolicy;
use App\Policies\DashboardControllerPolicy;
use App\Policies\Integration\IntegrationControllerPolicy;
use App\Policies\Integration\IntegrationProviderControllerPolicy;
use App\Policies\Integration\TimeTracking\ActivityControllerPolicy;
use App\Policies\Integration\TimeTracking\ProjectControllerPolicy as TimeTrackingProjectControllerPolicy;
use App\Policies\Integration\TimeTracking\UserControllerPolicy as TimeTrackingUserControllerPolicy;
use App\Policies\InvitationControllerPolicy;
use App\Policies\InvoiceControllerPolicy;
use App\Policies\InvoiceCorrectionTypeControllerPolicy;
use App\Policies\InvoiceFilterControllerPolicy;
use App\Policies\InvoiceMarginProcedureControllerPolicy;
use App\Policies\InvoicePaymentsControllerPolicy;
use App\Policies\InvoiceReportControllerPolicy;
use App\Policies\KnowledgePageCommentControllerPolicy;
use App\Policies\ModuleControllerPolicy;
use App\Policies\KnowledgeDirectoryControllerPolicy;
use App\Policies\OnlineSaleControllerPolicy;
use App\Policies\KnowledgePageControllerPolicy;
use App\Policies\PackageControllerPolicy;
use App\Policies\PaymentControllerPolicy;
use App\Policies\PaymentMethodsControllerPolicy;
use App\Policies\ProjectFileControllerPolicy;
use App\Policies\ProjectPermissionControllerPolicy;
use App\Policies\ProjectStoryControllerPolicy;
use App\Policies\ReceiptControllerPolicy;
use App\Policies\ReportControllerPolicy;
use App\Policies\RoleControllerPolicy;
use App\Policies\SprintControllerPolicy;
use App\Policies\StatusControllerPolicy;
use App\Policies\TicketCommentControllerPolicy;
use App\Policies\TicketControllerPolicy;
use App\Policies\TicketRealizationControllerPolicy;
use App\Policies\TimeTracker\ScreenshotPolicy;
use App\Policies\TimeTracker\TimeTrackerPolicy;
use App\Policies\WorkloadControllerPolicy;
use App\Policies\TicketTypeControllerPolicy;
use App\Policies\Company\TaxOfficeControllerPolicy;
use App\Policies\SaleInvoice\JpkControllerPolicy;
use App\Policies\SaleInvoice\JpkDetailsControllerPolicy;
use App\Policies\UserControllerPolicy;
use App\Policies\InvoiceFormatControllerPolicy;
use App\Policies\InvoiceTypeControllerPolicy;
use App\Policies\InvoiceSettingsControllerPolicy;
use App\Policies\VatRateControllerPolicy;
use App\Policies\ContractorControllerPolicy;
use App\Policies\CashFlowReportControllerPolicy;
use App\Policies\SaleReportControllerPolicy;
use App\Policies\ProjectControllerPolicy;
use App\Policies\ProjectUserControllerPolicy;
use App\Policies\ErrorLogControllerPolicy;
use App\Policies\VatReleaseReasonControllerPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        InvoiceController::class => InvoiceControllerPolicy::class,
        InvoiceReportController::class => InvoiceReportControllerPolicy::class,
        InvoicePaymentsController::class => InvoicePaymentsControllerPolicy::class,
        CashFlowReportController::class => CashFlowReportControllerPolicy::class,
        CashFlowController::class => CashFlowControllerPolicy::class,
        PaymentMethodsController::class => PaymentMethodsControllerPolicy::class,
        InvoiceFormatController::class => InvoiceFormatControllerPolicy::class,
        InvoiceTypeController::class => InvoiceTypeControllerPolicy::class,
        InvoiceFilterController::class => InvoiceFilterControllerPolicy::class,
        InvoiceSettingsController::class => InvoiceSettingsControllerPolicy::class,
        UserController::class => UserControllerPolicy::class,
        RoleController::class => RoleControllerPolicy::class,
        CalendarAvailabilityController::class => CalendarAvailabilityControllerPolicy::class,
        CalendarDayOffController::class => CalendarDayOffControllerPolicy::class,
        CompanyController::class => CompanyControllerPolicy::class,
        CompanyUserController::class => CompanyUserControllerPolicy::class,
        InvitationController::class => InvitationControllerPolicy::class,
        VatRateController::class => VatRateControllerPolicy::class,
        CompanyServiceController::class => CompanyServiceControllerPolicy::class,
        CompanyServiceUnitController::class => CompanyServiceUnitControllerPolicy::class,
        ContractorController::class => ContractorControllerPolicy::class,
        TokenController::class => TokenControllerPolicy::class,
        ReceiptController::class => ReceiptControllerPolicy::class,
        OnlineSaleController::class => OnlineSaleControllerPolicy::class,
        AuthController::class => AuthControllerPolicy::class,
        SaleReportController::class => SaleReportControllerPolicy::class,
        ProjectController::class => ProjectControllerPolicy::class,
        ProjectPermissionController::class => ProjectPermissionControllerPolicy::class,
        ProjectUserController::class => ProjectUserControllerPolicy::class,
        FileController::class => ProjectFileControllerPolicy::class,
        ErrorLogController::class => ErrorLogControllerPolicy::class,
        InvoiceMarginProcedureController::class => InvoiceMarginProcedureControllerPolicy::class,
        KnowledgePageController::class => KnowledgePageControllerPolicy::class,
        KnowledgeDirectoryController::class => KnowledgeDirectoryControllerPolicy::class,
        KnowledgePageCommentController::class => KnowledgePageCommentControllerPolicy::class,
        SprintController::class => SprintControllerPolicy::class,
        TicketController::class => TicketControllerPolicy::class,
        TicketCommentController::class => TicketCommentControllerPolicy::class,
        TicketTypeController::class => TicketTypeControllerPolicy::class,
        TicketRealizationController::class => TicketRealizationControllerPolicy::class,
        StoryController::class => ProjectStoryControllerPolicy::class,
        StatusController::class => StatusControllerPolicy::class,
        InvoiceCorrectionTypeController::class => InvoiceCorrectionTypeControllerPolicy::class,
        PaymentController::class => PaymentControllerPolicy::class,
        InvoiceReverseChargeController::class => InvoiceCorrectionTypeControllerPolicy::class,
        TaxOfficeController::class => TaxOfficeControllerPolicy::class,
        JpkController::class => JpkControllerPolicy::class,
        JpkDetailsController::class => JpkDetailsControllerPolicy::class,
        VatReleaseReasonController::class => VatReleaseReasonControllerPolicy::class,
        ClipboardController::class => ClipboardControllerPolicy::class,
        ModuleController::class => ModuleControllerPolicy::class,
        PackageController::class => PackageControllerPolicy::class,
        IntegrationProviderController::class => IntegrationProviderControllerPolicy::class,
        IntegrationController::class => IntegrationControllerPolicy::class,
        TimeTrackingActivityController::class => ActivityControllerPolicy::class,
        TimeTrackingProjectController::class => TimeTrackingProjectControllerPolicy::class,
        TimeTrackingUserController::class => TimeTrackingUserControllerPolicy::class,
        WorkloadController::class => WorkloadControllerPolicy::class,
        ReportController::class => ReportControllerPolicy::class,
        TimeTrackerController::class => TimeTrackerPolicy::class,
        ScreenshotController::class => ScreenshotPolicy::class,
        DashboardController::class => DashboardControllerPolicy::class,

    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
