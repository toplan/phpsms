<?php

namespace Toplan\PhpSms;

class Util
{
    /**
     * 对数组进行赋值/取值操作
     *
     * @param array         $arr
     * @param mixed         $key
     * @param mixed         $value
     * @param mixed         $getDefault
     * @param \Closure|null $setAction
     * @param bool          $override
     * @param \Closure|null $beforeOverride
     * @param bool          $isSet
     *
     * @return mixed
     */
    public static function operateArray(array &$arr, $key, $value = null, $getDefault = null, \Closure $setAction = null, $override = false, $beforeOverride = null, $isSet = false)
    {
        if (!$isSet && ($key === null || is_string($key) || is_int($key)) && $value === null) {
            return $key === null ? $arr :
                (isset($arr[$key]) ? $arr[$key] : $getDefault);
        }
        if ($override) {
            if (is_callable($beforeOverride)) {
                call_user_func_array($beforeOverride, [$arr]);
            }
            $arr = [];
        }
        if (is_array($key) || is_object($key)) {
            foreach ($key as $k => $v) {
                self::operateArray($arr, $k, $v, $getDefault, $setAction, false, null, true);
            }
        } elseif (is_callable($setAction)) {
            call_user_func_array($setAction, [$key, $value]);
        } else {
            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * Pull the value from the specified array by key.
     *
     * @param array      $options
     * @param int|string $key
     *
     * @return mixed
     */
    public static function pullFromArrayByKey(array &$options, $key)
    {
        if (!isset($options[$key])) {
            return;
        }
        $value = $options[$key];
        unset($options[$key]);

        return $value;
    }
}
