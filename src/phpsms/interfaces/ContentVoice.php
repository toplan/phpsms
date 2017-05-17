<?php

namespace Toplan\PhpSms;

interface ContentVoice
{
    /**
     * Content voice send process.
     *
     * @param string|array $to
     * @param string       $content
     */
    public function sendContentVoice($to, $content);
}
