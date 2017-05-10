<?php

namespace Toplan\PhpSms;

/**
 * Class SendCloudAgent
 *
 * @property string $appId
 * @property string $appKey
 */
class QcloudAgent extends Agent implements TemplateSms, ContentSms
{
    protected $sendSms = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';
    protected $sendVoiceCode = 'https://yun.tim.qq.com/v5/tlssmssvr/sendVoice';
    protected $sendVoicePrompt = 'https://yun.tim.qq.com/v5/tlsvoicesvr/sendvoiceprompt';
    protected $random;

    public function sendSms($to, $content, $tempId, array $data)
    {
        if ($content) {
            $this->sendContentSms($to, $content);
            $content = null;
        } else if ($tempId) {
            $this->sendTemplateSms($to, $tempId, $data);
            $tempId = null;
        }
        if (!$this->result(Agent::SUCCESS) && ($content || $tempId)) {
            $this->sendSms($to, $content, $tempId, $data);
        }
    }

    public function sendContentSms($to, $content)
    {
        $params = [
            'type'   => 0, // 0:普通短信 1:营销短信
            'msg'    => $content,
            'tel'    => ['nationcode' => '86', 'mobile' => $to],
            'time'   => time(),
            'extend' => '',
            'ext'    => '',
        ];
        $this->random = $this->getRandom();
        $sendUrl = "{$this->sendSms}?sdkappid={$this->appId}&random={$this->random}";
        $this->request($sendUrl, $params);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $params = [
            'tel'    => ['nationcode' => '86', 'mobile' => $to],
            'tpl_id' => $tempId,
            'params' => array_values($data),
            'time'   => time(),
            'extend' => '',
            'ext'    => '',
        ];
        $this->random = $this->getRandom();
        $sendUrl = "{$this->sendSms}?sdkappid={$this->appId}&random={$this->random}";
        $this->request($sendUrl, $params);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $this->request($this->sendVoiceUrl, [
            'phone' => $to,
            'code'  => $code,
        ]);
    }

    protected function request($sendUrl, array $params)
    {
        $params['sig'] = $this->genSign($params);
        $result = $this->curl($sendUrl, json_encode($params), true);
        $this->setResult($result);
    }

    protected function genSign($params)
    {
        $phone = $params['tel']['mobile'];
        $signature = "appkey={$this->appKey}&random={$this->random}&time={$params['time']}&mobile={$phone}";

        return hash('sha256', $signature, false);
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            if (isset($result['result'])) {
                $this->result(Agent::SUCCESS, $result['result'] === 0);
                $this->result(Agent::CODE, $result['result']);
            }
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }

    protected function getRandom()
    {
        return rand(100000, 999999);
    }
}
