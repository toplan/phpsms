<?php

namespace Toplan\PhpSms;

interface TemplateVoice
{
    /**
     * Template voice send process.
     *
     * @param string|array $to
     * @param int|string   $tempId
     * @param array        $tempData
     * @param array        $params
     */
    public function sendTemplateVoice($to, $tempId, array $tempData, array $params);
}
