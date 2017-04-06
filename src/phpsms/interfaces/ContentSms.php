<?php

namespace Toplan\PhpSms;

interface ContentSms
{
    /**
     * Content SMS send process.
     *
     * @param $to
     * @param $content
     */
    public function sendContentSms($to, $content);
}
