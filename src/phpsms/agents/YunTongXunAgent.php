<?php

namespace Toplan\PhpSms;

use REST;

/**
 * Class YunTongXunAgent
 *
 * @property string $serverIP
 * @property string $serverPort
 * @property string $softVersion
 * @property string $bodyType
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
            $this->softVersion,
            $this->bodyType
        );
        $rest->setAccount($this->accountSid, $this->accountToken);
        $rest->setAppId($this->appId);
        // 发送模板短信
        $data = array_values($data);
        $result = $rest->sendTemplateSMS($to, $data, $tempId);
        $this->setResult($result);
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
            $this->bodyType
        );
        $rest->setAccount($this->accountSid, $this->accountToken);
        $rest->setAppId($this->appId);

        // 调用语音验证码接口
        $playTimes = intval($this->playTimes ?: 3);
        $lang = $this->voiceLang ?: 'zh';
        $userData = $respUrl = null;
        $result = $rest->voiceVerify($code, $playTimes, $to, null, $respUrl, $lang, $userData, null, null);
        $this->setResult($result);
    }

    protected function setResult($result)
    {
        if (!$result) {
            return;
        }
        $code = (string) $result->statusCode;
        $success = $code === '000000';
        $info = $code;
        if ($success) {
            $info = (string) $result->statusMsg;
        } else {
            if (isset($result->TemplateSMS)) {
                $info = 'smsSid:' . $result->TemplateSMS->smsMessageSid;
            } elseif (isset($result->VoiceVerify)) {
                $info = 'callSid:' . $result->VoiceVerify->callSid;
            }
        }
        $this->result(Agent::SUCCESS, $success);
        $this->result(Agent::CODE, $code);
        $this->result(Agent::INFO, $info);
    }
}
