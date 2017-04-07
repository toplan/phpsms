<?php

namespace Toplan\PhpSms;

class Util
{
    /**
     * 对数组进行赋值/取值操作
     *
     * @param array         $array
     * @param mixed         $key
     * @param mixed         $value
     * @param mixed         $default
     * @param \Closure|null $setter
     * @param bool          $override
     * @param \Closure|null $willOverride
     * @param bool          $isSet
     *
     * @return mixed
     */
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

    /**
     * 从数组中根据指定键名拉取数据
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
