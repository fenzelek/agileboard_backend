<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('reports/cash-flows', 'CashFlowReportController@index')->name('cash-flow-report.index');
    Route::get('reports/receipts', 'SaleReportController@reportReceipts');
    Route::get('reports/online-sales', 'SaleReportController@reportOnlineSales');
    Route::get('reports/invoices-registry', 'SaleReportController@invoicesRegistry')->name('reports.invoice-registry');
    Route::get('reports/invoices-registry-pdf', 'SaleReportController@invoicesRegistryPdf');
    Route::get('reports/invoices-registry-xls', 'SaleReportController@invoicesRegistryXls');
    Route::get('reports/invoices-registry-report', 'SaleReportController@reportInvoicesRegistry');
    Route::get('reports/invoices-report-export', 'SaleReportController@invoicesRegisterExport');
    Route::get('reports/company-invoices', 'InvoiceReportController@reportCompanyInvoices');
});
