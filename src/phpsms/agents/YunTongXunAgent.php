<?php

namespace Toplan\PhpSms;

use REST;

/**
 * Class YunTongXunAgent
 * @package Toplan\PhpSms
 *
 * @property string $serverIP
 * @property string $serverPort
 * @property string $softVersion
 * @property string $accountSid
 * @property string $accountToken
 * @property string $appId
 * @property string $appId
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
            $this->softVersion,
            null
        );
        $rest->setAccount($this->accountSid, $this->accountToken);
        $rest->setAppId($this->appId);
        // 发送模板短信
        if (is_array($data)) {
            $data = array_values($data);
        }
        $result = $rest->sendTemplateSMS($to, $data, $tempId);
        if ($result !== null && $result->statusCode === 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $result->statusCode;
        $this->result['code'] = $result->statusCode;
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
            $this->softVersion,
            null
        );
        $rest->setAccount($this->accountSid, $this->accountToken);
        $rest->setAppId($this->appId);

        // 调用语音验证码接口
        $playTimes = 3;
        $respUrl = null;
        $lang = 'zh';
        $userData = null;
        $result = $rest->voiceVerify($code, $playTimes, $to, null, $respUrl, $lang, $userData, null, null);
        if ($result === null) {
            return $this->result;
        }
        if ($result->statusCode === 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $result->statusMsg;
        $this->result['code'] = $result->statusCode;

        return $this->result;
    }
}
