<?php

return [

    'invitations' => [
        /*
         * Expire time of invitation to company (in minutes) 
         */
        'expire_time' => 1440,
    ],

    /*
     * Logo of application
     * Full path of file logo with extension
    */
    'logo' => env('APP_LOGO_FULL_PATH'),

    /*
     * Min logo of application
     * Full path of file logo with extension
    */
    'logo_min' => env('APP_LOGO_MIN_FULL_PATH'),

    /*
     * Welcome url of application
     * Absolute url placed in email notifications
    */
    'welcome_absolute_url' => env('APP_WELCOME_ABSOLUTE_URL'),

    /*
     * Url to ticket in front
     * Relative path to ticket
    */
    'ticket_url' => env('APP_TICKET_URL'),

    /*
     * Url to board in front
     * Relative path to board
    */
    'board_url' => env('APP_BOARD_URL'),

    /*
     * Clipboard cleanup period.
     */
    'cleanup_clipboard' => env('CLEANUP_CLIPBOARD', 7),

    /*
     * Packages
     */
    'package_portal_name' => env('PACKAGE_PORTAL_NAME'),

];
