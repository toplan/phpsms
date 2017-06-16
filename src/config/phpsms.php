<?php

return [
    /*
     * The scheme information
     * -------------------------------------------------------------------
     *
     * The key-value paris: {name} => {value}
     *
     * Examples:
     * 'Log' => '10 backup'
     * 'SmsBao' => '100'
     * 'CustomAgent' => [
     *     '5 backup',
     *     'agentClass' => '/Namespace/ClassName'
     * ]
     *
     * Supported agents:
     * 'Log', 'YunPian', 'YunTongXun', 'SubMail', 'Luosimao',
     * 'Ucpaas', 'JuHe', 'Alidayu', 'SendCloud', 'SmsBao',
     * 'Qcloud', 'Aliyun'
     *
     */
    'scheme' => [
        'Log',
    ],

    /*
     * The configuration
     * -------------------------------------------------------------------
     *
     * Expected the name of agent to be a string.
     *
     */
    'agents' => [
        /*
         * -----------------------------------
         * YunPian
         * 云片代理器
         * -----------------------------------
         * website:http://www.yunpian.com
         * support content sms.
         */
        'YunPian' => [
            //用户唯一标识，必须
            'apikey' => 'your api key',
        ],

        /*
         * -----------------------------------
         * YunTongXun
         * 云通讯代理器
         * -----------------------------------
         * website：http://www.yuntongxun.com/
         * support template sms.
         */
        'YunTongXun' => [
            //主帐号
            'accountSid' => 'your account sid',

            //主帐号令牌
            'accountToken' => 'your account token',

            //应用Id
            'appId' => 'your app id',

            //请求地址(不加协议前缀)
            'serverIP' => 'app.cloopen.com',

            //请求端口
            'serverPort' => '8883',

            //被叫号显
            'displayNum' => null,

            //语音验证码播放次数
            'playTimes' => 3,
        ],

        /*
         * -----------------------------------
         * SubMail
         * -----------------------------------
         * website:http://submail.cn/
         * support template sms.
         */
        'SubMail' => [

            'appid' => 'your app id',

            'signature' => 'your app key',
        ],

        /*
         * -----------------------------------
         * luosimao
         * -----------------------------------
         * website:http://luosimao.com
         * support content sms.
         */
        'Luosimao' => [
            //短信 API key
            //在管理中心->短信->触发发送下查看
            'apikey' => 'your api key',

            //语言验证 API key
            //在管理中心->语音->语音验证下查看
            'voiceApikey' => 'your voice api key',
        ],

        /*
         * -----------------------------------
         * ucpaas
         * -----------------------------------
         * website:http://ucpaas.com
         * support template sms.
         */
        'Ucpaas' => [
            //主帐号,对应开官网发者主账号下的 ACCOUNT SID
            'accountSid' => 'your account sid',

            //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
            'accountToken' => 'your account token',

            //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
            //在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
            'appId' => 'your app id',
        ],

        /*
         * -----------------------------------
         * JuHe
         * 聚合数据
         * -----------------------------------
         * website:https://www.juhe.cn
         * support template sms.
         */
        'JuHe' => [
            //应用App Key
            'key' => 'your key',

            //语音验证码播放次数
            'times' => 3,
        ],

        /*
         * -----------------------------------
         * Alidayu
         * 阿里大鱼代理器
         * -----------------------------------
         * website:http://www.alidayu.com
         * support template sms.
         */
        'Alidayu' => [
            //请求地址
            'sendUrl' => 'http://gw.api.taobao.com/router/rest',

            //淘宝开放平台中，对应阿里大鱼短信应用的App Key
            'appKey' => 'your app key',

            //淘宝开放平台中，对应阿里大鱼短信应用的App Secret
            'secretKey' => 'your secret key',

            //短信签名，传入的短信签名必须是在阿里大鱼“管理中心-短信签名管理”中的可用签名
            'smsFreeSignName' => 'your sms free sign name',

            //被叫号显(用于语音通知)，传入的显示号码必须是阿里大鱼“管理中心-号码管理”中申请或购买的号码
            'calledShowNum' => null,
        ],

        /*
         * -----------------------------------
         * SendCloud
         * -----------------------------------
         * website: http://sendcloud.sohu.com/sms/
         * support template sms.
         */
        'SendCloud' => [
            //SMS_USER
            'smsUser' => 'your SMS_USER',

            //SMS_KEY
            'smsKey' => 'your SMS_KEY',
        ],

        /*
         * -----------------------------------
         * SmsBao
         * -----------------------------------
         * website: http://www.smsbao.com
         * support content sms.
         */
        'SmsBao' => [
            //注册账号
            'username' => 'your username',

            //账号密码（明文）
            'password' => 'your password',
        ],

        /*
         * -----------------------------------
         * Qcloud
         * 腾讯云
         * -----------------------------------
         * website:http://www.qcloud.com
         * support template sms.
         */
        'Qcloud' => [
            //App ID
            'appId' => 'your app id',

            //App KEY
            'appKey' => 'your app key',
        ],

        /*
         * -----------------------------------
         * Aliyun
         * 阿里云
         * -----------------------------------
         * website:https://www.aliyun.com/product/sms
         * support template sms.
         */
        'AliyunSms' => [
            //阿里云颁发给用户的访问服务所用的密钥ID
            'accessKeyId' => 'your access key id',

            //阿里云颁发给用户的，用于加密签名字符串和服务器端验证签名字符串的密钥
            'accessKeySecret' => 'your access key secret',

            //阿里云管理控制台中配置的短信签名（状态必须是验证通过）
            'signName' => 'your sms sign name',
        ],
    ],
];
