<?php

namespace Toplan\PhpSms;

/**
 * Class LuosimaoAgent
 *
 * @property string $apikey
 * @property string $voiceApikey
 */
class LuosimaoAgent extends Agent implements ContentSms, VoiceCode
{
    public function sendContentSms($to, $content)
    {
        // 签名必须在最后面
        if ($content && !preg_match('/】$/', $content)) {
            preg_match('/【([0-9a-zA-Z\W]+)】/', $content, $matches);
            if (isset($matches[0])) {
                $content = str_replace($matches[0], '', $content) . $matches[0];
            }
        }
        $url = 'http://sms-api.luosimao.com/v1/send.json';
        $result = $this->curlPost($url, [
            'mobile'  => $to,
            'message' => $content,
        ], [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD  => "api:key-$this->apikey",
        ]);
        $this->setResult($result);
    }

    public function sendVoiceCode($to, $code)
    {
        $url = 'http://voice-api.luosimao.com/v1/verify.json';
        $result = $this->curlPost($url, [
            'mobile' => $to,
            'code'   => $code,
        ], [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD  => "api:key-$this->voiceApikey",
        ]);
        $this->setResult($result);
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            $this->result(Agent::SUCCESS, $result['error'] === 0);
            $this->result(Agent::CODE, $result['error']);
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }
}
