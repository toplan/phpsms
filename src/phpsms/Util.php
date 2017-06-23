<?php

namespace Toplan\PhpSms;

use SuperClosure\Serializer;

class Util
{
    protected static $closureSerializer = null;

    public static function operateArray(array &$array, $key, $value = null, $default = null, \Closure $setter = null, $override = false, $willOverride = null, $isSet = false)
    {
        if (!$isSet && ($key === null || is_string($key) || is_int($key)) && $value === null) {
            return $key === null ? $array :
                (isset($array[$key]) ? $array[$key] : $default);
        }
        if ($override) {
            if (is_callable($willOverride)) {
                call_user_func_array($willOverride, [$array]);
            }
            $array = [];
        }
        if (is_array($key) || is_object($key)) {
            foreach ($key as $k => $v) {
                self::operateArray($array, $k, $v, $default, $setter, false, null, true);
            }
        } elseif (is_callable($setter)) {
            call_user_func_array($setter, [$key, $value]);
        } else {
            $array[$key] = $value;
        }

        return $array;
    }

    public static function pullFromArray(array &$options, $key)
    {
        $value = null;
        if (!isset($options[$key])) {
            return $value;
        }
        $value = $options[$key];
        unset($options[$key]);

        return $value;
    }

    public static function getClosureSerializer()
    {
        if (empty(self::$closureSerializer)) {
            self::$closureSerializer = new Serializer();
        }

        return self::$closureSerializer;
    }

    public static function formatMobiles($target)
    {
        if (!is_array($target)) {
            return [$target];
        }
        $list = [];
        $nation = $number = null;
        $count = count($target);
        if ($count === 2) {
            $firstItem = $target[0];
            if (is_int($firstItem) && $firstItem > 0 && $firstItem <= 9999) {
                $nation = $firstItem;
                $number = $target[1];
            }
            if (is_string($firstItem) && strlen($firstItem = trim($firstItem)) <= 4) {
                $nation = $firstItem;
                $number = $target[1];
            }
        }
        if (!is_null($nation)) {
            return [compact('nation', 'number')];
        }
        foreach ($target as $childTarget) {
            $childList = self::formatMobiles($childTarget);
            foreach ($childList as $childListItem) {
                array_push($list, $childListItem);
            }
        }

        return $list;
    }
}
