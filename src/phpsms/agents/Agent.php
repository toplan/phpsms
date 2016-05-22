<?php

namespace Toplan\PhpSms;

abstract class Agent
{
    const SUCCESS = 'success';
    const INFO = 'info';
    const CODE = 'code';

    /**
     * The configuration information of agent.
     *
     * @var array
     */
    protected $config = [];

    /**
     * The result data.
     *
     * @var array
     */
    protected $result = [
        self::SUCCESS => false,
        self::INFO    => null,
        self::CODE    => 0,
    ];

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
    }

    /**
     * Get or set the configuration information of agent.
     *
     * @param mixed $key
     * @param mixed $value
     * @param bool  $override
     *
     * @return mixed
     */
    public function config($key = null, $value = null, $override = false)
    {
        if (is_array($key) && is_bool($value)) {
            $override = $value;
        }

        return Util::operateArray($this->config, $key, $value, null, null, $override);
    }

    /**
     * SMS send process.
     *
     * @param       $to
     * @param       $content
     * @param       $tempId
     * @param array $tempData
     */
    abstract public function sendSms($to, $content, $tempId, array $tempData);

    /**
     * Content SMS send process.
     *
     * @param $to
     * @param $content
     */
    abstract public function sendContentSms($to, $content);

    /**
     * Template SMS send process.
     *
     * @param       $to
     * @param       $tempId
     * @param array $tempData
     */
    abstract public function sendTemplateSms($to, $tempId, array $tempData);

    /**
     * Voice verify send process.
     *
     * @param       $to
     * @param       $code
     * @param       $tempId
     * @param array $tempData
     */
    abstract public function voiceVerify($to, $code, $tempId, array $tempData);

    /**
     * Http post request.
     *
     * @codeCoverageIgnore
     *
     * @param       $url
     * @param array $query
     * @param       $port
     *
     * @return mixed
     */
    public static function sockPost($url, $query, $port = 80)
    {
        $data = '';
        $info = parse_url($url);
        $fp = fsockopen($info['host'], $port, $errno, $errstr, 30);
        if (!$fp) {
            return $data;
        }
        $head = 'POST ' . $info['path'] . " HTTP/1.0\r\n";
        $head .= 'Host: ' . $info['host'] . "\r\n";
        $head .= 'Referer: http://' . $info['host'] . $info['path'] . "\r\n";
        $head .= "Content-type: application/x-www-form-urlencoded\r\n";
        $head .= 'Content-Length: ' . strlen(trim($query)) . "\r\n";
        $head .= "\r\n";
        $head .= trim($query);
        $write = fwrite($fp, $head);
        $header = '';
        while ($str = trim(fgets($fp, 4096))) {
            $header .= $str;
        }
        while (!feof($fp)) {
            $data .= fgets($fp, 4096);
        }

        return $data;
    }

    /**
     * cURl
     *
     * @codeCoverageIgnore
     *
     * @param string   $url    [请求的URL地址]
     * @param array    $params [请求的参数]
     * @param int|bool $isPost [是否采用POST形式]
     *
     * @return array ['request', 'response']
     *               request:是否请求成功
     *               response:响应数据
     */
    public static function curl($url, array $params = [], $isPost = false)
    {
        $request = true;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            $params = http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $params ? "$url?$params" : $url);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            $request = false;
            $response = curl_getinfo($ch);
        }
        curl_close($ch);

        return compact('request', 'response');
    }

    /**
     * Set/get result data.
     *
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function result($name = null, $value = null)
    {
        if ($name === null) {
            return $this->result;
        }
        if (array_key_exists($name, $this->result)) {
            if ($value === null) {
                return $this->result["$name"];
            }
            $this->result["$name"] = $value;
        }
    }

    /**
     * Overload object properties.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->config($name);
    }

    /**
     * When using isset() or empty() on inaccessible object properties,
     * the __isset() overloading method will be called.
     *
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->config);
    }
}
