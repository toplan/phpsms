<?php

namespace Toplan\PhpSms;

/**
 * Class SubMailAgent
 *
 * @property string $appid
 * @property string $signature
 */
class SubMailAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendTemplateSms($to, $tempId, $data);
    }

    public function sendContentSms($to, $content)
    {
    }

    public function sendTemplateSms($to, $tempId, array $data)
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

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $this->result['success'] = false;
        $this->result['info'] = 'SubMail agent does not support voice verify';
        $this->result['code'] = '0';
    }
}
