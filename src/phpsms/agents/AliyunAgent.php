<?php

namespace Toplan\PhpSms;

/**
 * Class AliyunAgent
 *
 * @property string $accessKeyId
 * @property string $accessKeySecret
 * @property string $signName
 */
class AliyunAgent extends Agent implements TemplateSms
{
    protected static $sendUrl = 'https://sms.aliyuncs.com';

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

    protected function request(array $params)
    {
        $params = $this->createParams($params);
        $result = $this->curlPost(self::$sendUrl, [], [
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);
        $this->setResult($result);
    }

    protected function createParams(array $params)
    {
        return $this->params(array_merge([
            'Format'            => 'JSON',
            'Version'           => '2016-09-27',
            'AccessKeyId'       => $this->accessKeyId,
            'SignatureMethod'   => 'HMAC-SHA1',
            'Timestamp'         => date('Y-m-d\TH:i:s\Z'),
            'SignatureVersion'  => '1.0',
            'SignatureNonce'    => uniqid(),
        ], $params, [
            'Signature'         => $this->computeSignature($params),
        ]));
    }

    private function computeSignature($parameters)
    {
        ksort($parameters);
        $canonicalizedQueryString = '';
        foreach ($parameters as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }
        $stringToSign = 'POST&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));

        return base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret . '&', true));
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
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            if (isset($result['Message'])) {
                $this->result(Agent::CODE, $result['Code']);
            } else {
                $this->result(Agent::SUCCESS, true);
            }
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }

    protected function getTempDataString(array $data)
    {
        $data = array_map(function ($value) {
            return (string) $value;
        }, $data);

        return json_encode($data);
    }
}
