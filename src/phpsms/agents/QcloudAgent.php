<?php

namespace Toplan\PhpSms;
/**
 * Class SendCloudAgent
 *
 * @property string $smsUser
 * @property string $smsKey
 */
class QcloudAgent extends Agent implements TemplateSms
{
    protected $sendUrl = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';
    protected $random;
    protected $content;
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->content = $content;
        $this->sendTemplateSms($to, $tempId, $data);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $params = [
            'type'    => 0,//0:普通短信;1:营销短信
            'msg'    => $this->content,//$this->getTempDataString($data)
            'tel'   => ["nationcode"=> "86","mobile"=>$to],
            'time' => time(),
            'extend' => "",
            'ext' => "",
        ];
        $this->random = $this->getRandom();
        $sendUrl =  $this->sendUrl.'?'.'sdkappid='.$this->appId.'&random='.$this->random;
        $this->request($sendUrl, $params);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $params = [
            'phone' => $to,
            'code'  => $code,
        ];
        $this->request('https://yun.tim.qq.com/v5/tlssmssvr/sendVoice', $params);
    }

    protected function request($sendUrl, array $params)
    {
        $params = $this->createParams($params);
        $result = $this->curl($sendUrl, json_encode($params), true);
        $this->setResult($result);
    }

    protected function createParams(array $params)
    {
        $params['sig'] = $this->genSign($params);

        return $params;
    }

    protected function genSign($params)
    {
        $phone = $params['tel']["mobile"];
        $signature = "appkey=".$this->appKey."&random=".$this->random."&time=".$params['time']."&mobile=".$phone;
        return hash("sha256",$signature, FALSE);
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            if (isset($result['result'])) {
                $this->result(Agent::SUCCESS, (bool) ($result['result'] == 0));
                $this->result(Agent::CODE, $result['result']);
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

    protected function getRandom() {
        return rand(100000, 999999);
    }
}
