<?php

namespace Toplan\PhpSms;

/**
 * Class LogAgent
 */
class LogAgent extends Agent
{
    public function sendSms($tempId, $to, array $tempData, $content)
    {
        if ($content) {
            $this->sendContentSms($to, $content);
        } else {
            $this->sendTemplateSms($tempId, $to, $tempData);
        }
    }

    public function sendContentSms($to, $content)
    {
        $this->result['success'] = true;
        $this->result['info'] = 'send content sms success';
    }

    public function sendTemplateSms($tempId, $to, array $tempData)
    {
        $this->result['success'] = true;
        $this->result['info'] = 'send template sms success';
    }

    public function voiceVerify($to, $code)
    {
        $this->result['success'] = true;
        $this->result['info'] = "send voice verify success";
    }
}
