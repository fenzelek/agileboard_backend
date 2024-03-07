<?php

namespace App\Helpers;

class ErrorCode
{
    // GENERAL
    const VALIDATION_FAILED = 'general.validation_failed';
    const REQUESTS_RATE_EXCEEDED = 'general.request_rate_exceeded';
    const NO_PERMISSION = 'general.no_action_permission';
    const RESOURCE_NOT_FOUND = 'general.no_resource_found';
    const API_ERROR = 'general.api_error';
    const NOT_FOUND = 'general.invalid_action_or_method';
    const DATABASE_ERROR = 'general.database_error';

    // AUTH
    const AUTH_INVALID_LOGIN_DATA = 'auth.invalid_login_data';
    const AUTH_CANNOT_CREATE_TOKEN = 'auth.cannot_create_token';
    const AUTH_INVALID_TOKEN = 'auth.invalid_token';
    const AUTH_EXPIRED_TOKEN = 'auth.expired_token';
    const AUTH_USER_NOT_FOUND = 'auth.user_not_found';
    const AUTH_ALREADY_LOGGED = 'auth.user_already_logged';
    const AUTH_NOT_ACTIVATED = 'auth.user_not_activated';

    // EXTERNAL AUTH
    const AUTH_EXTERNAL_API_MISSING_TOKEN = 'auth_external.missing_token';
    const AUTH_EXTERNAL_INVALID_TOKEN = 'auth_external.invalid_token';
    const AUTH_EXTERNAL_EXPIRED_TOKEN = 'auth_external.expired_token';

    // PASSWORD
    const PASSWORD_NO_USER_FOUND = 'password.no_user_found';
    const PASSWORD_INVALID_PASSWORD = 'password.invalid_password';
    const PASSWORD_INVALID_TOKEN = 'password.invalid_token';

    // COMPANY
    const COMPANY_CREATION_LIMIT = 'company.creation_limit';
    const COMPANY_MORE_THAN_ONE_REGISTRY = 'company.more_than_one_registry';
    const COMPANY_BLOCKED_CHANGING_VAT_PAYER_SETTING = 'company.blocked_changing_vat_payer_setting';
    const COMPANY_BLOCKED_CHANGING_GROSS_COUNTED_SETTING = 'company.blocked_changing_cross_counted_setting';

    // COMPANY - INVITATION
    const COMPANY_INVITATION_ALREADY_ASSIGNED = 'company_invitation.already_assigned_to_company';
    const COMPANY_INVITATION_NOT_PENDING = 'company_invitation.not_pending';
    const COMPANY_INVITATION_EXPIRED = 'company_invitation.expired';

    // ACTIVATION
    const ACTIVATION_INVALID_TOKEN_OR_USER = 'activation.invalid_token_or_user';
    const ACTIVATION_ALREADY_ACTIVATED = 'activation.user_already_activated';

    // INVOICES
    const INVOICES_REGISTER_NOT_FOUND_OR_AMBIGUOUS = 'invoices.registry_not_found_or_ambiguous';
    const INVOICE_PROTECT_CORRECTION_OF_COMPANY_SERVICE = 'invoices.protect_correction_of_company_service';
    const INVOICE_HAS_CORRECTION = 'invoices.has_correction';
    const INVOICES_DUPLICATE_INVOICE_FOR_SINGLE_SALE_DOCUMENT = 'invoices.duplicate_invoice_for_single_sale_document';
    const INVOICE_DUPLICATE_FOR_PROFORMA_IS_NOT_ALLOWED = 'invoices.duplicate_for_proforma_is_not_allowed';
    const INVOICE_PACKAGE_BUFFER_OVERFLOW = 'invoices.package_buffer_overflow';

    // RECEIPTS AND ONLINE SALES
    const OTHER_SALES_DUPLICATE_TRANSACTION_NUMBER = 'other_sales.duplicate_transaction_number';

    // PACKAGE
    const PACKAGE_CANT_USE_CUSTOM_EXPORTS = 'package.cant_use_custom_exports';
    const PACKAGE_DATA_CONSISTENCY_ERROR = 'package.data_consistency_error';
    const PACKAGE_LIMIT_REACHED = 'package.limit_reached';
    const PACKAGE_TOO_MANY_USERS = 'package.too_many_users';

    // GUS
    const GUS_TECHNICAL_PROBLEMS = 'gus.technical_problems';

    // SALE INVOICE
    const SALE_INVOICE_JPK_NOT_ENABLED = 'sale_invoice.jpk_not_enabled';
    const SALE_INVOICE_JPK_DETAILS_MISSING = 'sale_invoice.jpk_details_missing';
    const SALE_INVOICE_JPK_VAT_PAYER_NOT_FILLED_IN = 'sale_invoice.jpk_vat_payer_not_filled_in';

    //PAYU
    const PAYU_TECHNICAL_PROBLEMS = 'payu.technical_problems';
    const PAYU_WARNING_CONTINUE_3DS = 'payu.warning_continue_3ds';
    const PAYU_WARNING_CONTINUE_CVV = 'payu.warning_continue_cvv';
    const PAYU_SOME_ERROR = 'payu.some_error';

    // CLIPBOARD
    const CLIPBOARD_NOT_FOUND_FILE = 'clipboard.no_found_file';

    // SPRINTS
    const SPRINT_INVALID_STATUS = 'sprint.invalid_current_status';
    const SPRINT_NOT_EMPTY = 'sprint.not_empty';

    // PROJECTS
    const PROJECT_NO_STATUSES = 'project.no_statuses_created';

    // INTEGRATIONS
    const INTEGRATION_INVALID_TIME_TRACKING_DATA = 'integration.invalid_time_tracking_data';
    const INTEGRATION_INVALID_MANUAL_ACTIVITY_TIME_PERIOD = 'integration.invalid_manual_activity_time_period';
    const INTEGRATION_INVALID = 'integration.invalid_manual_integration_company';
    const INTEGRATION_REMOVE_ERROR = 'integration.invalid_remove_method_not_allowed_by_given_ids';

    //TIME TRACKER
    const ERROR_SAVE_PICTURE = 'time_tracker.error_save_picture';

    //USER AVAILABILITY
    const ERROR_TIME_PERIOD = 'availability.error_period';
}
