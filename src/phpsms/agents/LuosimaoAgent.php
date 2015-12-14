<?php

namespace Toplan\PhpSms;

/**
 * Class LuosimaoAgent
 *
 * @property string $apikey
 * @property string $voiceApikey
 */
class LuosimaoAgent extends Agent
{
    public function sendSms($tempId, $to, array $data, $content)
    {
        // check content signature,
        // Luosimao signature must be in the content finally
        if ($content && !preg_match('/】$/', $content)) {
            preg_match('/【([0-9a-zA-Z\W]+)】/', $content, $matches);
            if (isset($matches[0])) {
                $content = str_replace($matches[0], '', $content) . $matches[0];
            }
        }
        $this->sendContentSms($to, $content);
    }

    public function sendContentSms($to, $content)
    {
        $url = 'https://sms-api.luosimao.com/v1/send.json';
        $apikey = $this->apikey;
        $optData = [
            'mobile'  => $to,
            'message' => $content,
        ];
        $data = $this->LuosimaoCurl($url, $optData, $apikey);
        if ($data['error'] === 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $data['msg'];
        $this->result['code'] = $data['error'];
    }

    public function sendTemplateSms($tempId, $to, array $data)
    {
    }

    public function voiceVerify($to, $code)
    {
        $url = 'https://voice-api.luosimao.com/v1/verify.json';
        $apikey = $this->voiceApikey;
        $optData = [
            'mobile' => $to,
            'code'   => $code,
        ];
        $data = $this->LuosimaoCurl($url, $optData, $apikey);
        if ($data['error'] === 0) {
            $this->result['success'] = true;
        }
        $this->result['info'] = $data['msg'];
        $this->result['code'] = $data['error'];
    }

    protected function LuosimaoCurl($url, $optData, $apikey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url");

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:key-' . $apikey);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $optData);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }
}
