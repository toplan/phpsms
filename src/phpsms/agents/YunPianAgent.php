<?php

namespace Toplan\PhpSms;

/**
 * Class YunPianAgent
 *
 * @property string $apikey
 */
class YunPianAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendContentSms($to, $content);
    }

    public function sendContentSms($to, $content)
    {
        $url = 'http://yunpian.com/v1/sms/send.json';
        $apikey = $this->apikey;
        $content = urlencode("$content");

        $postString = "apikey=$apikey&text=$content&mobile=$to";
        $response = $this->sockPost($url, $postString);

        $data = json_decode($response, true);
        if ($data['code'] === 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $data['msg'];
        $this->result['code'] = $data['code'];
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $url = 'http://voice.yunpian.com/v1/voice/send.json';
        $apikey = $this->apikey;

        $postString = "apikey=$apikey&code=$code&mobile=$to";
        $response = $this->sockPost($url, $postString);

        $data = json_decode($response, true);
        if ($data['code'] === 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $data['msg'];
        $this->result['code'] = $data['code'];
    }
}
