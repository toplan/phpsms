<?php

namespace Toplan\PhpSms;

/**
 * Class LuosimaoAgent
 *
 * @property string $apikey
 * @property string $voiceApikey
 */
class LuosimaoAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        // 签名必须在最后面
        if ($content && !preg_match('/】$/', $content)) {
            preg_match('/【([0-9a-zA-Z\W]+)】/', $content, $matches);
            if (isset($matches[0])) {
                $content = str_replace($matches[0], '', $content) . $matches[0];
            }
        }
        $this->sendContentSms($to, $content);
    }

    public function sendContentSms($to, $content)
    {
        $url = 'http://sms-api.luosimao.com/v1/send.json';
        $optData = [
            'mobile'  => $to,
            'message' => $content,
        ];
        $result = $this->curl($url, $optData, true, [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "api:key-$this->apikey"
        ]);
        $this->setResult($result);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $url = 'http://voice-api.luosimao.com/v1/verify.json';
        $optData = [
            'mobile' => $to,
            'code'   => $code,
        ];
        $result = $this->curl($url, $optData, true, [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "api:key-$this->voiceApikey"
        ]);
        $this->setResult($result);
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $result = json_decode($result['response'], true);
            $this->result(Agent::INFO, $result);
            $this->result(Agent::SUCCESS, $result['error'] === 0);
            $this->result(Agent::CODE, $result['error']);
        } else {
            $this->result(Agent::INFO, '请求失败');
        }
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
    }
}
