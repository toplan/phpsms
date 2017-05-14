<?php

namespace Toplan\PhpSms;

interface ContentSms
{
    /**
     * Content SMS send process.
     *
     * @param string|array $to
     * @param string       $content
     * @param array        $params
     */
    public function sendContentSms($to, $content, array $params);
}
