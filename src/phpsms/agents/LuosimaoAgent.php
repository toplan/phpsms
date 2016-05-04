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
    public function sendSms($to, $content, $tempId, array $data)
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
        $optData = [
            'mobile'  => $to,
            'message' => $content,
        ];
        $data = $this->LuosimaoCurl($url, $optData, $this->apikey);
        $this->setResult($data);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $url = 'https://voice-api.luosimao.com/v1/verify.json';
        $optData = [
            'mobile' => $to,
            'code'   => $code,
        ];
        $data = $this->LuosimaoCurl($url, $optData, $this->voiceApikey);
        $this->setResult($data);
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

        return $res;
    }

    protected function setResult($result)
    {
        $this->result(Agent::INFO, $result);
        $result = json_decode($result, true);
        $this->result(Agent::SUCCESS, $result['error'] === 0);
        $this->result(Agent::CODE, $result['error']);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
    }
}
