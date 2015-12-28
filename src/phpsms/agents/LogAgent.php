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
        if (is_callable($this->sendSms)) {
            //寄生代理器的发送短信
            $result = call_user_func_array($this->sendSms, [$this, $to, $tempId, $data, $content]);
            if ($result) {
                $this->result = $result;
            }

            return;
        }
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
        if (is_callable($this->voiceVerify)) {
            //寄生代理器发送语音验证码
            $result = call_user_func_array($this->voiceVerify, [$this, $to, $code]);
            if ($result) {
                $this->result = $result;
            }

            return;
        }
        $this->result['success'] = true;
        $this->result['info'] = "send voice verify to $to success [code = $code]";
    }
}
