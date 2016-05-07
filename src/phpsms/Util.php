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
     * @param bool          $isSet
     *
     * @return mixed
     */
    public static function operateArray(array &$arr, $key, $value = null, $getDefault = null, \Closure $setAction = null, $override = false, $isSet = false)
    {
        if (($key === null || is_string($key) || is_int($key)) && $value === null && !$isSet) {
            return $key === null ? $arr :
                (isset($arr[$key]) ? $arr[$key] : $getDefault);
        }
        if (is_array($key) || is_object($key)) {
            $index = 0;
            if (empty($key) && $override) {
                $arr = [];
            }
            foreach ($key as $k => $v) {
                self::operateArray($arr, $k, $v, $getDefault, $setAction, ($override && !($index)), true);
                $index++;
            }

            return $arr;
        }
        if ($override) {
            $arr = [];
        }
        if (is_callable($setAction)) {
            call_user_func_array($setAction, [$key, $value, $override]);
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
