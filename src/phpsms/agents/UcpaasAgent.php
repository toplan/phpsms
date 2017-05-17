<?php

namespace Toplan\PhpSms;

/**
 * Class UcpaasAgent
 *
 * @property string $accountSid
 * @property string $accountToken
 * @property string $appId
 */
class UcpaasAgent extends Agent implements TemplateSms, VoiceCode
{
    public function sendTemplateSms($to, $tempId, array $data)
    {
        $response = $this->ucpass()->templateSMS($this->appId, $to, $tempId, implode(',', $data));
        $this->setResult($response);
    }

    public function sendVoiceCode($to, $code)
    {
        $response = $this->ucpass()->voiceCode($this->appId, $code, $to);
        $this->result($response);
    }

    protected function ucpass()
    {
        return new \Ucpaas([
            'accountsid' => $this->accountSid,
            'token'      => $this->accountToken,
        ]);
    }

    protected function setResult($result)
    {
        $result = json_decode($result);
        if (!$result) {
            return $this->result(Agent::INFO, 'request failed');
        }
        $this->result(Agent::SUCCESS, $result->resp->respCode === '000000');
        $this->result(Agent::CODE, $result->resp->respCode);
        $this->result(Agent::INFO, json_encode($result->resp));
    }
}
