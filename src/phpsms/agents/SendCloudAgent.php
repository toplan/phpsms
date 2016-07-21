<?php

namespace Toplan\PhpSms;

/**
 * Class SendCloudAgent
 *
 * @property string $smsUser
 * @property string $smsKey
 */
class SendCloudAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendTemplateSms($to, $tempId, $data);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $params = [
            'msgType'    => 0,
            'vars'       => $this->getTempDataString($data),
            'phone'      => $to,
            'templateId' => $tempId,
        ];
        $this->request('http://sendcloud.sohu.com/smsapi/send', $params);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $params = [
            'phone' => $to,
            'code'  => $code,
        ];
        $this->request('http://sendcloud.sohu.com/smsapi/sendVoice', $params);
    }

    protected function request($sendUrl, array $params)
    {
        $params = $this->createParams($params);
        $result = $this->curl($sendUrl, $params, true);
        $this->setResult($result);
    }

    protected function createParams(array $params)
    {
        $params = array_merge([
            'smsUser' => $this->smsUser,
            ], $params);
        $params['signature'] = $this->genSign($params);

        return $params;
    }

    protected function genSign($params)
    {
        ksort($params);
        $stringToBeSigned = '';
        foreach ($params as $k => $v) {
            $stringToBeSigned .= $k . '=' . $v . '&';
        }
        $stringToBeSigned = trim($stringToBeSigned, '&');
        $stringToBeSigned = $this->smsKey . '&' . $stringToBeSigned . '&' . $this->smsKey;

        return md5($stringToBeSigned);
    }

    protected function setResult($result)
    {
        if ($result) {
            $response = json_decode($result['response'], true);
            if (isset($response['result'])) {
                $this->result(Agent::SUCCESS, (bool) $response['result']);
                $this->result(Agent::INFO, $response['message']);
                $this->result(Agent::CODE, $response['statusCode']);
            }
        } else {
            $this->result(Agent::INFO, '请求失败');
        }
    }

    protected function getTempDataString(array $data)
    {
        return json_encode(array_map('strval', $data));
    }

    public function sendContentSms($to, $content)
    {
    }
}
