<?php

namespace App\Models\Other;

class ModuleType
{
    //projects
    const PROJECTS_ACTIVE = 'projects.active';
    const PROJECTS_DISC_VOLUME = 'projects.disc.volume';
    const PROJECTS_FILE_SIZE = 'projects.file.size';
    const PROJECTS_MULTIPLE_PROJECTS = 'projects.multiple.projects';
    const PROJECTS_USERS_IN_PROJECT = 'projects.users.in.project';
    const PROJECTS_INTEGRATIONS_HUBSTAFF = 'projects.integrations.hubstaff';

    //general
    const GENERAL_INVITE_ENABLED = 'general.invite.enabled';
    const GENERAL_WELCOME_URL = 'general.welcome_url';
    const GENERAL_COMPANIES_VISIBLE = 'general.companies.visible';
    const GENERAL_MULTIPLE_USERS = 'general.multiple_users';

    //invoices
    const INVOICES_ACTIVE = 'invoices.active';
    const INVOICES_ADDRESSES_DELIVERY_ENABLED = 'invoices.addresses.delivery.enabled';
    const INVOICES_SERVICES_NAME_CUSTOMIZE = 'invoices.services.name.allow_customization';
    const INVOICES_PROFORMA_ENABLED = 'invoices.proforma.enabled';
    const INVOICES_MARGIN_ENABLED = 'invoices.margin.enabled';
    const INVOICES_REVERSE_CHARGE_ENABLED = 'invoices.reverse.charge.enabled';
    const INVOICES_ADVANCE_ENABLED = 'invoices.advance.enabled';
    const INVOICES_UE_ENABLED = 'invoices.ue.enabled';
    const INVOICES_FOOTER_ENABLED = 'invoices.footer.enabled';
    const INVOICES_REGISTER_EXPORT_NAME = 'invoices.registry.export.name';
    const INVOICES_JPK_EXPORT = 'invoices.jpk_export';

    //receipts
    const RECEIPTS_ACTIVE = 'receipts.active';

    //time tracker
    const TIME_TRACKER_ACTIVE = 'time_tracker.active';
}
