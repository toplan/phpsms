<?php

namespace Toplan\PhpSms;

/**
 * Class UcpaasAgent
 *
 * @property string $accountSid
 * @property string $accountToken
 * @property string $appId
 */
class UcpaasAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendTemplateSms($to, $tempId, $data);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $response = $this->ucpass()->templateSMS($this->appId, $to, $tempId, implode(',', $data));
        $this->setResult($response);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $response = $this->ucpass()->voiceCode($this->appId, $code, $to);
        $this->result($response);
    }

    protected function ucpass()
    {
        $config = [
            'accountsid' => $this->accountSid,
            'token'      => $this->accountToken,
        ];

        return new \Ucpaas($config);
    }

    protected function setResult($result)
    {
        $result = json_decode($result);
        if (!$result) {
            $this->result(Agent::INFO, '请求失败');

            return;
        }
        $this->result(Agent::SUCCESS, $result->resp->respCode === '000000');
        $this->result(Agent::CODE, $result->resp->respCode);
        $this->result(Agent::INFO, json_encode($result->resp));
    }

    public function sendContentSms($to, $content)
    {
    }
}
