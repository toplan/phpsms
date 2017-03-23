<?php

namespace Toplan\PhpSms\Facades;

use Illuminate\Support\Facades\Facade;

class Sms extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Toplan\\PhpSms\\Sms';
    }
}
