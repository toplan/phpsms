<?php

namespace Toplan\PhpSms;

interface FileVoice
{
    /**
     * File voice send process.
     *
     * @param string|array $to
     * @param int|string   $fileId
     */
    public function sendFileVoice($to, $fileId);
}
