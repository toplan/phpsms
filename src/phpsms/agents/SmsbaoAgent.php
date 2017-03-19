<?php

namespace Toplan\PhpSms;

/**
 * Class SmsBaoAgent
 *
 * @property string $smsUser
 * @property string $smsPassword
 */
class SmsBaoAgent extends Agent
{
    protected $resultArr = [
        '0'  => '发送成功',
        '-1' => '参数不全',
        '-2' => '服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！',
        '30' => '密码错误',
        '40' => '账号不存在',
        '41' => '余额不足',
        '42' => '帐户已过期',
        '43' => 'IP地址限制',
        '50' => '内容含有敏感词',
        '51' => '手机号码不正确',
    ];

    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendContentSms($to, $content);
    }

    /**
     * Content SMS send process.
     *
     * @param $to
     * @param $content
     */
    public function sendContentSms($to, $content)
    {
        $url = 'http://api.smsbao.com/sms';
        $username = $this->smsUser;
        $password = md5($this->smsPassword);
        $content = urlencode($content);
        $postString = "u=$username&p=$password&m=$to&c=$content";
        $response = $this->sockPost($url, $postString);
        $this->setResult($response);
    }

    /**
     * Template SMS send process.
     *
     * @param       $to
     * @param       $tempId
     * @param array $tempData
     */
    public function sendTemplateSms($to, $tempId, array $tempData)
    {
    }

    /**
     * Voice verify send process.
     *
     * @param       $to
     * @param       $code
     * @param       $tempId
     * @param array $tempData
     */
    public function voiceVerify($to, $code, $tempId, array $tempData)
    {
        $url = 'http://api.smsbao.com/voice';
        $username = $this->smsUser;
        $password = md5($this->smsPassword);
        $postString = "u=$username&p=$password&m=$to&c=$code";
        $response = $this->sockPost($url, $postString);
        $this->setResult($response);
    }

    protected function setResult($result)
    {
        $msg = array_key_exists($result, $this->resultArr) ? $this->resultArr[$result] : '未知错误';
        $this->result(Agent::INFO, json_encode(['code' => $result, 'msg' => $msg]));
        $this->result(Agent::SUCCESS, $result === '0');
        $this->result(Agent::CODE, $result);
    }
}
