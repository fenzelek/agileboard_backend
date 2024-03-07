<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'This field must be accepted.',
    'active_url' => 'This field is not a valid URL.',
    'after' => 'This field must be a date after :date.',
    'alpha' => 'This field may only contain letters.',
    'alpha_dash' => 'This field may only contain letters, numbers, and dashes.',
    'alpha_num' => 'This field may only contain letters and numbers.',
    'array' => 'This field must be an array.',
    'before' => 'This field must be a date before :date.',
    'between' => [
        'numeric' => 'This field must be between :min and :max.',
        'file' => 'This field must be between :min and :max kilobytes.',
        'string' => 'This field must be between :min and :max characters.',
        'array' => 'This field must have between :min and :max items.',
    ],
    'boolean' => 'This field must be true or false.',
    'confirmed' => 'This field confirmation does not match.',
    'date' => 'This field is not a valid date.',
    'date_format' => 'This field does not match the format :format.',
    'different' => 'This field and :other must be different.',
    'digits' => 'This field must be :digits digits.',
    'digits_between' => 'This field must be between :min and :max digits.',
    'email' => 'This field must be a valid email address.',
    'exists' => 'The selected field is invalid.',
    'filled' => 'This field is required.',
    'image' => 'This field must be an image.',
    'in' => 'The selected field is invalid.',
    'integer' => 'This field must be an integer.',
    'ip' => 'This field must be a valid IP address.',
    'json' => 'This field must be a valid JSON string.',
    'max' => [
        'numeric' => 'This field may not be greater than :max.',
        'file' => 'This field may not be greater than :max kilobytes.',
        'string' => 'This field may not be greater than :max characters.',
        'array' => 'This field may not have more than :max items.',
    ],
    'mimes' => 'This field must be a file of type: :values.',
    'min' => [
        'numeric' => 'This field must be at least :min.',
        'file' => 'This field must be at least :min kilobytes.',
        'string' => 'This field must be at least :min characters.',
        'array' => 'This field must have at least :min items.',
    ],
    'not_in' => 'The selected field is invalid.',
    'numeric' => 'This field must be a number.',
    'regex' => 'This field format is invalid.',
    'required' => 'This field is required.',
    'required_if' => 'This field is required when :other is :value.',
    'required_unless' => 'This field is required unless :other is in :values.',
    'required_with' => 'This field is required when :values is present.',
    'required_with_all' => 'This field is required when :values is present.',
    'required_without' => 'This field is required when :values is not present.',
    'required_without_all' => 'This field is required when none of :values are present.',
    'same' => 'This field and :other must match.',
    'size' => [
        'numeric' => 'This field must be :size.',
        'file' => 'This field must be :size kilobytes.',
        'string' => 'This field must be :size characters.',
        'array' => 'This field must contain :size items.',
    ],
    'string' => 'This field must be a string.',
    'timezone' => 'This field must be a valid zone.',
    'unique' => 'This field has already been taken.',
    'url' => 'This field format is invalid.',
    'amount_min' => 'The value to low',
    'registries_start_number' => 'Starting number allowed only for registry with year format without created invoices',
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'invoice_registries' => [
            'invoice_registries_prefix' => 'Wrong prefix value was given',
        ],
        'addresses' => [
            'check_polish_zip_code' => 'Zip code is too long.',
        ],
        'items' => [
            'decimal_quantity' => 'Wrong quantity number was given.',
        ],
        'items.*.quantity' => [
            'decimal_quantity' => 'Number must be an integer.',
        ],
        'regon' => [
            'regex' => 'REGON should have 9 or 14 digits.',
        ],
        'email' => [
            'blacklist' => 'This email is blacklisted.'
        ],
        'ids' => 'Wrong ids',
        'project' => 'Given project not valid'
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
