<?php

namespace Toplan\PhpSms;

/**
 * Class YunPianAgent
 *
 * @property string $apikey
 */
class YunPianAgent extends Agent implements ContentSms, VoiceCode
{
    protected $headers = [
        'Accept:application/json;charset=utf-8',
        'Content-Type:application/x-www-form-urlencoded;charset=utf-8',
    ];

    public function sendContentSms($to, $content)
    {
        $url = 'https://sms.yunpian.com/v1/sms/send.json';
        $params = $this->params([
            'apikey' => $this->apikey,
            'mobile' => $to,
            'text'   => $content,
        ]);
        $result = $this->curlPost($url, [], [
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);
        $this->setResult($result);
    }

    public function sendVoiceCode($to, $code)
    {
        $url = 'https://voice.yunpian.com/v1/voice/send.json';
        $params = $this->params([
            'apikey' => $this->apikey,
            'mobile' => $to,
            'code'   => $code,
        ]);
        $result = $this->curlPost($url, [], [
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);
        $this->setResult($result);
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            $this->result(Agent::SUCCESS, $result['code'] === 0);
            $this->result(Agent::CODE, $result['code']);
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }
}
