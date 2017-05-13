<?php

namespace Toplan\PhpSms;

interface VoiceCode
{
    /**
     * Voice code send process.
     *
     * @param string|array $to
     * @param int|string   $code
     * @param array        $params
     */
    public function sendVoiceCode($to, $code, array $params);
}
