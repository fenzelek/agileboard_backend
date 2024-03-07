<?php

namespace App\Modules\Company\Services\Payments\Validator;

class ValidatorErrors
{
    const FREE_PACKAGE_NOW_USED = 'free_package_now_used';
    const MODULE_MOD_CURRENTLY_USED = 'module_mod_currently_used';
    const MODULE_MOD_CURRENTLY_USED_CAN_EXTEND = 'module_mod_currently_used_can_extend';
    const UNAVAILABLE_VALUE = 'unavailable_value';
    const WAITING_FOR_PAYMENT = 'waiting_for_payment';
    const WRONG_CHECKSUM = 'wrong_checksum';
    const WRONG_DATA = 'wrong_data';
}
