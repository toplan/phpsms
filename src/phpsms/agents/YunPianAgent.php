<?php
namespace Toplan\PhpSms;

class YunPianAgent extends Agent
{
    public function sendSms($tempId, $to, Array $data, $content)
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
        if ($data['code'] == 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $data['msg'];
        $this->result['code'] = $data['code'];
    }

    public function sendTemplateSms($tempId, $to, Array $data)
    {
    }

    public function voiceVerify($to, $code)
    {
        $url = 'http://voice.yunpian.com/v1/voice/send.json';
        $apikey = $this->apikey;

        $postString = "apikey=$apikey&code=$code&mobile=$to";
        $response = $this->sockPost($url, $postString);

        $data = json_decode($response, true);
        if ($data['code'] == 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $data['msg'];
        $this->result['code'] = $data['code'];
    }
}
