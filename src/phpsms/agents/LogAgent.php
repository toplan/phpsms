<?php

namespace Toplan\PhpSms;

class LogAgent extends Agent
{
    public function sendSms($tempId, $to, array $data, $content)
    {
        if ($content) {
            $this->sendContentSms($to, $content);
        } else {
            $this->sendTemplateSms($tempId, $to, $data);
        }
    }

    public function sendContentSms($to, $content)
    {
        $this->result['success'] = true;
        $this->result['info'] = 'send content sms success';
    }

    public function sendTemplateSms($tempId, $to, array $data)
    {
        $this->result['success'] = true;
        $this->result['info'] = 'send template sms success';
    }

    public function voiceVerify($to, $code)
    {
        $this->result['success'] = true;
        $this->result['info'] = "send voice verify to $to success [code = $code]";
    }
}
