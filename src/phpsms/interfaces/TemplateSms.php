<?php

namespace Toplan\PhpSms;

interface TemplateSms
{
    /**
     * Template SMS send process.
     *
     * @param string|array $to
     * @param int|string   $tempId
     * @param array        $tempData
     * @param array        $params
     */
    public function sendTemplateSms($to, $tempId, array $tempData, array $params);
}
