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
        $config = [
            'accountsid' => $this->accountSid,
            'token'      => $this->accountToken,
        ];
        $ucpaas = new \Ucpaas($config);
        $response = $ucpaas->templateSMS($this->appId, $to, $tempId, implode(',', $data));
        $result = json_decode($response);
        $this->setResult($result);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $config = [
            'accountsid' => $this->accountSid,
            'token'      => $this->accountToken,
        ];
        $ucpass = new \Ucpaas($config);
        $response = $ucpass->voiceCode($this->appId, $code, $to, $type = 'json');
        $result = json_decode($response);
        $this->result($result);
    }

    protected function setResult($result)
    {
        if (empty($result) || !is_object($result)) {
            $this->result(Agent::INFO, '请求失败');

            return;
        }
        $this->result(Agent::SUCCESS, $result->resp->respCode === '000000');
        $this->result(Agent::CODE, $result->resp->respCode);
        $this->result(Agent::INFO, $result->resp->respCode);
    }

    public function sendContentSms($to, $content)
    {
    }
}
