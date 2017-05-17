<?php

namespace Toplan\PhpSms;

/**
 * Class JuHeAgent
 *
 * @property string $key
 * @property string $times
 */
class JuHeAgent extends Agent implements TemplateSms, VoiceCode
{
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
        $params = [
            'key'       => $this->key,
            'mobile'    => $to,
            'tpl_id'    => $tempId,
            'tpl_value' => urlencode($tplValue),
            'dtype'     => 'json',
        ];
        $result = $this->curlGet($sendUrl, $params);
        $this->setResult($result);
    }

    public function sendVoiceCode($to, $code)
    {
        $url = 'http://op.juhe.cn/yuntongxun/voice';
        $params = [
            'valicode'  => $code,
            'to'        => $to,
            'playtimes' => $this->times ?: 3,
            'key'       => $this->key,
            'dtype'     => 'json',
        ];
        $result = $this->curlGet($url, $params);
        $this->setResult($result);
    }

    protected function setResult($result)
    {
        if ($result['request']) {
            $this->result(Agent::INFO, $result['response']);
            $result = json_decode($result['response'], true);
            $this->result(Agent::SUCCESS, $result['error_code'] === 0);
            $this->result(Agent::CODE, $result['error_code']);
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }
}
