<?php

/**
 * Created by PhpStorm.
 * User: UCPAAS JackZhao
 * Date: 2014/10/22
 * Time: 12:04
 * Dec : ucpass php demo
 */
class Ucpaas
{
    /**
     *  云之讯REST API版本号。当前版本号为：2014-06-30
     */
    const SoftVersion = '2014-06-30';
    /**
     * API请求地址
     */
    const BaseUrl = 'https://api.ucpaas.com/';
    /**
     * @var string
     *             开发者账号ID。由32个英文字母和阿拉伯数字组成的开发者账号唯一标识符。
     */
    private $accountSid;
    /**
     * @var string
     *             开发者账号TOKEN
     */
    private $token;
    /**
     * @var string
     *             时间戳
     */
    private $timestamp;

    /**
     * @param mixed $options 数组参数必填
     *                       $options = array(
     *
     * )
     *
     * @throws Exception
     */
    public function __construct($options)
    {
        date_default_timezone_set('Asia/Shanghai');
        if (is_array($options) && !empty($options)) {
            $this->accountSid = isset($options['accountsid']) ? $options['accountsid'] : '';
            $this->token = isset($options['token']) ? $options['token'] : '';
            $this->timestamp = date('YmdHis');
        } else {
            throw new Exception('非法参数');
        }
    }

    /**
     * @return string
     *                包头验证信息,使用Base64编码（账户Id:时间戳）
     */
    private function getAuthorization()
    {
        $data = $this->accountSid . ':' . $this->timestamp;

        return trim(base64_encode($data));
    }

    /**
     * @return string
     *                验证参数,URL后必须带有sig参数，sig= MD5（账户Id + 账户授权令牌 + 时间戳，共32位）(注:转成大写)
     */
    private function getSigParameter()
    {
        $sig = $this->accountSid . $this->token . $this->timestamp;

        return strtoupper(md5($sig));
    }

    /**
     * @param string      $url
     * @param string|null $body
     * @param string      $type
     * @param string      $method
     *
     * @return mixed|string
     */
    private function getResult($url, $body, $type, $method)
    {
        $data = $this->connection($url, $body, $type, $method);
        if (isset($data) && !empty($data)) {
            $result = $data;
        } else {
            $result = '没有返回数据';
        }

        return $result;
    }

    /**
     * @param string      $url
     * @param string      $type
     * @param string|null $body
     * @param string      $method
     *
     * @return mixed|string
     */
    private function connection($url, $body, $type, $method)
    {
        if ($type === 'json') {
            $mine = 'application/json';
        } else {
            $mine = 'application/xml';
        }
        if (function_exists('curl_init')) {
            $header = array(
                'Accept:' . $mine,
                'Content-Type:' . $mine . ';charset=utf-8',
                'Authorization:' . $this->getAuthorization(),
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            if ($method === 'post') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $opts = array();
            $opts['http'] = array();
            $headers = array(
                'method' => strtoupper($method),
            );
            $headers[] = 'Accept:' . $mine;
            $headers['header'] = array();
            $headers['header'][] = 'Authorization: ' . $this->getAuthorization();
            $headers['header'][] = 'Content-Type:' . $mine . ';charset=utf-8';

            if (!empty($body)) {
                $headers['header'][] = 'Content-Length:' . strlen($body);
                $headers['content'] = $body;
            }

            $opts['http'] = $headers;
            $result = file_get_contents($url, false, stream_context_create($opts));
        }

        return $result;
    }

    /**
     * @param $appId
     * @param $fromClient
     * @param string $to
     * @param null   $fromSerNum
     * @param null   $toSerNum
     * @param string $type
     *
     * @throws Exception
     *
     * @return mixed|string
     * @links http://www.ucpaas.com/page/doc/doc_rest3-1.jsp
     */
    public function callBack($appId, $fromClient, $to, $fromSerNum = null, $toSerNum = null, $type = 'json')
    {
        $url = self::BaseUrl . self::SoftVersion . '/Accounts/' . $this->accountSid . '/Calls/callBack?sig=' . $this->getSigParameter();
        if ($type === 'json') {
            $body_json = array('callback' => array(
                'appId'      => $appId,
                'fromClient' => $fromClient,
                'fromSerNum' => $fromSerNum,
                'to'         => $to,
                'toSerNum'   => $toSerNum,
            ));
            $body = json_encode($body_json);
        } elseif ($type === 'xml') {
            $body_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <callback>
                            <fromClient>' . $fromClient . '</clientNumber>
                            <fromSerNum>' . $fromSerNum . '</chargeType>
                            <to>' . $to . '</charge>
                            <toSerNum>' . $toSerNum . '</toSerNum>
                            <appId>' . $appId . '</appId>
                        </callback>';
            $body = trim($body_xml);
        } else {
            throw new Exception('只能json或xml，默认为json');
        }
        $data = $this->getResult($url, $body, $type, 'post');

        return $data;
    }

    /**
     * @param $appId
     * @param $verifyCode
     * @param $to
     * @param string $type
     *
     * @throws Exception
     *
     * @return mixed|string
     * @links http://www.ucpaas.com/page/doc/doc_rest3-2.jsp
     */
    public function voiceCode($appId, $verifyCode, $to, $type = 'json')
    {
        $url = self::BaseUrl . self::SoftVersion . '/Accounts/' . $this->accountSid . '/Calls/voiceCode?sig=' . $this->getSigParameter();
        if ($type === 'json') {
            $body_json = array('voiceCode' => array(
                'appId'      => $appId,
                'verifyCode' => $verifyCode,
                'to'         => $to,
            ));
            $body = json_encode($body_json);
        } elseif ($type === 'xml') {
            $body_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <voiceCode>
                            <verifyCode>' . $verifyCode . '</clientNumber>
                            <to>' . $to . '</charge>
                            <appId>' . $appId . '</appId>
                        </voiceCode>';
            $body = trim($body_xml);
        } else {
            throw new Exception('只能json或xml，默认为json');
        }
        $data = $this->getResult($url, $body, $type, 'post');

        return $data;
    }

    /**
     * @param $appId
     * @param $to
     * @param $templateId
     * @param null   $param
     * @param string $type
     *
     * @throws Exception
     *
     * @return mixed|string
     * @links http://www.ucpaas.com/page/doc/doc_rest4-1.jsp
     */
    public function templateSMS($appId, $to, $templateId, $param = null, $type = 'json')
    {
        $url = self::BaseUrl . self::SoftVersion . '/Accounts/' . $this->accountSid . '/Messages/templateSMS?sig=' . $this->getSigParameter();
        if ($type === 'json') {
            $body_json = array('templateSMS' => array(
                'appId'      => $appId,
                'templateId' => $templateId,
                'to'         => $to,
                'param'      => $param,
            ));
            $body = json_encode($body_json);
        } elseif ($type === 'xml') {
            $body_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <templateSMS>
                            <templateId>' . $templateId . '</templateId>
                            <to>' . $to . '</to>
                            <param>' . $param . '</param>
                            <appId>' . $appId . '</appId>
                        </templateSMS>';
            $body = trim($body_xml);
        } else {
            throw new Exception('只能json或xml，默认为json');
        }
        $data = $this->getResult($url, $body, $type, 'post');

        return $data;
    }
}
