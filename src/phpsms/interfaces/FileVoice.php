<?php

namespace Toplan\PhpSms;

interface FileVoice
{
    /**
     * File voice send process.
     *
     * @param string|array  $to
     * @param int|string    $fileId
     * @param array         $params
     */
    public function sendFileVoice($to, $fileId, array $params);
}
