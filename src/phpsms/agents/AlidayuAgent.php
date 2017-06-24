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
class AlidayuAgent extends Agent implements TemplateSms, VoiceCode, TemplateVoice
{
    /**
     * Template SMS send process.
     *
     * @param string|array $to
     * @param int|string   $tempId
     * @param array        $tempData
     */
    public function sendTemplateSms($to, $tempId, array $tempData)
    {
        $params = [
            'method'             => 'alibaba.aliqin.fc.sms.num.send',
            'sms_type'           => 'normal',
            'sms_free_sign_name' => $this->smsFreeSignName,
            'sms_param'          => $this->getTempDataString($tempData),
            'rec_num'            => $to,
            'sms_template_code'  => $tempId,
        ];
        $this->request($params);
    }

    /**
     * Template voice send process.
     *
     * @param string|array $to
     * @param int|string   $tempId
     * @param array        $tempData
     */
    public function sendTemplateVoice($to, $tempId, array $tempData)
    {
        $params = [
            'called_num'        => $to,
            'called_show_num'   => $this->calledShowNum,
            'method'            => 'alibaba.aliqin.fc.tts.num.singlecall',
            'tts_code'          => $tempId,
            'tts_param'         => $this->getTempDataString($tempData),
        ];
        $this->request($params);
    }

    /**
     * Voice code send process.
     *
     * @param string|array $to
     * @param int|string   $code
     */
    public function sendVoiceCode($to, $code)
    {
        $params = [
            'called_num'        => $to,
            'called_show_num'   => $this->calledShowNum,
            'method'            => 'alibaba.aliqin.fc.voice.num.singlecall',
            'voice_code'        => $code,
        ];
        $this->request($params);
    }

    protected function request(array $params)
    {
        $params = $this->createParams($params);
        $result = $this->curlPost($this->sendUrl, [], [
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);
        $this->setResult($result, $this->genResponseName($params['method']));
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

        return $this->params($params);
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

    protected function setResult($result, $callbackName)
    {
        if ($result['request']) {
            $result = json_decode($result['response'], true);
            if (isset($result[$callbackName]['result'])) {
                $result = $result[$callbackName]['result'];
                $this->result(Agent::SUCCESS, (bool) $result['success']);
                $this->result(Agent::INFO, json_encode($result));
                $this->result(Agent::CODE, $result['err_code']);
            } elseif (isset($result['error_response'])) {
                $error = $result['error_response'];
                $this->result(Agent::INFO, json_encode($error));
                $this->result(Agent::CODE, $error['code']);
            }
        } else {
            $this->result(Agent::INFO, 'request failed');
        }
    }

    protected function genResponseName($method)
    {
        return str_replace('.', '_', $method) . '_response';
    }

    protected function getTempDataString(array $data)
    {
        $data = array_map(function ($value) {
            return (string) $value;
        }, $data);

        return json_encode($data);
    }
}
