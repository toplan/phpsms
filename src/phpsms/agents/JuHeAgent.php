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
    public function sendSms($tempId, $to, array $data, $content)
    {
        $this->sendTemplateSms($tempId, $to, $data);
    }

    public function sendContentSms($to, $content)
    {
    }

    public function sendTemplateSms($tempId, $to, array $data)
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
        $this->genResult($result);
    }

    public function voiceVerify($to, $code)
    {
        $url = 'http://op.juhe.cn/yuntongxun/voice';
        $params = [
            'valicode'  => $code,
            'to'        => $to,
            'playtimes' => $this->times ?: 3,
            'key'       => $this->key,
        ];
        $result = $this->curl($url, $params);
        $this->genResult($result);
    }

    public function genResult($result)
    {
        if ($result['request']) {
            $result = json_decode($result['response'], true);
            if ($result['error_code'] === 0) {
                $this->result['success'] = true;
            } else {
                $this->result['info'] = $result['reason'] . '|result:' . json_encode($result['result'] ?: '');
                $this->result['code'] = $result['error_code'];
            }
        } else {
            $this->result['info'] = '请求失败';
        }
    }
}
