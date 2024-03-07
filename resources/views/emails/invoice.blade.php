<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <style type="text/css" rel="stylesheet" media="all">
        /* Media Queries */
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
</head>

<?php

$style = [
    /* Layout ------------------------------ */

    'body' => 'margin: 0; padding: 0; width: 100%; background-color: #228cc0;',
    'email-wrapper' => 'width: 100%; margin: 0; padding: 0; background-color: #228cc0;',

    /* Masthead ----------------------- */

    'email-masthead' => 'padding: 20px 0; text-align: center;',
    'email-masthead_name' => 'font-size: 16px; font-weight: bold; color: #fff; text-decoration: none; text-shadow: 0 1px 0 white;',

    'email-body' => 'width: 100%; margin: 0; padding: 0; border-top: background-color: #228cc0;',
    'email-body_inner' => 'width: auto; max-width: 570px; margin: 0 auto; padding: 0; background-color: #fff;',
    'email-body_cell' => 'padding: 35px;',

    'email-footer' => 'width: auto; max-width: 570px; margin: 0 auto; padding: 0; text-align: center;',
    'email-footer_cell' => 'color: #fff; padding: 35px; text-align: center;',

    /* Body ------------------------------ */

    'body_action' => 'width: 100%; margin: 30px auto; padding: 0; text-align: center;',
    'body_sub' => 'margin-top: 20px; padding-top: 20px; border-top: 1px solid #EDEFF2;',

    /* Type ------------------------------ */

    'anchor' => 'color: #228cc0;',
    'footer-anchor' => 'color: #fff;',
    'footer-paragraph-sub' => 'margin-top: 0; font-size: 12px; line-height: 1.5em;',
    'header-1' => 'margin-top: 0; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;',
    'paragraph' => 'margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;',
    'paragraph-sub' => 'margin-top: 0; color: #74787E; font-size: 12px; line-height: 1.5em;',
    'paragraph-center' => 'text-align: center;',

    /* Buttons ------------------------------ */

    'button' => 'display: block; display: inline-block; width: 250px; min-height: 20px; padding: 10px;
                 background-color: #228cc0; border-radius: 3px; color: #ffffff; font-size: 15px; line-height: 25px;
                 text-align: center; text-decoration: none; -webkit-text-size-adjust: none;',

    'button--green' => 'background-color: #22BC66;',
    'button--red' => 'background-color: #dc4d2f;',
    'button--blue' => 'background-color: #228cc0;',
];
?>

<?php $fontFamily = 'font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;'; ?>

<body style="{{ $style['body'] }}">
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="{{ $style['email-wrapper'] }}" align="center">
            <table width="100%" cellpadding="0" cellspacing="0">
                <!-- Logo -->
                <tr>
                    <td style="{{ $style['email-masthead'] }}">
                        @if (config('app_settings.welcome_absolute_url'))
                            <a style="{{ $fontFamily }} {{ $style['email-masthead_name'] }}" href="{{ config('app_settings.welcome_absolute_url') }}" target="_blank">
                                <img height="80px" src="{{ config('app_settings.logo') }}" alt="{{ config('app.name') }}">
                            </a>
                        @else
                            <span style="{{ $fontFamily }} {{ $style['email-masthead_name'] }}">
                                <img height="80px" src="{{ config('app_settings.logo') }}" alt="{{ config('app.name') }}">
                                </span>
                        @endif
                    </td>
                </tr>

                <!-- Email Body -->
                <tr>
                    <td style="{{ $style['email-body'] }}" width="100%">
                        <table style="{{ $style['email-body_inner'] }}" align="center" width="570" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="{{ $fontFamily }} {{ $style['email-body_cell'] }}">
                                    <!-- Greeting -->
                                    <h1 style="{{ $style['header-1'] }}">
                                        Cześć {{ $email }}
                                    </h1>
                                    <p style="{{ $style['paragraph'] }}">
                                    Do niniejszej wiadomości została dołączona faktura {{ $invoice->number }}<br>
                                        z dnia {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}.<br>
                                    Wartość faktury wynosi {{ denormalize_price($invoice->price_gross) }} PLN,<br>
                                    @if($invoice->paymentMethod->paymentPostponed())
                                        termin płatności upływa {{ \Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)->format('d.m.Y') }}.
                                    </p>
                                    Do dyspozycji są następujące formy płatności:
                                    <ol>
                                        <li>Przelew na konto bankowe:<br>
                                            Bank: {{ $invoice->invoiceCompany->bank_name }}<br>
                                            Numer konta: {{ $invoice->invoiceCompany->bank_account_number }}<br>
                                            {{ $invoice->invoiceCompany->name }}<br>
                                            {{ $invoice->invoiceCompany->main_address_street }} {{ $invoice->invoiceCompany->main_address_number }}
                                            {{ $invoice->invoiceCompany->main_address_zip_code }} {{ $invoice->invoiceCompany->main_address_city }}
                                            {{ $invoice->invoiceCompany->vatin }}
                                        </li>
                                    </ol>
                                    @else
                                        opłacono gotówką.
                                    @endif

                                <!-- Salutation -->
                                    <p style="{{ $style['paragraph'] }}">
                                        Pozdrowienia,<br>{{ $invoice->invoiceCompany->name }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td>
                        <table style="{{ $style['email-footer'] }}" align="center" width="570" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="{{ $fontFamily }} {{ $style['email-footer_cell'] }}">
                                    <p style="{{ $style['footer-paragraph-sub'] }}">
                                        &copy; {{ date('Y') }}
                                        @if (config('app_settings.welcome_absolute_url'))
                                            <a style="{{ $style['footer-anchor'] }}" href="{{ config('app_settings.welcome_absolute_url') }}" target="_blank">{{ config('app.name') }}</a>.
                                        @else
                                            {{ config('app.name') }}
                                        @endif
                                        {{ trans('emails.default.reserved') }}.

                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
