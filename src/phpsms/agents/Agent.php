<?php
namespace Toplan\PhpSms;

Abstract class Agent
{
    /**
     * agent config
     * @var array
     */
    protected $config;

    /**
     * sent result info
     * @var array
     */
    protected $result = [
        'success' => false,
        'info'  => '',
        'code'  => 0
    ];

    /**
     * construct for create a instance
     * @param array $config
     */
    public function __construct(Array $config = [])
    {
        $this->config = $config;
    }

    /**
     * sms send entry
     * @param       $template
     * @param       $to
     * @param array $data
     * @param       $content
     *
     * @return array|null
     */
    public function sms($template, $to, Array $data, $content)
    {
        $this->sendSms($template, $to, $data, $content);
        return $this->result;
    }

    /**
     * sms send process entry
     * @param       $tempId
     * @param       $to
     * @param array $data
     * @param       $content
     *
     * @return mixed
     */
    public abstract function sendSms($tempId, $to, Array $data, $content);

    /**
     * content sms send process
     * @param $to
     * @param $content
     *
     * @return mixed
     */
    public abstract function sendContentSms($to, $content);

    /**
     * template sms send process
     * @param       $tempId
     * @param       $to
     * @param array $data
     *
     * @return mixed
     */
    public abstract function sendTemplateSms($tempId, $to, Array $data);

    /**
     * voice verify
     * @param $to
     * @param $code
     *
     * @return mixed
     */
    public abstract function voiceVerify($to, $code);

    /**
     * http post request
     * @param       $url
     * @param array $query
     * @param       $port
     *
     * @return mixed
     */
    function sockPost($url, $query, $port = 80){
        $data = "";
        $info = parse_url($url);
        $fp   = fsockopen($info["host"], $port, $errno, $errstr, 30);
        if ( ! $fp) {
            return $data;
        }
        $head  = "POST ".$info['path']." HTTP/1.0\r\n";
        $head .= "Host: ".$info['host']."\r\n";
        $head .= "Referer: http://".$info['host'].$info['path']."\r\n";
        $head .= "Content-type: application/x-www-form-urlencoded\r\n";
        $head .= "Content-Length: ".strlen(trim($query))."\r\n";
        $head .= "\r\n";
        $head .= trim($query);
        $write = fputs($fp,$head);
        $header = "";
        while ($str = trim(fgets($fp, 4096))) {
            $header .= $str;
        }
        while ( ! feof($fp)) {
            $data .= fgets($fp, 4096);
        }
        return $data;
    }

    /**
     * overload object attribute
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->config)) {
            return $this->config["$name"];
        }
        return null;
    }
}
