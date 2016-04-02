<?php

namespace Toplan\PhpSms;

use REST;

/**
 * Class YunTongXunAgent
 *
 * @property string $serverIP
 * @property string $serverPort
 * @property string $softVersion
 * @property string $accountSid
 * @property string $accountToken
 * @property string $appId
 * @property int $playTimes
 * @property string $voiceLang
 */
class YunTongXunAgent extends Agent
{
    public function sendSms($tempId, $to, array $data, $content)
    {
        $this->sendTemplateSms($tempId, $to, $data);
    }

    public function sendTemplateSms($tempId, $to, array $data)
    {
        // 初始化REST SDK
        $rest = new REST(
            $this->serverIP,
            $this->serverPort,
            $this->softVersion
        );
        $rest->setAccount($this->accountSid, $this->accountToken);
        $rest->setAppId($this->appId);
        // 发送模板短信
        $data = array_values($data);
        $result = $rest->sendTemplateSMS($to, $data, $tempId);
        if ($result) {
            $code = (string) $result->statusCode;
            if ($code === '000000') {
                $this->result['success'] = true;
                $this->result['code'] = $code;
                $this->result['info'] = 'smsSid:' . $result->templateSMS->smsMessageSid;
            } else {
                $this->result['code'] = $code;
                $this->result['info'] = (string) $result->statusMsg;
            }
        }
    }

    public function sendContentSms($to, $content)
    {
    }

    public function voiceVerify($to, $code)
    {
        // 初始化REST SDK
        $rest = new REST(
            $this->serverIP,
            $this->serverPort,
            $this->softVersion
        );
        $rest->setAccount($this->accountSid, $this->accountToken);
        $rest->setAppId($this->appId);

        // 调用语音验证码接口
        $playTimes = intval($this->playTimes ?: 3);
        $lang = $this->voiceLang ?: 'zh';
        $userData = $respUrl = null;
        $result = $rest->voiceVerify($code, $playTimes, $to, null, $respUrl, $lang, $userData, null, null);
        if ($result) {
            $code = (string) $result->statusCode;
            if ($code === '000000') {
                $this->result['success'] = true;
                $this->result['code'] = $code;
                $this->result['info'] = 'callSid:' . $result->VoiceVerify->callSid;
            } else {
                $this->result['code'] = $code;
                $this->result['info'] = (string) $result->statusMsg;
            }
        }
    }
}
