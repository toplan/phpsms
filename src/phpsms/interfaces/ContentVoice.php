<?php

namespace Toplan\PhpSms;

interface ContentVoice
{
    /**
     * Content voice send process.
     *
     * @param string|array $to
     * @param string       $content
     * @param array        $params
     */
    public function sendContentVoice($to, $content, array $params);
}
