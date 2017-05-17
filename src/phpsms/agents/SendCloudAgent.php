<?php

namespace Toplan\PhpSms;

/**
 * Class SendCloudAgent
 *
 * @property string $smsUser
 * @property string $smsKey
 */
class SendCloudAgent extends Agent implements TemplateSms, VoiceCode
{
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

    public function sendVoiceCode($to, $code)
    {
        $params = [
            'phone' => $to,
            'code'  => $code,
        ];
        $this->request('http://sendcloud.sohu.com/smsapi/sendVoice', $params);
    }

    protected function request($sendUrl, array $params)
    {
        $params['smsUser'] = $this->smsUser;
        $params['signature'] = $this->genSign($params);
        $result = $this->curlPost($sendUrl, $params);
        $this->setResult($result);
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
        if ($result['request']) {
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            if (isset($result['result'])) {
                $this->result(Agent::SUCCESS, (bool) $result['result']);
                $this->result(Agent::CODE, $result['statusCode']);
            }
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }

    protected function getTempDataString(array $data)
    {
        return json_encode(array_map('strval', $data));
    }
}
