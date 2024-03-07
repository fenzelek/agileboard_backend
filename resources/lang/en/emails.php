<?php

return [

    /* DEFAULT */
    'default' => [
        'greeting' => 'Hi',
        'regards' => 'Team '.config('app.name'),
        'dictionary_regards' => 'Regards',
        'whooops' => 'Whooops!',
        'hi' => 'Hi!',
        'trouble_with_button_1' => 'If the ',
        'trouble_with_button_2' => 'button does not work, copy the URL below and paste it into your browser\'s address bar:',
        'reserved' => 'All rights reserved',
    ],

    /* PASSWORD RESET */
    'reset_password' => [
        'subject' => 'Password reset for '.config('app.name'),
        'line_1' => 'You are receiving this email because we received a password reset request for your account.',
        'line_2' => 'If you did not request a password reset, no further action is required.',
        'action' => 'Reset Password',
    ],

    /* NEW USER INVITATION CREATED */
    'new_user_invitation_created' => [
        'subject' => 'Invitation from ',
        'line_1' => 'You are receiving this email because you have invitation from company ',
        'line_2' => 'You invitation expires ',
        'action' => 'Accept invitation',
    ],

    /* ACTIVATION */
    'activation' => [
        'subject' => 'Activation Account for application '.config('app.name'),
        'line_1' => 'You are receiving this email because you need to activate your account.',
        'line_2' => 'If you did not create account, no further action is required.',
        'action' => 'Activate account',
    ],

    /* PAYMENT STATUS INFO */
    'payment_status_info_completed' => [
        'subject' => 'Payment completed successfully - '.config('app.name'),
        'line_1' => 'Your payment with number :number has been successfully completed.',
    ],
    'payment_status_info_canceled' => [
        'subject' => 'Payment canceled - '.config('app.name'),
        'line_1' => 'Your payment with the number :number has been canceled by you or by the bank.',
    ],

    /* SUBSCRIPTION CANCELED */
    'payment_subscription_canceled' => [
        'subject' => 'Subscription canceled - '.config('app.name'),
        'line_1' => 'One or more of your subscriptions has been canceled because we were unable to automatically collect the payment. If you want to use the paid version again, go to the company management and order selected packages and modules.',
    ],

    /* RENEW SUBSCRIPTION INFORMATION */
    'renew_subscription_information' => [
        'subject' => 'Renew subscription - '.config('app.name'),
        'line_1_14' => 'Please be advised that one of your subscriptions will be renewed in 14 days. If you want to opt out of it, go to manage the company and change the settings of packages/modules.',
        'line_1_1' => 'Please be advised that one of your subscriptions will be renewed in the next 24 hours. If you want to opt out of it, go to manage the company and change the settings of packages/modules.',
    ],

    /* REMIND EXPIRING */
    'remind_expiring_package' => [
        'subject' => 'Your package expires in :days days - '.config('app.name'),
        'subject_1' => 'Your package expires in 1 day - '.config('app.name'),
        'line_1' => 'Please be advised that your current package will expire in :days days. If you want to continue using it, go to the company management and make a payment.',
        'line_1_1' => 'Please be advised that your current package will expire in the next 24 hour. If you want to continue using it, go to the company management and make a payment.',
    ],
    'remind_expiring_module' => [
        'subject' => 'Your module expires in :days days - '.config('app.name'),
        'subject_1' => 'Your module expires in 1 day  - '.config('app.name'),
        'line_1' => 'Please be advised that one of your active modules will expire in :days days. If you want to continue using it, go to the company management and make a payment.',
        'line_1_1' => 'Please be advised that one of your active modules will expire in the next 24 hours. JIf you want to continue using it, go to the company management and make a payment.',
    ],

    /* OVERTIME ADDED */
    'overtime_information' => [
        'subject' => 'The employee added overtime',
        'line_1' => 'One or more time periods were added by: ',
        'line_2' => 'Please check dashboard AB',
    ],
];
