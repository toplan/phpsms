<?php

namespace Toplan\PhpSms;

/**
 * Class LogAgent
 */
class LogAgent extends Agent implements TemplateSms, ContentSms, VoiceCode
{
    public function sendContentSms($to, $content, array $params)
    {
        $this->result(Agent::SUCCESS, true);
        $this->result(Agent::INFO, 'send content sms success');
    }

    public function sendTemplateSms($to, $tempId, array $data, array $params)
    {
        $this->result(Agent::SUCCESS, true);
        $this->result(Agent::INFO, 'send template sms success');
    }

    public function sendVoiceCode($to, $code, array $params)
    {
        $this->result(Agent::SUCCESS, true);
        $this->result(Agent::INFO, 'send voice verify success');
    }
}
