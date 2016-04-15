<?php

namespace Toplan\PhpSms;

class CheckFramework
{
    public static function is($name)
    {
        $name = ucfirst(strtolower(trim("$name")));
        $staticMethod = 'is' . $name;
        if (method_exists(new self(), $staticMethod)) {
            return self::$staticMethod();
        }
        return false;
    }

    public static function isLaravel()
    {
        if (function_exists('app')) {
            try {
                $laravel = app();
                return (bool) $laravel::VERSION;
            } catch (\Exception $e) {
            }
        }
        return false;
    }
}
