
<?php
/*
 *  Copyright (c) 2014 The CCP project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a Beijing Speedtong Information Technology Co.,Ltd license
 *  that can be found in the LICENSE file in the root of the web site.
 *
 *   http://www.yuntongxun.com
 *
 *  An additional intellectual property rights grant can be found
 *  in the file PATENTS.  All contributing project authors may
 *  be found in the AUTHORS file in the root of the source tree.
 */

class REST
{
    private $AccountSid;
    private $AccountToken;
    private $AppId;
    private $ServerIP;
    private $ServerPort;
    private $SoftVersion;
    private $Batch;  //时间戳
    private $BodyType = 'xml'; //包体格式，可填值：json 、xml

    public function __construct($ServerIP, $ServerPort, $SoftVersion, $BodyType = 'xml')
    {
        $this->Batch = date('YmdHis');
        $this->ServerIP = $ServerIP;
        $this->ServerPort = $ServerPort;
        $this->SoftVersion = $SoftVersion;
        if (in_array($BodyType, ['xml', 'json'])) {
            $this->BodyType = $BodyType;
        }
    }

    /**
     * 设置主帐号
     *
     * @param string $AccountSid   主帐号
     * @param string $AccountToken 主帐号Token
     */
    public function setAccount($AccountSid, $AccountToken)
    {
        $this->AccountSid = $AccountSid;
        $this->AccountToken = $AccountToken;
    }

    /**
     * 设置应用ID
     *
     * @param string $AppId 应用ID
     */
    public function setAppId($AppId)
    {
        $this->AppId = $AppId;
    }

     /**
      * 发起HTTPS请求
      *
      * @param string $url
      * @param mixed $data
      * @param mixed $header
      * @param mixed $post
      *
      * @return mixed
      */
     public function curl_post($url, $data, $header, $post = 1)
     {
         //初始化curl
         $ch = curl_init();
         //参数设置
         $res = curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_HEADER, 0);
         curl_setopt($ch, CURLOPT_POST, $post);
         if ($post) {
             curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
         }
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
         $result = curl_exec($ch);
         //连接失败
         if ($result === false) {
             if ($this->BodyType === 'json') {
                 $result = '{"statusCode":"172001","statusMsg":"网络错误"}';
             } else {
                 $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Response><statusCode>172001</statusCode><statusMsg>网络错误</statusMsg></Response>';
             }
         }
         curl_close($ch);

         return $result;
     }

    /**
     * 发送模板短信
     *
     * @param string $to
     *                       短信接收彿手机号码集合,用英文逗号分开
     * @param array  $datas
     *                       内容数据
     * @param mixed  $tempId
     *                       模板Id
     *
     * @return mixed
     */
    public function sendTemplateSMS($to, $datas, $tempId)
    {
        //主帐号鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth !== true) {
            return $auth;
        }
        // 拼接请求包体
        if ($this->BodyType === 'json') {
            $data = '';
            for ($i = 0; $i < count($datas); $i++) {
                $data = $data . "'" . $datas[$i] . "',";
            }
            $body = "{'to':'$to','templateId':'$tempId','appId':'$this->AppId','datas':[" . $data . ']}';
        } else {
            $data = '';
            for ($i = 0; $i < count($datas); $i++) {
                $data = $data . '<data>' . $datas[$i] . '</data>';
            }
            $body = "<TemplateSMS>
                    <to>$to</to> 
                    <appId>$this->AppId</appId>
                    <templateId>$tempId</templateId>
                    <datas>" . $data . '</datas>
                  </TemplateSMS>';
        }
        // 大写的sig参数
        $sig = strtoupper(md5($this->AccountSid . $this->AccountToken . $this->Batch));
        // 生成请求URL
        $url = "https://$this->ServerIP:$this->ServerPort/$this->SoftVersion/Accounts/$this->AccountSid/SMS/TemplateSMS?sig=$sig";
        // 生成授权：主帐户Id + 英文冒号 + 时间戳。
        $authen = base64_encode($this->AccountSid . ':' . $this->Batch);
        // 生成包头
        $header = array("Accept:application/$this->BodyType", "Content-Type:application/$this->BodyType;charset=utf-8", "Authorization:$authen");
        // 发送请求
        $result = $this->curl_post($url, $body, $header);
        if ($this->BodyType === 'json') {//JSON格式
           $datas = json_decode($result);
        } else { //xml格式
           $datas = simplexml_load_string(trim($result, " \t\n\r"));
        }
        // 重新装填数据
        if (isset($datas->templateSMS)) {
            $datas->TemplateSMS = $datas->templateSMS;
        }

        return $datas;
    }

    /**
     * 语音验证码
     *
     * @param mixed $verifyCode     验证码内容，为数字和英文字母，不区分大小写，长度4-8位
     * @param mixed $playTimes      播放次数，1－3次
     * @param mixed $to             接收号码
     * @param mixed $displayNum     显示的主叫号码
     * @param mixed $respUrl        语音验证码状态通知回调地址，云通讯平台将向该Url地址发送呼叫结果通知
     * @param mixed $lang           语言类型
     * @param mixed $userData       第三方私有数据
     * @param mixed $welcomePrompt  欢迎提示音，在播放验证码语音前播放此内容（语音文件格式为wav）
     * @param mixed $playVerifyCode 语音验证码的内容全部播放此节点下的全部语音文件
     *
     * @return mixed
     */
    public function voiceVerify($verifyCode, $playTimes, $to, $displayNum = null, $respUrl = null, $lang = 'zh', $userData = null, $welcomePrompt = null, $playVerifyCode = null)
    {
        //主帐号鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth !== true) {
            return $auth;
        }
        // 拼接请求包体
        if ($this->BodyType === 'json') {
            $body = "{'appId':'$this->AppId','verifyCode':'$verifyCode','playTimes':'$playTimes','to':'$to','respUrl':'$respUrl','displayNum':'$displayNum',
           'lang':'$lang','userData':'$userData','welcomePrompt':'$welcomePrompt','playVerifyCode':'$playVerifyCode'}";
        } else {
            $body = "<VoiceVerify>
                    <appId>$this->AppId</appId>
                    <verifyCode>$verifyCode</verifyCode>
                    <playTimes>$playTimes</playTimes>
                    <to>$to</to>
                    <respUrl>$respUrl</respUrl>
                    <displayNum>$displayNum</displayNum>
                    <lang>$lang</lang>
                    <userData>$userData</userData>
					<welcomePrompt>$welcomePrompt</welcomePrompt>
					<playVerifyCode>$playVerifyCode</playVerifyCode>
                  </VoiceVerify>";
        }
        // 大写的sig参数
        $sig = strtoupper(md5($this->AccountSid . $this->AccountToken . $this->Batch));
        // 生成请求URL
        $url = "https://$this->ServerIP:$this->ServerPort/$this->SoftVersion/Accounts/$this->AccountSid/Calls/VoiceVerify?sig=$sig";
        // 生成授权：主帐户Id + 英文冒号 + 时间戳。
        $authen = base64_encode($this->AccountSid . ':' . $this->Batch);
        // 生成包头
        $header = array("Accept:application/$this->BodyType", "Content-Type:application/$this->BodyType;charset=utf-8", "Authorization:$authen");
        // 发送请求
        $result = $this->curl_post($url, $body, $header);
        if ($this->BodyType === 'json') {//JSON格式
            $datas = json_decode($result);
        } else { //xml格式
            $datas = simplexml_load_string(trim($result, " \t\n\r"));
        }

        return $datas;
    }

   /**
    * 主帐号鉴权
    *
    * @return mixed
    */
   public function accAuth()
   {
       if ($this->ServerIP === '') {
           $data = new stdClass();
           $data->statusCode = '172004';
           $data->statusMsg = 'IP为空';

           return $data;
       }
       if ($this->ServerPort <= 0) {
           $data = new stdClass();
           $data->statusCode = '172005';
           $data->statusMsg = '端口错误（小于等于0）';

           return $data;
       }
       if ($this->SoftVersion === '') {
           $data = new stdClass();
           $data->statusCode = '172013';
           $data->statusMsg = '版本号为空';

           return $data;
       }
       if ($this->AccountSid === '') {
           $data = new stdClass();
           $data->statusCode = '172006';
           $data->statusMsg = '主帐号为空';

           return $data;
       }
       if ($this->AccountToken === '') {
           $data = new stdClass();
           $data->statusCode = '172007';
           $data->statusMsg = '主帐号令牌为空';

           return $data;
       }
       if ($this->AppId === '') {
           $data = new stdClass();
           $data->statusCode = '172012';
           $data->statusMsg = '应用ID为空';

           return $data;
       }

       return true;
   }
}
