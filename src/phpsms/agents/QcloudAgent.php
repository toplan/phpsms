<?php

namespace Toplan\PhpSms;

/**
 * Class SendCloudAgent
 *
 * @property string $appId
 * @property string $appKey
 */
class QcloudAgent extends Agent implements TemplateSms, ContentSms, VoiceCode, ContentVoice
{
    protected $sendSms = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';
    protected $sendVoiceCode = 'https://yun.tim.qq.com/v5/tlsvoicesvr/sendvoice';
    protected $sendVoicePrompt = 'https://yun.tim.qq.com/v5/tlsvoicesvr/sendvoiceprompt';
    protected $random;

    public function formatMobile(array $list)
    {
        $list = array_map(function ($value) {
            return [
                'nationcode'    => $value['nation'],
                'mobile'        => $value['number'],
            ];
        }, array_filter($list, function ($value) {
            return is_array($value);
        }));

        return count($list) === 1 ? array_pop($list) : array_values($list);
    }

    public function sendContentSms($to, $content)
    {
        $params = [
            'type'   => 0, // 0:普通短信 1:营销短信
            'msg'    => $content,
            'tel'    => $to,
            'time'   => time(),
        ];
        $this->random = $this->getRandom();
        $sendUrl = "{$this->sendSms}?sdkappid={$this->appId}&random={$this->random}";
        $this->request($sendUrl, $params);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $params = [
            'tel'    => $to,
            'tpl_id' => $tempId,
            'params' => array_values($data),
            'time'   => time(),
        ];
        $this->random = $this->getRandom();
        $sendUrl = "{$this->sendSms}?sdkappid={$this->appId}&random={$this->random}";
        $this->request($sendUrl, $params);
    }

    public function sendVoiceCode($to, $code)
    {
        $params = [
            'tel'    => $to,
            'msg'    => $code,
        ];
        $sendUrl = "{$this->sendVoiceCode}?sdkappid={$this->appId}&random={$this->random}";
        $this->request($sendUrl, $params);
    }

    public function sendContentVoice($to, $content)
    {
        $params = [
            'tel'        => $to,
            'prompttype' => 2,
            'promptfile' => $content,
        ];
        $sendUrl = "{$this->sendVoicePrompt}?sdkappid={$this->appId}&random={$this->random}";
        $this->request($sendUrl, $params);
    }

    protected function request($sendUrl, array $params)
    {
        $params['sig'] = $this->genSign($params);
        $params = $this->params($params);
        $result = $this->curlPost($sendUrl, [], [
            CURLOPT_POSTFIELDS => json_encode($params),
        ]);
        $this->setResult($result);
    }

    protected function genSign($params)
    {
        $mobileStr = null;
        if (array_key_exists('mobile', $params['tel'])) {
            $mobileStr = $params['tel']['mobile'];
        } else {
            $mobileStr = implode(',', array_map(function ($value) {
                return $value['mobile'];
            }, $params['tel']));
        }
        $signature = "appkey={$this->appKey}&random={$this->random}&time={$params['time']}&mobile={$mobileStr}";

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
            } elseif (isset($result['ErrorCode'])) {
                $this->result(Agent::CODE, $result['ErrorCode']);
                $this->result(Agent::INFO, $result['ErrorInfo']);
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
