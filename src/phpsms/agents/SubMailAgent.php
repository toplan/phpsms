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
            $this->result(Agent::SUCCESS, true);
            $this->result(Agent::INFO, json_encode($data));
        } else {
            $this->result(Agent::INFO, $data['msg']);
            $this->result(Agent::CODE, $data['code']);
        }
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $this->result(Agent::SUCCESS, false);
        $this->result(Agent::INFO, 'SubMail agent does not support voice verify');
    }

    public function sendContentSms($to, $content)
    {
    }
}
