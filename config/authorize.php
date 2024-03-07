<?php

return [
    /*
     * Super roles - in role mode, those roles will be automatically added to
     * other roles, and in permission mode for those roles won't
     * be made any detailed checks - in case if user is assigned to any of those
     * roles it will be assumed he has permission. If you leave it empty, no
     * super roles will be used
     */
    'super_roles' => [
        \App\Models\Other\RoleType::SYSTEM_ADMIN,
    ],

    /*
     * Name of virtual role for not logged user
     */
    'guest_role_name' => 'anonymous',

    /*
     * Available permissions for roles (those are by default used by
     * PermissionConfigHandler unless you create custom handler that will read
     * them for example from database)
     */
    'permissions' => [

        /*
         * List of all available permissions in system. You should keep it
         * up-to-date. Format of those permissions is:
         * name of group in your Policy class . controller method name
         */
        'available' => [
            'role.index',
            'role.company',
            'user.index',
            'user.store',
            'user.current',
            'user.companies',
            'calendar.index',
            'calendar.report',
            'calendar.store',
            'calendar.storeOwn',
            'calendar.show',
            'day-off.add',
            'day-off.update',
            'day-off.destroy',
            'company.store',
            'invitation.store',
            'cash-flow-report.index',
            'vat-rate.index',
            'cash-flow.index',
            'cash-flow.update',
            'payment-methods.index',
            'company_user.index',
            'user.update',
            'company_user.store',
            'invoice.index',
            'invoice.show',
            'invoice.update',
            'invoice.destroy',
            'invoice.indexPdf',
            'invoice.indexZip',
            'invoice-format.index',
            'invoice-payments.index',
            'invoice-payments.destroy',
            'invoice-payments.store',
            'invoice-type.index',
            'invoice-filter.index',
            'invoice-settings.show',
            'invoice-settings.update',
            'company_user.update',
            'company_user.destroy',
            'company-service.index',
            'company-service.store',
            'company-service.update',
            'company-service.show',
            'company-service-unit.index',
            'company.update',
            'company.updateSettings',
            'company.updatePaymentMethod',
            'company.showCurrent',
            'company.getLogotype',
            'company.getLogotypeSelectedCompany',
            'company.getGusData',
            'company.indexCountryVatinPrefixes',
            'contractor.index',
            'contractor.store',
            'contractor.update',
            'contractor.show',
            'contractor.destroy',
            'company-token.index',
            'company-token.store',
            'company-token.destroy',
            'receipt.store',
            'online-sale.store',
            'auth.apiToken',
            'receipt.index',
            'receipt.show',
            'online-sale.index',
            'online-sale.show',
            'report.reportReceipts',
            'report-agile.daily',
            'invoice.store',
            'report.reportOnlineSales',
            'invoice-report.reportCompanyInvoices',
            'online-sale.pdf',
            'cash-flow.pdf',
            'cash-flow.pdfItem',
            'report.invoicesRegistry',
            'report.reportInvoicesRegistry',
            'report.invoicesRegisterExport',
            'invoice.pdf',
            'report.invoicesRegistryPdf',
            'report.invoicesRegistryXls',
            'project.store',
            'project.clone',
            'project.close',
            'project.index',
            'project.exist',
            'project.show',
            'project-file.index',
            'project-file.store',
            'project-file.destroy',
            'project-file.update',
            'project-file.show',
            'project-file.download',
            'project-file.types',
            'project.update',
            'project.destroy',
            'project-user.index',
            'project-user.store',
            'project-user.destroy',
            'project-permissions.show',
            'project-permissions.update',
            'receipt.pdf',
            'receipt.pdfReport',
            'error-log.index',
            'invoice.send',
            'invoice-margin-procedure.index',
            'invoice-correction-type.index',
            'company-package.orderTest',
            'invoice-reverse-charge.index',
            'tax-office.index',
            'jpk.index',
            'jpk-details.show',
            'jpk-details.update',
            'vat-release-reasons.index',
            'company-new-package.index',
            'package.index',
            'package.current',
            'package.show',
            'package.store',
            'modules.current',
            'modules.available',
            'modules.store',
            'modules.destroy',
            'modules.limits',
            'payment.index',
            'payment.show',
            'payment.confirmBuy',
            'payment.payAgain',
            'payment.cardList',
            'payment.cancelSubscription',
            'payment.cancelPayment',
            'clipboard.index',
            'clipboard.download',
            'knowledge-page.update',
            'knowledge-page.index',
            'knowledge-page.show',
            'knowledge-page.store',
            'knowledge-page.destroy',
            'knowledge-directory.index',
            'knowledge-directory.store',
            'knowledge-directory.update',
            'knowledge-directory.destroy',
            'knowledge-page-comment.store',
            'sprint.update',
            'sprint.activate',
            'sprint.pause',
            'sprint.resume',
            'sprint.lock',
            'sprint.unlock',
            'sprint.close',
            'sprint.store',
            'sprint.clone',
            'sprint.destroy',
            'sprint.export',
            'sprint.changePriority',
            'sprint.index',
            'ticket-type.index',
            'ticket-realizations.index',
            'workload.index',
            'ticket.index',
            'ticket.show',
            'ticket.history',
            'ticket.store',
            'ticket.update',
            'ticket.setFlagToShow',
            'ticket.setFlagToHide',
            'ticket.destroy',
            'ticket.changePriority',
            'ticket-comment.store',
            'ticket-comment.update',
            'ticket-comment.destroy',
            'project-story.store',
            'project-story.update',
            'project-story.destroy',
            'project-story.index',
            'project-story.show',
            'user.getAvatar',
            'status.store',
            'status.update',
            'status.index',
            'project.basicInfo',
            'integration-provider.index',
            'integration.store',
            'integration.index',
            'time-tracking-activity.index',
            'time-tracking-activity.export',
            'time-tracking-activity.summary',
            'time-tracking-activity.dailySummary',
            'time-tracking-activity.bulkUpdate',
            'time-tracking-activity.store',
            'time-tracking-activity.storeOwnActivity',
            'time-tracking-activity.remove',
            'time-tracking-activity.removeOwnActivities',
            'time-tracking-project.index',
            'time-tracking-project.fetch',
            'time-tracking-project.update',
            'time-tracking-user.index',
            'time-tracker.addFrames',
            'time-tracker.getTimeSummary',
            'time-tracker.addScreenshots',
            'screenshot.index',
            'screenshot.indexOwn',
            'calendar-availability.store',
            'calendar-availability.storeOwn',
            'dashboard-agile.index',
        ],

        /*
         * Assignment of above permissions to user roles
         * If for any role you set only '*' as permission it means all
         * permissions will be available for this role
         */
        'roles' => [
            \App\Models\Other\RoleType::SYSTEM_ADMIN => [
                '*', // all permissions (don't add anything into this array)
            ],

            /*
             * This should match the value you set as `guest_role_name`.
             * It contains allowed permissions for not logged users
             */
            'anonymous' => [
                // @todo at the moment it's not used like this in this application
            ],

            \App\Models\Other\RoleType::SYSTEM_USER => [
                'company.store',
                'company.getGusData',
                'user.current',
                'user.companies',
                'role.index',
                'role.company',
                'invitation.currentIndex',
                'user.update',
                'user.getAvatar',
                'ticket-type.index',
                'project.basicInfo',
                'company-service-unit.index',
                'tax-office.index',
                'vat-release-reasons.index',

                //access for all in selected company
                'ticket.index',
                'time-tracker.addFrames',
                'time-tracker.getTimeSummary',
                'time-tracker.addScreenshots',
                'dashboard-agile.index',
            ],

            \App\Models\Other\RoleType::ADMIN => [
                'company.store',
                'user.companies',
                'role.index',
                'role.company',
                'user.index',
                'calendar.index',
                'calendar.export',
                'calendar.report',
                'calendar.store',
                'calendar.storeOwn',
                'calendar.show',
                'company.invite',
                'day-off.add',
                'day-off.update',
                'day-off.destroy',
                'invitation.store',
                'invitation.currentIndex',
                'invoice.index',
                'invoice.indexZip',
                'invoice.show',
                'invoice.update',
                'invoice.destroy',
                'invoice.indexPdf',
                'invoice-format.index',
                'invoice-payments.index',
                'invoice-payments.destroy',
                'invoice-payments.store',
                'invoice-type.index',
                'invoice-filter.index',
                'invoice-settings.show',
                'invoice-settings.update',
                'cash-flow.index',
                'cash-flow.store',
                'cash-flow.update',
                'cash-flow.pdf',
                'cash-flow.pdfItem',
                'vat-rate.index',
                'company-service.index',
                'company-service.store',
                'company-service.update',
                'company-service.show',
                'company-service-unit.index',
                'company.update',
                'company.updateSettings',
                'company.showCurrent',
                'company.getLogotype',
                'company.getLogotypeSelectedCompany',
                'company.indexCountryVatinPrefixes',
                'company.updatePaymentMethod',
                'company.getGusData',
                'user.update',
                'contractor.index',
                'contractor.store',
                'contractor.update',
                'contractor.show',
                'contractor.destroy',
                'payment-methods.index',
                'company-token.index',
                'company-token.store',
                'company-token.destroy',
                'receipt.index',
                'receipt.show',
                'receipt.pdf',
                'online-sale.index',
                'online-sale.show',
                'online-sale.pdf',
                'report.reportReceipts',
                'invoice.store',
                'report.reportOnlineSales',
                'invoice-report.reportCompanyInvoices',
                'cash-flow-report.index',
                'report.invoicesRegistry',
                'report.reportInvoicesRegistry',
                'invoice.pdf',
                'company_user.index',
                'report.invoicesRegistryPdf',
                'report.invoicesRegistryXls',
                'project.store',
                'project.clone',
                'project.close',
                'project.index',
                'project.exist',
                'project.show',
                'project-file.index',
                'project-file.store',
                'project-file.destroy',
                'project-file.update',
                'project-file.show',
                'project-file.download',
                'project-file.types',
                'project.update',
                'project.destroy',
                'project-user.index',
                'project-user.store',
                'project-user.destroy',
                'project-permissions.show',
                'project-permissions.update',
                'receipt.pdfReport',
                'company_user.destroy',
                'company_user.update',
                'company_user.destroy',
                'error-log.index',
                'error-log.destroy',
                'invoice.send',
                'invoice-margin-procedure.index',
                'invoice-correction-type.index',
                'invoice-reverse-charge.index',
                'jpk.index',
                'jpk-details.show',
                'jpk-details.update',
                'vat-release-reasons.index',
                'modules.limits',
                'clipboard.index',
                'clipboard.download',
                'knowledge-page.index',
                'knowledge-page.show',
                'knowledge-page.store',
                'knowledge-page.update',
                'knowledge-page.destroy',
                'knowledge-directory.index',
                'knowledge-directory.store',
                'knowledge-directory.update',
                'knowledge-directory.destroy',
                'knowledge-page-comment.store',
                'knowledge-page-comment.update',
                'knowledge-page-comment.destroy',
                'sprint.update',
                'sprint.export',
                'sprint.activate',
                'sprint.pause',
                'sprint.resume',
                'sprint.lock',
                'sprint.unlock',
                'sprint.close',
                'sprint.store',
                'sprint.clone',
                'sprint.destroy',
                'sprint.changePriority',
                'sprint.index',
                'ticket-type.index',
                'ticket-realizations.index',
                'workload.index',
                'ticket.index',
                'ticket.show',
                'ticket.history',
                'ticket.store',
                'ticket.update',
                'ticket.setFlagToShow',
                'ticket.setFlagToHide',
                'ticket.destroy',
                'ticket.changePriority',
                'ticket-comment.store',
                'ticket-comment.update',
                'ticket-comment.destroy',
                'project-story.store',
                'project-story.update',
                'project-story.destroy',
                'project-story.index',
                'project-story.show',
                'user.getAvatar',
                'status.store',
                'status.update',
                'status.index',
                'integration-provider.index',
                'integration.index',
                'time-tracking-activity.index',
                'time-tracking-activity.export',
                'time-tracking-activity.summary',
                'time-tracking-activity.dailySummary',
                'time-tracking-activity.bulkUpdate',
                'time-tracking-activity.store',
                'time-tracking-activity.storeOwnActivity',
                'time-tracking-activity.removeActivities',
                'time-tracking-activity.removeOwnActivities',
                'time-tracking-activity.activityReport',
                'time-tracking-project.index',
                'time-tracking-project.fetch',
                'time-tracking-project.update',
                'time-tracking-user.index',
                'report-agile.daily',
                'time-tracker.addFrames',
                'time-tracker.getTimeSummary',
                'time-tracker.addScreenshots',
                'screenshot.index',
                'screenshot.indexOwn',
                'dashboard-agile.index',
            ],

            \App\Models\Other\RoleType::OWNER => [
                'company.store',
                'user.companies',
                'role.index',
                'role.company',
                'user.index',
                'calendar.index',
                'calendar.export',
                'calendar.report',
                'calendar.store',
                'calendar.storeOwn',
                'calendar.show',
                'day-off.add',
                'day-off.update',
                'day-off.destroy',
                'company.invite',
                'invitation.store',
                'invoice.index',
                'invoice.show',
                'invoice.update',
                'invoice.destroy',
                'invoice.indexPdf',
                'invoice.indexZip',
                'invoice-format.index',
                'invoice-payments.index',
                'invoice-payments.destroy',
                'invoice-payments.store',
                'invoice-type.index',
                'invoice-filter.index',
                'invoice-settings.show',
                'invoice-settings.update',
                'cash-flow.index',
                'vat-rate.index',
                'payment-methods.index',
                'company_user.index',
                'company-service.index',
                'company-service.store',
                'company-service.update',
                'company-service.show',
                'company-service-unit.index',
                'company.update',
                'company.updateSettings',
                'company.showCurrent',
                'company.getLogotype',
                'company.getLogotypeSelectedCompany',
                'company.indexCountryVatinPrefixes',
                'company.updatePaymentMethod',
                'company.getGusData',
                'company_user.update',
                'company_user.destroy',
                'user.update',
                'contractor.index',
                'contractor.store',
                'contractor.update',
                'contractor.show',
                'contractor.destroy',
                'company-token.index',
                'company-token.store',
                'company-token.destroy',
                'receipt.index',
                'receipt.pdf',
                'receipt.show',
                'online-sale.index',
                'online-sale.show',
                'online-sale.pdf',
                'report.reportReceipts',
                'invoice.store',
                'report.reportOnlineSales',
                'invoice-report.reportCompanyInvoices',
                'cash-flow.store',
                'cash-flow.update',
                'cash-flow.pdf',
                'cash-flow.pdfItem',
                'cash-flow-report.index',
                'report.invoicesRegistry',
                'report.reportInvoicesRegistry',
                'invoice.pdf',
                'report.invoicesRegistryPdf',
                'report.invoicesRegistryXls',
                'project.store',
                'project.clone',
                'project.close',
                'project.index',
                'project.exist',
                'project.show',
                'project-file.index',
                'project-file.store',
                'project-file.destroy',
                'project-file.update',
                'project-file.show',
                'project-file.download',
                'project-file.types',
                'project.update',
                'project.destroy',
                'project-user.index',
                'project-user.store',
                'project-user.destroy',
                'project-permissions.show',
                'project-permissions.update',
                'receipt.pdfReport',
                'error-log.index',
                'error-log.destroy',
                'invoice.send',
                'invoice-margin-procedure.index',
                'invoice-correction-type.index',
                'invoice-correction-type.index',
                'company-package.orderTest',
                'invoice-reverse-charge.index',
                'jpk.index',
                'jpk-details.show',
                'jpk-details.update',
                'vat-release-reasons.index',
                'company-new-package.index',
                'package.index',
                'package.current',
                'package.show',
                'package.store',
                'modules.current',
                'modules.available',
                'modules.store',
                'modules.destroy',
                'modules.limits',
                'payment.index',
                'payment.show',
                'payment.confirmBuy',
                'payment.payAgain',
                'payment.cardList',
                'payment.cancelSubscription',
                'payment.cancelPayment',
                'clipboard.index',
                'clipboard.download',
                'knowledge-page.show',
                'knowledge-page.index',
                'knowledge-page.store',
                'knowledge-page.update',
                'knowledge-page.destroy',
                'knowledge-directory.index',
                'knowledge-directory.store',
                'knowledge-directory.update',
                'knowledge-directory.destroy',
                'sprint.export',
                'knowledge-page-comment.store',
                'knowledge-page-comment.update',
                'knowledge-page-comment.destroy',
                'sprint.export',
                'sprint.update',
                'sprint.activate',
                'sprint.pause',
                'sprint.resume',
                'sprint.lock',
                'sprint.unlock',
                'sprint.close',
                'sprint.store',
                'sprint.clone',
                'sprint.destroy',
                'sprint.changePriority',
                'sprint.index',
                'ticket-type.index',
                'ticket-realizations.index',
                'workload.index',
                'ticket.index',
                'ticket.show',
                'ticket.history',
                'ticket.store',
                'ticket.update',
                'ticket.setFlagToShow',
                'ticket.setFlagToHide',
                'ticket.destroy',
                'ticket.changePriority',
                'ticket-comment.store',
                'ticket-comment.update',
                'ticket-comment.destroy',
                'project-story.store',
                'project-story.update',
                'project-story.destroy',
                'project-story.index',
                'project-story.show',
                'user.getAvatar',
                'status.store',
                'status.update',
                'status.index',
                'integration-provider.index',
                'integration.store',
                'integration.index',
                'time-tracking-activity.index',
                'time-tracking-activity.export',
                'time-tracking-activity.summary',
                'time-tracking-activity.dailySummary',
                'time-tracking-activity.bulkUpdate',
                'time-tracking-activity.store',
                'time-tracking-activity.storeOwnActivity',
                'time-tracking-activity.removeActivities',
                'time-tracking-activity.removeOwnActivities',
                'time-tracking-activity.activityReport',
                'time-tracking-project.index',
                'time-tracking-project.fetch',
                'time-tracking-project.update',
                'time-tracking-user.index',
                'report-agile.daily',
                'time-tracker.addFrames',
                'time-tracker.getTimeSummary',
                'time-tracker.addScreenshots',
                'screenshot.index',
                'screenshot.indexOwn',
                'dashboard-agile.index',
            ],

            \App\Models\Other\RoleType::DEALER => [
                'user.index',
                'calendar.index',
                'calendar.report',
                'calendar.store',
                'calendar.show',
                'user.update',
                'contractor.index',
                'contractor.store',
                'contractor.update',
                'contractor.show',
                'contractor.destroy',
                'payment-methods.index',
                'cash-flow-report.index',
                'invoice-report.reportCompanyInvoices',
                'company.getGusData',
                'company.showCurrent',
                'company.getLogotype',
                'company.getLogotypeSelectedCompany',
                'project.index',
                'project.exist',
                'project.show',
                'project-file.index',
                'project-file.store',
                'project-file.destroy',
                'project-file.update',
                'project-file.show',
                'project-file.download',
                'project-file.types',
                'project-user.index',
                'knowledge-page.show',
                'knowledge-page.index',
                'knowledge-page.store',
                'knowledge-page.update',
                'knowledge-page.destroy',
                'knowledge-directory.index',
                'knowledge-directory.store',
                'knowledge-directory.update',
                'knowledge-directory.destroy',
                'knowledge-page-comment.store',
                'knowledge-page-comment.update',
                'knowledge-page-comment.destroy',
                'project-story.store',
                'project-story.update',
                'project-story.destroy',
                'project-story.index',
                'project-story.show',
                'user.getAvatar',
                'ticket-type.index',
                'integration-provider.index',
                'time-tracker.addFrames',
                'time-tracker.getTimeSummary',
                'time-tracker.addScreenshots',
                'availabilities.storeOwn',
                'dashboard-agile.index',
            ],

            \App\Models\Other\RoleType::DEVELOPER => [
                'user.index',
                'calendar.index',
                'calendar.export',
                'calendar.report',
                'calendar.storeOwn',
                'calendar.show',
                'user.update',
                'contractor.store',
                'contractor.update',
                'contractor.show',
                'contractor.destroy',
                'payment-methods.index',
                'company.getGusData',
                'company.showCurrent',
                'vat-release-reasons.index',
                'company.getLogotype',
                'company.getLogotypeSelectedCompany',
                'project.index',
                'project.exist',
                'project.show',
                'project-file.index',
                'project-file.store',
                'project-file.destroy',
                'knowledge-page.show',
                'knowledge-page.index',
                'knowledge-page.store',
                'knowledge-page.update',
                'knowledge-page.destroy',
                'knowledge-directory.index',
                'knowledge-directory.store',
                'knowledge-directory.update',
                'knowledge-directory.destroy',
                'knowledge-page-comment.store',
                'knowledge-page-comment.update',
                'knowledge-page-comment.destroy',
                'project-file.update',
                'project-file.show',
                'project-file.download',
                'project-file.types',
                'project-user.index',
                'sprint.update',
                'sprint.store',
                'sprint.changePriority',
                'sprint.index',
                'sprint.lock',
                'sprint.unlock',
                'ticket-type.index',
                'ticket-realizations.index',
                'workload.index',
                'ticket.index',
                'ticket.show',
                'ticket.history',
                'ticket.store',
                'ticket.update',
                'ticket.setFlagToShow',
                'ticket.setFlagToHide',
                'ticket.destroy',
                'ticket.changePriority',
                'ticket-comment.store',
                'ticket-comment.update',
                'ticket-comment.destroy',
                'project-story.store',
                'project-story.update',
                'project-story.destroy',
                'project-story.index',
                'project-story.show',
                'status.index',
                'user.getAvatar',
                'integration-provider.index',
                'time-tracking-activity.index',
                'time-tracking-activity.export',
                'time-tracking-activity.summary',
                'time-tracking-activity.dailySummary',
                'time-tracking-activity.bulkUpdate',
                'time-tracking-activity.storeOwnActivity',
                'time-tracking-activity.removeOwnActivities',
                'report-agile.daily',
                'time-tracker.addFrames',
                'time-tracker.getTimeSummary',
                'time-tracker.addScreenshots',
                'screenshot.indexOwn',
                'availabilities.storeOwn',
                'dashboard-agile.index',

            ],

            \App\Models\Other\RoleType::CLIENT => [
                'user.index',
                'user.update',
                'payment-methods.index',
                'project.index',
                'project.exist',
                'project.show',
                'project-file.index',
                'project-file.store',
                'project-file.destroy',
                'knowledge-page.show',
                'knowledge-page.index',
                'knowledge-directory.index',
                'knowledge-page-comment.store',
                'knowledge-page-comment.update',
                'knowledge-page-comment.destroy',
                'project-file.update',
                'project-file.show',
                'project-file.download',
                'project-file.types',
                'project-user.index',
                'sprint.changePriority',
                'sprint.index',
                'sprint.lock',
                'sprint.unlock',
                'ticket-type.index',
                'ticket.index',
                'ticket.show',
                'ticket.history',
                'ticket.store',
                'ticket.update',
                'ticket.setFlagToShow',
                'ticket.setFlagToHide',
                'ticket.destroy',
                'ticket.changePriority',
                'ticket-comment.store',
                'ticket-comment.update',
                'ticket-comment.destroy',
                'project-story.store',
                'project-story.update',
                'project-story.index',
                'project-story.show',
                'status.index',
                'user.getAvatar',
                'company.showCurrent',
                'company.getLogotype',
                'company.getLogotypeSelectedCompany',
                'report-agile.daily',
                'time-tracker.addFrames',
                'time-tracker.getTimeSummary',
                'time-tracker.addScreenshots',
                'dashboard-agile.index',
            ],

            \App\Models\Other\RoleType::EMPLOYEE => [
                'user.update',
                'company-service.index',
                'company-service.store',
                'company-service.update',
                'company-service-unit.index',
                'contractor.index',
                'contractor.store',
                'contractor.update',
                'contractor.show',
                'contractor.destroy',
                'company-service.show',
                'payment-methods.index',
                'receipt.index',
                'receipt.pdf',
                'receipt.show',
                'cash-flow.index',
                'cash-flow.update',
                'cash-flow.store',
                'cash-flow.pdf',
                'cash-flow.pdfItem',
                'online-sale.index',
                'online-sale.show',
                'online-sale.pdf',
                'report.reportReceipts',
                'invoice.store',
                'invoice.show',
                'invoice.update',
                'invoice.indexPdf',
                'invoice.indexZip',
                'report.reportOnlineSales',
                'cash-flow-report.index',
                'invoice-payments.index',
                'invoice-payments.destroy',
                'invoice-payments.store',
                'invoice-settings.show',
                'report.invoicesRegistry',
                'report.reportInvoicesRegistry',
                'invoice-report.reportCompanyInvoices',
                'invoice.pdf',
                'invoice.index',
                'invoice.indexZip',
                'vat-rate.index',
                'invoice-type.index',
                'invoice-filter.index',
                'company.showCurrent',
                'company.getLogotype',
                'company.getLogotypeSelectedCompany',
                'company.indexCountryVatinPrefixes',
                'company_user.index',
                'invoice-format.index',
                'report.invoicesRegistryPdf',
                'report.invoicesRegistryXls',
                'receipt.pdfReport',
                'error-log.index',
                'error-log.destroy',
                'invoice.send',
                'invoice-margin-procedure.index',
                'invoice-correction-type.index',
                'company.getGusData',
                'invoice-reverse-charge.index',
                'jpk.index',
                'jpk-details.show',
                'vat-release-reasons.index',
                'clipboard.index',
                'clipboard.download',
                'knowledge-page.show',
                'knowledge-page.index',
                'knowledge-page.store',
                'knowledge-page.update',
                'knowledge-page.destroy',
                'knowledge-directory.index',
                'knowledge-directory.store',
                'knowledge-directory.update',
                'knowledge-directory.destroy',
                'knowledge-page-comment.store',
                'knowledge-page-comment.update',
                'knowledge-page-comment.destroy',
                'sprint.update',
                'sprint.store',
                'sprint.activate',
                'sprint.pause',
                'sprint.resume',
                'sprint.lock',
                'sprint.unlock',
                'sprint.close',
                'sprint.destroy',
                'sprint.changePriority',
                'sprint.index',
                'ticket-type.index',
                'ticket-realizations.index',
                'workload.index',
                'ticket.index',
                'ticket.show',
                'ticket.history',
                'ticket.store',
                'ticket.update',
                'ticket.setFlagToShow',
                'ticket.setFlagToHide',
                'ticket.destroy',
                'ticket.changePriority',
                'ticket-comment.store',
                'ticket-comment.update',
                'ticket-comment.destroy',
                'status.index',
                'user.getAvatar',
                'time-tracker.addFrames',
                'time-tracker.getTimeSummary',
                'time-tracker.addScreenshots',
                'screenshot.index',
                'dashboard-agile.index',

            ],
            \App\Models\Other\RoleType::TAX_OFFICE => [
                'company-service.index',
                'contractor.index',
                'contractor.show',
                'company-service.show',
                'company-service-unit.index',
                'payment-methods.index',
                'receipt.index',
                'receipt.pdf',
                'receipt.show',
                'cash-flow.index',
                'cash-flow.pdf',
                'cash-flow.pdfItem',
                'online-sale.index',
                'online-sale.show',
                'online-sale.pdf',
                'report.reportReceipts',
                'invoice.show',
                'report.reportOnlineSales',
                'cash-flow-report.index',
                'invoice-payments.index',
                'invoice-settings.show',
                'report.invoicesRegistry',
                'report.reportInvoicesRegistry',
                'invoice-report.reportCompanyInvoices',
                'invoice.pdf',
                'invoice.index',
                'invoice.indexZip',
                'invoice.indexPdf',
                'vat-rate.index',
                'invoice-type.index',
                'invoice-filter.index',
                'company.showCurrent',
                'company.indexCountryVatinPrefixes',
                'invoice-format.index',
                'report.invoicesRegistryPdf',
                'report.invoicesRegistryXls',
                'report.invoicesRegisterExport',
                'receipt.pdfReport',
                'invoice-margin-procedure.index',
                'invoice-correction-type.index',
                'company.getGusData',
                'invoice-reverse-charge.index',
                'company_user.index',
                'jpk.index',
                'jpk-details.show',
                'vat-release-reasons.index',
                'clipboard.index',
                'clipboard.download',
            ],
            \App\Models\Other\RoleType::API_USER => [
                'receipt.store',
                'auth.apiToken',
                'day-off.add',
                'day-off.update',
                'day-off.destroy',
            ],
            \App\Models\Other\RoleType::API_COMPANY => [
                'online-sale.store',
                'day-off.add',
                'day-off.update',
                'day-off.destroy',
            ],
        ],
    ],

    /*
     * Module bindings (don't touch them unless you want write custom permission handling)
     */
    'bindings' => [
        \App\Services\Mnabialek\LaravelAuthorize\Contracts\Permissionable::class => \App\Services\Mnabialek\LaravelAuthorize\Services\Permission::class,
        \App\Services\Mnabialek\LaravelAuthorize\Contracts\PermissionHandler::class => \App\Services\Mnabialek\LaravelAuthorize\Services\ConfigPermissionHandler::class,
    ],
];
