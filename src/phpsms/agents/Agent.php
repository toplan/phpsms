<?php
namespace Toplan\PhpSms;

Abstract class Agent
{
    /**
     * sms whether to support multiple mobile number.
     * If support, it is the count of mobile numbers.
     * default support, and count is 100.
     * @var int
     */
    protected $smsMultiMobile = 100;

    /**
     * voice verify whether to support multiple mobile number.
     * If support, it is the count of mobile numbers.
     * default not support
     * @var int
     */
    protected $voiceMultiMobile = false;

    /**
     * voice verify play times.
     * default 3 times.
     * @var int
     */
    protected $voicePlayTimes = 3;

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
     * @param       $tempId
     * @param       $to
     * @param array $data
     * @param       $content
     * @return array
     */
    public function sms($tempId, $to, Array $data, $content)
    {
        $failedMobile = [];
        if (!$this->smsMultiMobile && count($mobileArray = explode(',', $to)) > 1) {
            $code = null;
            $failMsg = '';
            foreach ($mobileArray as $mobile) {
                $this->sendSms($tempId, $mobile, $data, $content);
                if (!$this->result['success']) {
                    array_push($failedMobile, $mobile);
                    $failMsg .= "$mobile:" . $this->result['info'] . ";";
                    $code = $this->result['code'];
                }
            }
            if ($failedCount = count($failedMobile)) {
                $this->result['success'] = false;
                $successCount = count($mobileArray) - $failedCount;
                $this->result['info'] = "($successCount success, $failedCount failed). $failMsg";
                $this->result['code'] = $code;
            }
        } else {
            $this->sendSms($tempId, $to, $data, $content);
        }
        return $failedMobile;
    }

    /**
     * sms send process
     * @param       $tempId
     * @param       $to
     * @param array $data
     * @param       $content
     */
    public abstract function sendSms($tempId, $to, Array $data, $content);

    /**
     * content sms send process
     * @param $to
     * @param $content
     */
    public abstract function sendContentSms($to, $content);

    /**
     * template sms send process
     * @param       $tempId
     * @param       $to
     * @param array $data
     */
    public abstract function sendTemplateSms($tempId, $to, Array $data);

    /**
     * voice verify
     * @param $to
     * @param $code
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
     * get result
     * @return array
     */
    public function getResult()
    {
        return $this->result;
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
