<?php

namespace Toplan\PhpSms;

/**
 * Class AliyunAgent
 *
 * @property string $accessKeyId
 * @property string $accessKeySecret
 * @property string $signName
 * @property string $regionId
 */
class AliyunAgent extends Agent implements TemplateSms
{
    protected static $sendUrl = 'https://dysmsapi.aliyuncs.com/';

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $params = [
            'Action'            => 'SendSms',
            'SignName'          => $this->signName,
            'TemplateParam'     => $this->getTempDataString($data),
            'PhoneNumbers'      => $to,
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
        $params = array_merge([
            'RegionId'          => $this->regionId ?: 'cn-shenzhen',
            'Format'            => 'JSON',
            'Version'           => '2017-05-25',
            'AccessKeyId'       => $this->accessKeyId,
            'SignatureMethod'   => 'HMAC-SHA1',
            'Timestamp'         => gmdate('Y-m-d\TH:i:s\Z'),
            'SignatureVersion'  => '1.0',
            'SignatureNonce'    => uniqid(),
        ], $params);
        $params['Signature'] = $this->computeSignature($params);

        return $this->params($params);
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
            $this->result(Agent::CODE, $result['Code']);
            if ($result['Code'] === 'OK') {
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
