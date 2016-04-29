<?php

namespace Toplan\PhpSms;

/**
 * Class AlidayuAgent
 *
 * @property string $appKey
 * @property string $secretKey
 * @property string $smsFreeSignName
 */
class AlidayuAgent extends Agent
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
        $sendUrl = 'https://eco.taobao.com/router/rest';
        $params = [
            'app_key'            => $this->appKey,
            'v'                  => '2.0',
            'format'             => 'json',
            'sign_method'        => 'md5',
            'method'             => 'alibaba.aliqin.fc.sms.num.send',
            'timestamp'          => date('Y-m-d H:i:s'),
            'sms_type'           => 'normal',
            'sms_free_sign_name' => $this->smsFreeSignName,
            'sms_param'          => json_encode($data),
            'rec_num'            => $to,
            'sms_template_code'  => $tempId,
        ];
        $params['sign'] = $this->generateSign($params);
        $result = $this->curl($sendUrl, $params, true);
        $this->genResult($result, 'alibaba_aliqin_fc_sms_num_send_response');
    }

    public function voiceVerify($to, $code)
    {
        // remain discussion
    }

    public function genResult($result, $callbackName)
    {
        if ($result['response']) {
            $result = json_decode($result['response'], true);
            if (isset($result[$callbackName]['result']) && $result[$callbackName]['result']['err_code'] == '0') {
                $this->result['success'] = true;
                return;
            } elseif (isset($result['error_response'])) {
                $this->result['info'] = $result['error_response']['msg'] . '|sub_msg:' . $result['error_response']['sub_msg'] . '|result:' . json_encode($result ?: '');
                $this->result['code'] = $result['error_response']['code'] . '_' . $result['error_response']['sub_code'];
                return;
            }
        }
        $this->result['info'] = '请求失败';
    }

    protected function generateSign($params)
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
}
