<?php

namespace Toplan\PhpSms;

use REST;

/**
 * Class YunTongXunAgent
 *
 * @property string $serverIP
 * @property string $serverPort
 * @property string $accountSid
 * @property string $accountToken
 * @property string $appId
 * @property int    $playTimes
 * @property string $voiceLang
 * @property string $displayNum
 */
class YunTongXunAgent extends Agent implements TemplateSms, VoiceCode
{
    public function sendTemplateSms($to, $tempId, array $data, array $params)
    {
        $data = array_values($data);
        $result = $this->rest()->sendTemplateSMS($to, $data, $tempId);
        $this->setResult($result);
    }

    public function sendVoiceCode($to, $code, array $params)
    {
        $playTimes = intval($this->playTimes ?: 3);
        $displayNum = $this->displayNum ?: null;
        $lang = $this->voiceLang ?: 'zh';
        $result = $this->rest()->voiceVerify($code, $playTimes, $to, $displayNum, null, $lang);
        $this->setResult($result);
    }

    protected function rest()
    {
        $rest = new REST($this->serverIP, $this->serverPort, '2013-12-26', 'json');
        $rest->setAccount($this->accountSid, $this->accountToken);
        $rest->setAppId($this->appId);

        return $rest;
    }

    protected function setResult($result)
    {
        if (!$result) {
            return;
        }
        $code = $info = (string) $result->statusCode;
        $success = $code === '000000';
        if (isset($result->statusMsg)) {
            $info = (string) $result->statusMsg;
        } elseif (isset($result->TemplateSMS)) {
            $info = 'smsSid:' . $result->TemplateSMS->smsMessageSid;
        } elseif (isset($result->VoiceVerify)) {
            $info = 'callSid:' . $result->VoiceVerify->callSid;
        }
        $this->result(Agent::SUCCESS, $success);
        $this->result(Agent::CODE, $code);
        $this->result(Agent::INFO, $info);
    }
}
