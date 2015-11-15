# PhpSms
可能是目前最靠谱、优雅的php短信发送库了。

< phpsms的任务负载均衡功能由[task-balancer](https://github.com/toplan/task-balancer)提供。

# 特点
1. 支持负载均衡，可以按代理器权重值均衡选择代理器发送。
2. 支持一个或多个‘backup’备用代理器。
3. 已经支持国内主流短信服务商(你也可以自定义代理器)：

| 服务商 | 模板短信 | 内容短信 | 语音验证码 | 最低消费  |  最低消费单价 |
| ----- | :-----: | :-----: | :------: | :-------: | :-----: |
| [Luosimao](http://luosimao.com)        | no  | yes |  yes    |￥850(1万条) |￥0.085/条|
| [云片网络](http://www.yunpian.com)       | no | yes  | yes    |￥55(1千条)  |￥0.055/条|
| [容联·云通讯](http://www.yuntongxun.com) | yes | no  | yes    |充值￥500    |￥0.055/条|
| [SUBMAIL](http://submail.cn)           | yes | no  | no      |￥100(1千条) |￥0.100/条|
| [云之讯](http://www.ucpaas.com/)        | yes | no  | yes     |            |￥0.050/条|

# 安装

```php
composer require 'toplan/phpsms:~0.0.1'
```

# 快速上手

###1. 配置

- 配置可用代理器

  见`config\phpsms.php`

- 配置代理器所需参数

  见`config\agents.php`

###2. Enjoy it!

```php
require('path/to/vendor/autoload.php');
use Toplan\PhpSms\Sms;

//只希望使用模板方式发送短信,可以不设置内容content (如云通讯,Submail)
Sms::make($tempId)->to('1828****349')->data(['12345', 5])->send();

//只希望使用内容方式放送,可以不设置模板id和模板数据data (如云片,luosimao)
Sms::make()->to('1828****349')->content('【PhpSMS】亲爱的张三，欢迎访问，祝你工作愉快。')->send();

//同时确保能通过模板和内容方式发送。这样做的好处是，可以兼顾到各种代理器(服务商)！
Sms::make([
      'YunTongXun' => '123',
      'SubMail'    => '123'
  ])
  ->to('1828****349')
  ->data(['张三'])
  ->content('【签名】亲爱的张三，欢迎访问，祝你工作愉快。')
  ->send();
```

###4. 语法糖

   * 生成发送短信的instance
```php
  $sms = Sms::make($code)
```

  ＊ 生成发送语音验证码的instance
```php
  $sms = Sms::voice($code)
```

   * 发送给谁
```php
   $sms = $sms->to('1828*******');
   $sms = $sms->to(['1828*******', '1828*******', ...]);//多个目标号码
```

   * 设置模板ID

如果你只想给第一个代理器设置模板ID, 你只需要传入一个id参数:
```php
   //静态方法设置，并返回sms实例
   $sms = Sms::make('20001');
   //或
   $sms = $sms->template('20001');
```

也可以这样设置:
```php
   //静态方法设置，并返回sms实例
   $sms = Sms::make(['YunTongXun' => '20001', 'SubMail' => 'xxx', ...]);
   //设置指定服务商的模板id
   $sms = $sms->template('YunTongXun', '20001')->template('SubMail' => 'xxx');
   //一次性设置多个服务商的模板id
   $sms = $sms->template(['YunTongXun' => '20001', 'SubMail' => 'xxx', ...]);
```

  * 设置模板短信的模板数据
```php
  $sms = $sms->data([
        'code' => $code,
        'minutes' => $minutes
      ]);//必须是数组
```

  * 设置内容短信的内容

  有些服务商(如YunPian,Luosimao)只支持内容短信(即直接发送短信内容)，不支持模板，那么就需要设置短信内容。
```php
  $sms = $sms->content('【签名】亲爱的张三，您的订单号是281xxxx，祝你购物愉快。');
```

  * 发送短信
```php
  $results = $sms->send();
```


##自定义代理器

请注意命名规范，Foo为代理器(服务商)名称。配置项加入到config/agents.php中：
```php
   'Foo' => [
        'xxx' => 'some info',
        ...
   ]
```

在agents目录下添加代理器类(注意类名为FooAgent),并继承Agent抽象类。如果使用到其他api，可以将api文件放入src/phpsms/lib文件夹中。
```php
   namespace Toplan\Sms;
   class FooAgent extends Agent {
        //override
        //发送短信一级入口
        public function sendSms($tempId, $to, Array $data, $content){
           //在这个方法中调用二级入口
           //根据你使用的服务商的接口选择调用哪个方式发送短信
           $this->sendContentSms($to, $content);
           $this->sendTemplateSms($tempId, $to, Array $data);
        }

        //override
        //发送短信二级入口：发送内容短信
        public function sendContentSms($to, $content)
        {
            //通过$this->config['key'],获取配置文件中的参数
            $x = $this->config['xxx'];
            $x = $this->xxx;//也可以这样获取配置参数
            //在这里实现发送内容短信，即直接发送内容
            ...
            //切记将发送结果存入到$this->result
            $this->result['success'] = false;//是否发送成功
            $this->result['info'] = $msg;//发送结果信息说明
            $this->result['code'] = $code;//发送结果代码
        }

        //override
        //发送短信二级入口：发送模板短信
        public function sendTemplateSms($tempId, $to, Array $data)
        {
            //同上...
        }

        //override
        //发送语音验证码
        public function voiceVerify($to, $code)
        {
            //同上...
        }
   }
```
至此, 新加代理器成功!

##License

MIT
