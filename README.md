# PhpSms
[![StyleCI](https://styleci.io/repos/44543599/shield)](https://styleci.io/repos/44543599)
[![Build Status](https://travis-ci.org/toplan/phpsms.svg?branch=master)](https://travis-ci.org/toplan/phpsms)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/toplan/phpsms/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/toplan/phpsms/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/toplan/phpsms/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/toplan/phpsms/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/toplan/phpsms.svg)](https://packagist.org/packages/toplan/phpsms)
[![Total Downloads](https://img.shields.io/packagist/dt/toplan/phpsms.svg)](https://packagist.org/packages/toplan/phpsms)

可能是目前最靠谱、优雅的php短信发送库了。

> phpsms的任务负载均衡功能由[task-balancer](https://github.com/toplan/task-balancer)提供。

# 特点
1. 支持请求发送负载均衡，可按代理器权重值均衡选择服务商发送。
2. 支持一个或多个备用代理器(服务商)。
3. 允许推入队列，并自定义队列实现逻辑(与队列系统松散耦合)。
4. 支持语音验证码。
5. 短信/语音验证码发送前后钩子。
6. 支持国内主流短信服务商(可自定义代理器)

| 服务商 | 模板短信 | 内容短信 | 语音验证码 | 最低消费  |  最低消费单价 |
| ----- | :-----: | :-----: | :------: | :-------: | :-----: |
| [Luosimao](http://luosimao.com)        | no  | yes |  yes    |￥850(1万条) |￥0.085/条|
| [云片网络](http://www.yunpian.com)       | no | yes  | yes    |￥55(1千条)  |￥0.055/条|
| [容联·云通讯](http://www.yuntongxun.com) | yes | no  | yes    |充值￥500    |￥0.055/条|
| [SUBMAIL](http://submail.cn)           | yes | no  | no      |￥100(1千条) |￥0.100/条|
| [云之讯](http://www.ucpaas.com/)        | yes | no  | yes     |            |￥0.050/条|

# 安装

```php
composer require 'toplan/phpsms:~1.0.0'
```

# 快速上手

###1. 配置

- 配置可用代理器

  在`config\phpsms.php`中键为`enable`的数组中配置。也可以手动在程序中设置：
```php
//example:
Sms::enable([
    //被使用概率为2/15
    'YunTongXun' => '20',

    //被使用概率为10/15，且为备用代理器
    'Luosimao' => '100 backup',

    //被使用概率为3/15，且为备用代理器
    'YunPian'  => '30 backup'
]);
```

- 配置代理器所需参数

  在`config\phpsms.php`中键为`agents`的数组中配置。也可以手动在程序中设置：
```php
//example:
Sms::agents([
    'Luosimao' => [
        //some options
    ],
    'YunPian'  => [
        'apikey' => '...',
    ]
]);
```

###2. Enjoy it!

```php
require('path/to/vendor/autoload.php');
use Toplan\PhpSms\Sms;

// 只希望使用模板方式发送短信，可以不设置content。
// 如:云通讯、Submail、Ucpaas
Sms::make()->to('1828****349')->template($templates)->data(['12345', 5])->send();

// 只希望使用内容方式放送，可以不设置模板id和模板数据data。
// 如:云片、luosimao
Sms::make()->to('1828****349')->content('【PhpSMS】亲爱的张三，欢迎访问，祝你工作愉快。')->send();

// 同时确保能通过模板和内容方式发送。
// 这样做的好处是，可以兼顾到各种类型服务商。
Sms::make()->to('1828****349')
     ->template([
         'YunTongXun' => '123',
         'SubMail'    => '123'
     ])
     ->data(['张三'])
     ->content('【签名】亲爱的张三，欢迎访问，祝你工作愉快。')
     ->send();

//语言验证码
Sms::voice('1111')->to('1828****349')->send();
```

###3. 在laravel中使用

如果你只想单纯的在laravel中使用phpsms的功能可以按如下步骤操作，
当然也为你准备了基于phpsms开发的增强版[laravel-sms](https://github.com/toplan/laravel-sms)

* 在config/app.php中引入服务提供器

```php
//服务提供器
'providers' => [
    ...
    Toplan\PhpSms\PhpSmsServiceProvide::class,
]

//别名
'aliases' => [
    ...
    'PhpSms' => Toplan\PhpSms\Facades\Sms::class,
]
```

* 生成配置文件

```php
php artisan vendor:publish
```
生成的配置文件为config/phpsms.php，然后在该文件中按提示配置。

* 使用

详见API，示例：
```php
PhpSms::make()->to($to)->content($content)->send();
```

# API

### Sms::enable($name, $optionString)

手动设置可用代理器(优先级高于配置文件)，如：
```php
   Sms::enable([
        'Luosimao' => '80 backup'
        'YunPian' => '100 backup'
   ]);
   //或
   Sms::enable('Luosimao', '80 backup');
   Sms::enable('YunPian', '100 backup');
```
### Sms::agents($name, $config);

手动设置代理器配置参数(优先级高于配置文件)，如：
```php
   Sms::agents([
       'YunPian' => [
           'apikey' => '',
       ]
   ]);
   //或
   Sms::agents('YunPian', [
       'apikey' => '',
   ]);
```

### Sms::beforeSend($handler, $override);

短信发送前钩子。
```php
Sms::beforeSend(function($task, $prev, $index, $handlers){
    //获取短信数据
    $smsData = $task->data;
    //do something here
});
```
> 更多细节请查看[task-balancer](https://github.com/toplan/task-balancer)的“beforeRun”钩子

### Sms::afterSend($handler, $override);

短信发送后钩子。
```php
Sms::afterSend(function($task, $result, $prev, $index, $handlers){
    //$results为短信发送后获得的结果数组
    //do something here
});
```
> 更多细节请查看[task-balancer](https://github.com/toplan/task-balancer)的“afterRun”钩子

### Sms::queue($enable, $handler)

设置是否启用队列以及定义如何推送到队列。

> $handler可使用的参数:
>
> `$sms` : Sms实例。
> `$data` : Sms实例中的短信数据，等同于`$sms->getData()`。

定义如何推送到队列：
```php
Sms::queue(function($sms, $data){
    //define how to push to queue.
});//自动启用队列
//or
Sms::queue(true, function($sms, $data){
    //define how to push to queue.
});//第一个参数为true,启用队列。
//or
Sms::queue(false, function($sms, $data){
    //define how to push to queue.
});//第一个参数为false,暂时关闭队列。
```

如果已经定义过如何推送到队列，还可以继续设置关闭/开启队列：
```php
Sms::queue(true);//开启队列
Sms::queue(false);//关闭队列
```

获取队列启用情况：
```php
$enable = Sms::queue();
//为true,表示当前启用了队列。
//为false,表示当前关闭了队列。
```

### Sms::make()

生成发送短信的sms实例，并返回该实例。
```php
  $sms = Sms::make();
```

### Sms::voice($code)

生成发送语音验证码的sms实例，并返回该实例。
```php
  $sms = Sms::voice($code)
```
### $sms->to($mobile)

设置发送给谁，并返回实例。
```php
   $sms->to('1828*******');
```

### $sms->template($templates)

指定代理器进行设置或批量设置:
```php
   //静态方法设置，并返回sms实例
   Sms::make(['YunTongXun' => '20001', 'SubMail' => 'xxx', ...]);
   //设置指定服务商的模板id
   $sms->template('YunTongXun', '20001')->template('SubMail', 'xxx');
   //一次性设置多个服务商的模板id
   $sms->template(['YunTongXun' => '20001', 'SubMail' => 'xxx', ...]);
```

### $sms->data($templateData)

设置模板短信的模板数据，并返回实例对象，`$templateData`必须为数组。
```php
  $sms = $sms->data([
        'code' => $code,
        'minutes' => $minutes
      ]);
```

### $sms->content($text)

设置内容短信的内容，并返回实例对象。
有些服务商(如YunPian,Luosimao)只支持内容短信(即直接发送短信内容)，那么就需要为它们设置短信内容。
```php
  $sms = $sms->content('【签名】您的订单号是xxxx，祝你购物愉快。');
```

### $sms->agent($name)

临时设置发送时使用的代理器(不会影响备用代理器的正常使用)，`$name`为代理器名称。
```php
  $sms = $sms->agent('Luosimao');
```
> 通过该方法设置的代理器将获得绝对优先权，但只对当前短信实例有效。

### $sms->send()

请求发送短信/语音验证码。
```php
  //会遵循是否使用队列:
  $result = $sms->send();

  //忽略是否使用队列:
  $result = $sms->send(true);
```

> `$result`数据结构请参看[task-balancer](https://github.com/toplan/task-balancer)

# 自定义代理器

配置项加入到config/agents.php中：

> 请注意命名规范，Foo为代理器(服务商)名称。

```php
   'Foo' => [
        'apikey' => 'some info',
        ...
   ]
```

在agents目录下添加代理器类：

**代理器类名为`FooAgent`，命名空间为`Toplan\PhpSms`，并继承`Agent`抽象类。**

> 如果使用到其它api库，可以将api库放入lib文件夹中。

```php
   namespace Toplan\PhpSms;
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
            //获取配置文件中的参数
            $x = $this->apikey;
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
        //发送语音验证码入口
        public function voiceVerify($to, $code)
        {
            //同上...
        }
   }
```
至此, 新加代理器成功!

# Todo list

- [] 优化读取配置文件的逻辑，确保必须读取一次配置文件，这样将配置文件`agents`和`Sms::agents()`方法结合起来。
- [] 可用代理器分组配置功能；短信发送时选择分组进行发送的功能。

# Encourage

hi, guys! 如果喜欢或者要收藏，欢迎star。如果要提供意见和bug，欢迎issue或提交pr。

# License

MIT
