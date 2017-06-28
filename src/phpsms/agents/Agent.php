<?php

namespace Toplan\PhpSms;

abstract class Agent
{
    const SUCCESS = 'success';
    const INFO = 'info';
    const CODE = 'code';

    /**
     * The configuration information.
     *
     * @var array
     */
    protected $config = [];

    /**
     * The custom params of request.
     *
     * @var array
     */
    protected $params = [];

    /**
     * The result data.
     *
     * @var array
     */
    protected $result = [];

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->reset();
        $this->config($config);
    }

    /**
     * Reset states.
     */
    public function reset()
    {
        $this->result = [
            self::SUCCESS => false,
            self::INFO    => null,
            self::CODE    => 0,
        ];
    }

    /**
     * Get or set the configuration information.
     *
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $override
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
     * Get or set the custom params.
     *
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $override
     *
     * @return mixed
     */
    public function params($key = null, $value = null, $override = false)
    {
        if (is_array($key) && is_bool($value)) {
            $override = $value;
        }

        return Util::operateArray($this->params, $key, $value, null, null, $override);
    }

    /**
     * SMS send process.
     *
     * @param       $to
     * @param       $content
     * @param       $tempId
     * @param array $data
     * @param array $params
     */
    public function sendSms($to, $content = null, $tempId = null, array $data = [], array $params = [])
    {
        $this->reset();
        $this->params($params, true);
        $to = $this->formatMobile(Util::formatMobiles($to));

        if ($tempId && $this instanceof TemplateSms) {
            $this->sendTemplateSms($to, $tempId, $data);
        } elseif ($content && $this instanceof ContentSms) {
            $this->sendContentSms($to, $content);
        }
    }

    /**
     * Voice send process.
     *
     * @param       $to
     * @param       $content
     * @param       $tempId
     * @param array $data
     * @param       $code
     * @param       $fileId
     * @param array $params
     */
    public function sendVoice($to, $content = null, $tempId = null, array $data = [], $code = null, $fileId = null, array $params = [])
    {
        $this->reset();
        $this->params($params, true);
        $to = $this->formatMobile(Util::formatMobiles($to));

        if ($tempId && $this instanceof TemplateVoice) {
            $this->sendTemplateVoice($to, $tempId, $data);
        } elseif ($fileId && $this instanceof FileVoice) {
            $this->sendFileVoice($to, $fileId);
        } elseif ($code && $this instanceof VoiceCode) {
            $this->sendVoiceCode($to, $code);
        } elseif ($content && $this instanceof ContentVoice) {
            $this->sendContentVoice($to, $content);
        }
    }

    /**
     * Formatting a mobile number from the list of mobile numbers.
     *
     * @param array $list
     *
     * @return string
     */
    public function formatMobile(array $list)
    {
        return implode(',', array_unique(array_map(function ($value) {
            return is_array($value) ? "{$value['number']}" : $value;
        }, $list)));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param       $url
     * @param array $params
     * @param array $opts
     *
     * @return array
     */
    public function curlPost($url, array $params = [], array $opts = [])
    {
        $options = [
            CURLOPT_POST    => true,
            CURLOPT_URL     => $url,
        ];
        foreach ($opts as $key => $value) {
            if ($key !== CURLOPT_POST && $key !== CURLOPT_URL) {
                $options[$key] = $value;
            }
        }
        if (!array_key_exists(CURLOPT_POSTFIELDS, $options)) {
            $options[CURLOPT_POSTFIELDS] = $this->params($params);
        }

        return self::curl($options);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param       $url
     * @param array $params
     * @param array $opts
     *
     * @return array
     */
    public function curlGet($url, array $params = [], array $opts = [])
    {
        $params = $this->params($params);
        $queryStr = http_build_query($params);
        $opts[CURLOPT_POST] = false;
        $opts[CURLOPT_URL] = $queryStr ? "$url?$queryStr" : $url;

        return self::curl($opts);
    }

    /**
     * cURl
     *
     * @codeCoverageIgnore
     *
     * @param array $opts curl options
     *
     * @return array ['request', 'response']
     *               request: Whether request success.
     *               response: Response data.
     */
    public static function curl(array $opts = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        foreach ($opts as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        $response = curl_exec($ch);
        $request = $response !== false;
        if (!$request) {
            $response = curl_getinfo($ch);
        }
        curl_close($ch);

        return compact('request', 'response');
    }

    /**
     * Get or set the result data.
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
                return $this->result[$name];
            }
            $this->result[$name] = $value;
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
        return isset($this->config[$name]);
    }
}
