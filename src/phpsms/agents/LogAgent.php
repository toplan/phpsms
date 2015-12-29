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
    public function sendSms($tempId, $to, array $data, $content)
    {
        //作为寄生代理器发送短信
        if (is_callable($this->sendSms)) {
            call_user_func_array($this->sendSms, [$this, $to, $tempId, $data, $content]);

            return;
        }
        //作为测试代理器
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
        //作为寄生代理器发送语音验证码
        if (is_callable($this->voiceVerify)) {
            call_user_func_array($this->voiceVerify, [$this, $to, $code]);

            return;
        }
        //作为测试代理器
        $this->result['success'] = true;
        $this->result['info'] = "send voice verify to $to success [code = $code]";
    }
}
