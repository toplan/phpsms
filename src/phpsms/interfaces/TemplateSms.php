<?php

namespace Toplan\PhpSms;

interface TemplateSms
{
    /**
     * Template SMS send process.
     *
     * @param       $to
     * @param       $tempId
     * @param array $tempData
     */
    public function sendTemplateSms($to, $tempId, array $tempData);
}
