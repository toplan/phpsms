<?php

namespace Toplan\PhpSms;

/**
 * Class LogAgent
 */
class LogAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        if ($content) {
            $this->sendContentSms($to, $content);
        } else {
            $this->sendTemplateSms($to, $tempId, $data);
        }
    }

    public function sendContentSms($to, $content)
    {
        $this->result(Agent::SUCCESS, true);
        $this->result(Agent::INFO, 'send content sms success');
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $this->result(Agent::SUCCESS, true);
        $this->result(Agent::INFO, 'send template sms success');
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $this->result(Agent::SUCCESS, true);
        $this->result(Agent::INFO, 'send voice verify success');
    }
}
