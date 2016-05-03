<?php

namespace Toplan\PhpSms;

/**
 * Class JuHeAgent
 *
 * @property string $key
 * @property string $times
 */
class JuHeAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendTemplateSms($to, $tempId, $data);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $sendUrl = 'http://v.juhe.cn/sms/send';
        $tplValue = '';
        foreach ($data as $key => $value) {
            if (preg_match('/[#&=]/', $value)) {
                $value = urlencode($value);
            }
            $split = !$tplValue ? '' : '&';
            $tplValue .= "$split#$key#=$value";
        }
        $tplValue = !$tplValue ?: urlencode($tplValue);
        $smsConf = [
            'key'       => $this->key,
            'mobile'    => $to,
            'tpl_id'    => $tempId,
            'tpl_value' => $tplValue,
        ];
        $result = $this->curl($sendUrl, $smsConf, true);
        $this->setResult($result);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $url = 'http://op.juhe.cn/yuntongxun/voice';
        $params = [
            'valicode'  => $code,
            'to'        => $to,
            'playtimes' => $this->times ?: 3,
            'key'       => $this->key,
        ];
        $result = $this->curl($url, $params);
        $this->setResult($result);
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $result = json_decode($result['response'], true);
            if ($result['error_code'] === 0) {
                $this->result(Agent::SUCCESS, true);
            }
            $this->result(Agent::INFO, json_encode($result));
            $this->result(Agent::CODE, $result['error_code']);
        } else {
            $this->result(Agent::INFO, '请求失败');
        }
    }

    public function sendContentSms($to, $content)
    {
    }
}
