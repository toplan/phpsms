<?php

namespace Toplan\PhpSms;

class UcpaasAgent extends Agent
{
    public function sendSms($tempId, $to, array $data, $content)
    {
        $this->sendTemplateSms($tempId, $to, $data);
    }

    public function sendContentSms($to, $content)
    {
    }

    public function sendTemplateSms($tempId, $to, array $data)
    {
        $config = [
            'accountsid' => $this->accountSid,
            'token'      => $this->accountToken,
        ];
        $ucpaas = new \Ucpaas($config);
        $response = $ucpaas->templateSMS($this->appId, $to, $tempId, implode(',', $data));
        $result = json_decode($response);
        if ($result !== null && $result->resp->respCode === '000000') {
            $this->result['success'] = true;
        }
        $this->result['info'] = $result->resp->respCode;
        $this->result['code'] = $result->resp->respCode;
    }

    public function voiceVerify($to, $code)
    {
        $config = [
            'accountsid' => $this->accountSid,
            'token'      => $this->accountToken,
        ];
        $ucpass = new \Ucpaas($config);
        $response = $ucpass->voiceCode($this->appId, $code, $to, $type = 'json');
        $result = json_decode($response);
        if ($result === null) {
            return $this->result;
        }
        if ($result->resp->respCode === '000000') {
            $this->result['success'] = true;
        }
        $this->result['info'] = $result->resp->respCode;
        $this->result['code'] = $result->resp->respCode;

        return $this->result;
    }
}
