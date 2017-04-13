<?php

namespace Toplan\PhpSms;

/**
 * Class AliyunSmsAgent
 *
 * @property string $accessKeyId
 * @property string $accessKeySecret
 * @property string $signName
 */
class AliyunSmsAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendTemplateSms($to, $tempId, $data);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $params = [
            'Action'            => 'SingleSendSms',
            'SignName'          => $this->signName,
            'ParamString'       => $this->getTempDataString($data),
            'RecNum'            => $to,
            'TemplateCode'      => $tempId,
        ];
        $this->request($params);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
    }

    protected function request(array $params)
    {
        $sendUrl = $this->sendUrl ?: 'https://sms.aliyuncs.com';
        $params = $this->createParams($params);
        $result = $this->curl($sendUrl, $params, true);
        $this->setResult($result);
    }

    protected function createParams(array $params)
    {
        date_default_timezone_set('GMT');
        $params = array_merge([
            'Format'             => 'JSON',
            'Version'            => '2016-09-27',
            'AccessKeyId'        => $this->accessKeyId,
            'SignatureMethod'    => 'HMAC-SHA1',
            'Timestamp'          => date('Y-m-d\TH:i:s\Z'),
            'SignatureVersion'   => '1.0',
            'SignatureNonce'     => uniqid(),
        ], $params);
        $params['Signature'] = $this->computeSignature($params);

        return $params;
    }

    private function computeSignature($parameters)
    {
        ksort($parameters);
        $canonicalizedQueryString = '';
        foreach ($parameters as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }
        $stringToSign = 'POST&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret . '&', true));

        return $signature;
    }

    protected function percentEncode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);

        return $res;
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $result = json_decode($result['response'], true);
            //dump($result);
            if (isset($result['Message'])) {
                $this->result(Agent::INFO, json_encode($result));
                $this->result(Agent::CODE, $result['Code']);
            } else {
                $this->result(Agent::SUCCESS, true);
                $this->result(Agent::INFO, json_encode($result));
                $this->result(Agent::CODE, 0);
            }
        } else {
            $this->result(Agent::INFO, '请求失败');
        }
    }

    protected function getTempDataString(array $data)
    {
        $data = array_map(function ($value) {
            return (string) $value;
        }, $data);

        return json_encode($data);
    }

    public function sendContentSms($to, $content)
    {
    }
}
