<?php

namespace Toplan\PhpSms;

/**
 * Class SubMailAgent
 * @package Toplan\PhpSms
 *
 * @property string $appid
 * @property string $signature
 */
class SubMailAgent extends Agent
{
    public function sendSms($tempId, $to, array $data, $content)
    {
        $this->sendTemplateSms($tempId, $to, $data);
    }

    public function sendContentSms($to, $content)
    {
    }

    public function sendTemplateSms($tempId, $to, array $data)
    {
        $url = 'https://api.submail.cn/message/xsend.json';
        $appid = $this->appid;
        $signature = $this->signature;
        $vars = json_encode($data);

        $postString = "appid=$appid&project=$tempId&to=$to&signature=$signature&vars=$vars";
        $response = $this->sockPost($url, $postString);

        $data = json_decode($response, true);
        if ($data['status'] === 'success') {
            $this->result['success'] = true;
            $this->result['info'] = 'send_id:' . $data['send_id'] .
                                    ',sms_credits:' . $data['sms_credits'];
        } else {
            $this->result['info'] = $data['msg'];
            $this->result['code'] = $data['code'];
        }
    }

    public function voiceVerify($to, $code)
    {
        $this->result['success'] = false;
        $this->result['info'] = 'SubMail agent does not support voice verify';
        $this->result['code'] = '0';
    }
}
