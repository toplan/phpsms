<?php

namespace Toplan\PhpSms;

/**
 * Class AlidayuAgent
 *
 * @property string $sendUrl
 * @property string $appKey
 * @property string $secretKey
 * @property string $smsFreeSignName
 * @property string $calledShowNum
 */
class AlidayuAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendTemplateSms($to, $tempId, $data);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        $params = [
            'method'             => 'alibaba.aliqin.fc.sms.num.send',
            'sms_type'           => 'normal',
            'sms_free_sign_name' => $this->smsFreeSignName,
            'sms_param'          => json_encode($data),
            'rec_num'            => $to,
            'sms_template_code'  => $tempId,
        ];
        $this->request($params);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $params = [
            'called_num'      => $to,
            'called_show_num' => $this->calledShowNum,
        ];
        if ($tempId) {
            //文本转语音通知
            $params['method'] = 'alibaba.aliqin.fc.tts.num.singlecall';
            $params['tts_code'] = $tempId;
            $params['tts_param'] = json_encode($data);
        } elseif ($code) {
            //语音通知
            $params['method'] = 'alibaba.aliqin.fc.voice.num.singlecall';
            $params['voice_code'] = $code;
        }
        $this->request($params);
    }

    protected function request(array $params)
    {
        $sendUrl = $this->sendUrl ?: 'https://eco.taobao.com/router/rest';
        $params = $this->createParams($params);
        $result = $this->curl($sendUrl, $params, true);
        $this->genResult($result, $this->genResponseName($params['method']));
    }

    protected function createParams(array $params)
    {
        $params = array_merge([
            'app_key'            => $this->appKey,
            'v'                  => '2.0',
            'format'             => 'json',
            'sign_method'        => 'md5',
            'timestamp'          => date('Y-m-d H:i:s'),
        ], $params);
        $params['sign'] = $this->genSign($params);

        return $params;
    }

    protected function genSign($params)
    {
        ksort($params);
        $stringToBeSigned = $this->secretKey;
        foreach ($params as $k => $v) {
            if (is_string($v) && '@' !== substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->secretKey;

        return strtoupper(md5($stringToBeSigned));
    }

    public function genResult($result, $callbackName)
    {
        if ($result['request']) {
            $result = json_decode($result['response'], true);
            if (isset($result[$callbackName]['result'])) {
                $result = $result[$callbackName]['result'];
                $this->result(Agent::SUCCESS, (bool) $result['success']);
                $this->result(Agent::INFO, $result['msg']);
                $this->result(Agent::CODE, $result['err_code']);
            } elseif (isset($result['error_response'])) {
                $error = $result['error_response'];
                $this->result(Agent::INFO, json_encode($error));
                $this->result(Agent::CODE, 'code:' . $error['code'] . '|sub_code:' . $error['sub_code']);
            }
        } else {
            $this->result['info'] = '请求失败';
        }
    }

    protected function genResponseName($method)
    {
        return str_replace('.', '_', $method) . '_response';
    }

    public function sendContentSms($to, $content)
    {
    }
}
