<?php

namespace Toplan\PhpSms;

/**
 * Class LogAgent
 * 测试|寄生 代理器
 *
 * @property string $sendSms
 * @property string $voiceVerify
 */
class LogAgent extends Agent
{
    public function sendSms($tempId, $to, array $tempData, $content)
    {
        //作为寄生代理器发送短信
        if (is_callable($this->sendSms)) {
            $smsData = compact('to', 'tempId', 'tempData', 'content');
            call_user_func_array($this->sendSms, [$this, $smsData]);

            return;
        }
        //作为测试代理器
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
        //作为寄生代理器发送语音验证码
        if (is_callable($this->voiceVerify)) {
            $data = compact('to', 'code');
            call_user_func_array($this->voiceVerify, [$this, $data]);

            return;
        }
        //作为测试代理器
        $this->result['success'] = true;
        $this->result['info'] = "send voice verify to $to success [code = $code]";
    }
}
