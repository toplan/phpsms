# PhpSms
[![StyleCI](https://styleci.io/repos/44543599/shield)](https://styleci.io/repos/44543599)
[![Build Status](https://travis-ci.org/toplan/phpsms.svg?branch=master)](https://travis-ci.org/toplan/phpsms)
[![Code Coverage](https://scrutinizer-ci.com/g/toplan/phpsms/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/toplan/phpsms/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/toplan/phpsms.svg)](https://packagist.org/packages/toplan/phpsms)
[![Total Downloads](https://img.shields.io/packagist/dt/toplan/phpsms.svg)](https://packagist.org/packages/toplan/phpsms)

可能是目前最聪明、优雅的 php 短信发送库了。

> phpsms的任务均衡调度功能由[toplan/task-balancer](https://github.com/toplan/task-balancer)提供。

特别感谢以下赞助者:

[![短信宝](http://toplan.github.io/img/smsbao-logo.png)](http://www.smsbao.com/)

# 特点
- 支持内容短信，模版短信，语音验证码，内容语音，模版语音，语音文件。
- 支持发送均衡调度，可按代理器权重值均衡选择服务商发送。
- 支持一个或多个备用代理器(服务商)。
- 支持代理器调度方案热更新，可随时更新/删除/新加代理器。
- 允许推入队列，并自定义队列实现逻辑(与队列系统松散耦合)。
- 灵活的发送前后钩子。
- 内置国内主流服务商的代理器。
- [自定义代理器](#自定义代理器)和[寄生代理器](#寄生代理器)。

# 服务商

| 服务商 | 模板短信 | 内容短信 | 语音验证码 | 最低消费  |  最低消费单价 | 资费标准
| ----- | :-----: | :-----: | :------: | :-------: | :-----: | :-----:
| [Luosimao](http://luosimao.com)        | × | √ | √ | ￥850(1万条) | ￥0.085/条 | [资费标准](https://luosimao.com/service/sms#sms-price)
| [云片网络](http://www.yunpian.com)      | × | √ | √ | ￥55(1千条) | ￥0.055/条 | [资费标准](http://www.yunpian.com/price.html)
| [容联·云通讯](http://www.yuntongxun.com) | √ | × | √ | 充值￥500   | ￥0.055/条 | [资费标准](http://www.yuntongxun.com/price/price_sms.html)
| [SUBMAIL](http://submail.cn)           | √ | × | √ | ￥100(1千条) | ￥0.100/条 | [资费标准](https://www.mysubmail.com/chs/store#/message)
| [云之讯](http://www.ucpaas.com/)        | √ | × | √ | -- | ￥0.050/条 | [资费标准](http://www.ucpaas.com/service/sms.html)
| [聚合数据](https://www.juhe.cn/)        | √ | × | √ | -- | ￥0.035/条 | [资费标准](https://www.juhe.cn/docs/api/id/54)
| [阿里大鱼](https://www.alidayu.com/)    | √ | × | √ | -- | ￥0.045/条 | [资费标准](https://www.alidayu.com/service/price)
| [SendCloud](https://sendcloud.sohu.com/) | √ | × | √ | -- | ￥0.048/条 | [资费标准](https://sendcloud.sohu.com/price.html)
| [短信宝](http://www.smsbao.com/)          | × | √ | √ | ￥5(50条) | ￥0.040/条(100万条) | [资费标准](http://www.smsbao.com/fee/)
| [腾讯云](https://www.qcloud.com/product/sms) | √ | √ | √ | -- | ￥0.045/条 | [资费标准](https://www.qcloud.com/product/sms#price)
| [阿里云](https://www.aliyun.com/product/sms) | √ | × | × | -- | ￥0.045/条 | [资费标准](https://cn.aliyun.com/price/product#/mns/detail)

# 安装

```php
composer require toplan/phpsms:~1.8
```

开发中版本
```php
composer require toplan/phpsms:dev-master
```

# 快速上手

### 1. 配置

- 配置代理器所需参数

为你需要用到的短信服务商(即代理器)配置必要的参数。可以在`config\phpsms.php`中键为`agents`的数组中配置，也可以手动在程序中设置，示例如下：

```php
//example:
Sms::config([
    'Luosimao' => [
        'apikey' => 'your api key',
        'voiceApikey' => 'your voice api key',
    ],
    'YunPian'  => [
        'apikey' => 'your api key',
    ],
    'SmsBao' => [
        'username' => 'your username',
        'password'  => 'your password'
    ]
]);
```

- 配置代理器调度方案

可在`config\phpsms.php`中键为`scheme`的数组中配置。也可以手动在程序中设置，示例如下：

```php
//example:
Sms::scheme([
    //被使用概率为2/3
    'Luosimao' => '20',

    //被使用概率为1/3，且为备用代理器
    'YunPian' => '10 backup',

    //仅为备用代理器
    'SmsBao' => '0 backup',
]);
```
> **调度方案解析：**
> 如果按照以上配置，那么系统首次会尝试使用`Luosimao`或`YunPian`发送短信，且它们被使用的概率分别为`2/3`和`1/3`。
> 如果使用其中一个代理器发送失败，那么会启用备用代理器，按照配置可知备用代理器有`YunPian`和`SmsBao`，那么会依次调用直到发送成功或无备用代理器可用。
> 值得注意的是，如果首次尝试的是`YunPian`，那么备用代理器将会只使用`SmsBao`，也就是会排除使用过的代理器。

### 2. Enjoy it!

```php
require('path/to/vendor/autoload.php');
use Toplan\PhpSms\Sms;

// 接收人手机号
$to = '1828****349';
// 短信模版
$templates = [
    'YunTongXun' => 'your_temp_id',
    'SubMail'    => 'your_temp_id'
];
// 模版数据
$tempData = [
    'code' => '87392',
    'minutes' => '5'
];
// 短信内容
$content = '【签名】这是短信内容...';

// 只希望使用模板方式发送短信，可以不设置content(如:云通讯、Submail、Ucpaas)
Sms::make()->to($to)->template($templates)->data($tempData)->send();

// 只希望使用内容方式发送，可以不设置模板id和模板data(如:短信宝、云片、luosimao)
Sms::make()->to($to)->content($content)->send();

// 同时确保能通过模板和内容方式发送，这样做的好处是可以兼顾到各种类型服务商
Sms::make()->to($to)
    ->template($templates)
    ->data($tempData)
    ->content($content)
    ->send();

// 语音验证码
Sms::voice('02343')->to($to)->send();

// 语音验证码兼容模版语音(如阿里大鱼的文本转语音)
Sms::voice('02343')
    ->template('Alidayu', 'your_tts_code')
    ->data(['code' => '02343'])
    ->to($to)
    ->send();
```

### 3. 在laravel和lumen中使用

* 服务提供器

```php
//服务提供器
'providers' => [
    ...
    Toplan\PhpSms\PhpSmsServiceProvider::class,
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

## API - 全局配置

### Sms::scheme([$name[, $scheme]])

设置/获取代理器的调度方案。

> 调度配置支持热更新，即在应用系统的整个运行过程中都能随时修改。

- 设置

手动设置代理器调度方案(优先级高于配置文件)，如：
```php
Sms::scheme([
    'SmsBao' => '80 backup'
    'YunPian' => '100 backup'
]);
//或
Sms::scheme('SmsBao', '80 backup');
Sms::scheme('YunPian', '100 backup');
```
- 获取

通过该方法还能获取所有或指定代理器的调度方案，如：
```php
//获取所有的调度方案:
$scheme = Sms::scheme();

//获取指定代理器的调度方案:
$scheme['SmsBao'] = Sms::scheme('SmsBao');
```

> `scheme`静态方法的更多使用方法见[高级调度配置](#高级调度配置)

### Sms::config([$name[, $config][, $override]]);

设置/获取代理器的配置数据。

> 参数配置支持热更新，即在应用系统的整个运行过程中都能随时修改。

- 设置

手动设置代理器的配置数据(优先级高于配置文件)，如：
```php
Sms::config([
   'SmsBao' => [
       'username' => ...,
       'password' => ...,
   ]
]);
//或
Sms::config('SmsBao', [
   'username' => ...,
   'password' => ...,
]);
```
- 获取

通过该方法还能获取所有或指定代理器的配置参数，如：
```php
//获取所有的配置:
$config = Sms::config();

//获取指定代理器的配置:
$config['SmsBao'] = Sms::config('SmsBao');
```

### Sms::beforeSend($handler[, $override]);

发送前钩子，示例：
```php
Sms::beforeSend(function($task, $index, $handlers, $prevReturn){
    //获取短信数据
    $smsData = $task->data;
    ...
    //如果返回false会终止发送任务
    return true;
});
```
> 更多细节请查看 [task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle) 的 `beforeRun` 钩子

### Sms::beforeAgentSend($handler[, $override]);

代理器发送前钩子，示例：
```php
Sms::beforeAgentSend(function($task, $driver, $index, $handlers, $prevReturn){
    //短信数据:
    $smsData = $task->data;
    //当前使用的代理器名称:
    $agentName = $driver->name;
    //如果返回false会停止使用当前代理器
    return true;
});
```
> 更多细节请查看 [task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle) 的 `beforeDriverRun` 钩子

### Sms::afterAgentSend($handler[, $override]);

代理器发送后钩子，示例：
```php
Sms::afterAgentSend(function($task, $agentResult, $index, $handlers, $prevReturn){
     //$result为代理器的发送结果数据
     $agentName = $agentResult['driver'];
     ...
});
```
> 更多细节请查看 [task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle) 的 `afterDriverRun` 钩子

### Sms::afterSend($handler[, $override]);

发送后钩子，示例：
```php
Sms::afterSend(function($task, $taskResult, $index, $handlers, $prevReturn){
    //$result为发送后获得的结果数组
    $success = $taskResult['success'];
    ...
});
```
> 更多细节请查看 [task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle) 的 `afterRun` 钩子

### Sms::queue([$enable[, $handler]])

该方法可以设置是否启用队列以及定义如何推送到队列。

`$handler`匿名函数可使用的参数:
+ `$sms` : Sms实例
+ `$data` : Sms实例中的短信数据，等同于`$sms->all()`

定义如何推送到队列：
```php
//自动启用队列
Sms::queue(function($sms, $data){
    //define how to push to queue.
    ...
});

//第一个参数为true,启用队列
Sms::queue(true, function($sms, $data){
    //define how to push to queue.
    ...
});

//第一个参数为false,暂时关闭队列
Sms::queue(false, function($sms, $data){
    //define how to push to queue.
    ...
});
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

## API - 发送相关

### Sms::make()

生成发送短信的sms实例，并返回实例。
```php
$sms = Sms::make();

//创建实例的同时设置短信内容：
$sms = Sms::make('【签名】这是短信内容...');

//创建实例的同时设置短信模版：
$sms = Sms::make('YunTongXun', 'your_temp_id');
//或
$sms = Sms::make([
    'YunTongXun' => 'your_temp_id',
    'SubMail' => 'your_temp_id',
    ...
]);
```

### Sms::voice()

生成发送语音验证码的sms实例，并返回实例。
```php
$sms = Sms::voice();

//创建实例的同时设置验证码
$sms = Sms::voice($code);
```

> - 如果你使用`Luosimao`语音验证码，还需用在配置文件中`Luosimao`选项中设置`voiceApikey`。
> - **语音文件ID**即是在服务商配置的语音文件的唯一编号，比如阿里大鱼[语音通知](http://open.taobao.com/doc2/apiDetail.htm?spm=a219a.7395905.0.0.oORhh9&apiId=25445)的`voice_code`。
> - **模版语音**是另一种语音请求方式，它是通过模版ID和模版数据进行的语音请求，比如阿里大鱼的[文本转语音通知](http://open.taobao.com/doc2/apiDetail.htm?spm=a219a.7395905.0.0.f04PJ3&apiId=25444)。

### type($type)

设置实例类型，可选值有`Sms::TYPE_SMS`和`Sms::TYPE_VOICE`，返回实例对象。

### to($mobile)

设置发送给谁，并返回实例。
```php
$sms->to('1828*******');

//兼容腾讯云
$sms->to([86, '1828*******'])
```

### template($agentName, $id)

指定代理器设置模版或批量设置，并返回实例。
```php
//设置指定服务商的模板id
$sms->template('YunTongXun', 'your_temp_id')
    ->template('SubMail', 'your_temp_id');

//一次性设置多个服务商的模板id
$sms->template([
    'YunTongXun' => 'your_temp_id',
    'SubMail' => 'your_temp_id',
    ...
]);
```

### data($key, $value)

设置模板短信的模板数据，并返回实例对象。
```php
//单个数据
$sms->data('code', $code);

//同时设置多个数据
$sms->data([
    'code' => $code,
    'minutes' => $minutes
]);
```

> 通过`template`和`data`方法的组合除了可以实现模版短信的数据填充，还可以实现模版语音的数据填充。

### content($text)

设置内容短信的内容，并返回实例对象。

> 一些内置的代理器(如SmsBao、YunPian、Luosimao)使用的是内容短信(即直接发送短信内容)，那么就需要为它们设置短信内容。

```php
$sms->content('【签名】这是短信内容...');
```

### code($code)

设置语音验证码，并返回实例对象。

### file($agentName, $id)

设置语音文件，并返回实例对象。
```php
$sms->file('Agent1', 'agent1_file_id')
    ->file('Agent2', 'agent2_file_id');

//或
$sms->file([
    'Agent1' => 'agent1_file_id',
    'Agent2' => 'agent2_fiile_id',
]);
```

### params($agentName, $params)

直接设置参数到服务商提供的原生接口上，并返回实例对象。
```php
$sms->params('Agent1', [
    'callbackUrl' => ...,
    'userData'    => ...,
]);

//或
$sms->params([
    'Agent1' => [
        'callbackUrl' => ...,
        'userData'    => ...,
    ],
    'Agent2' => [
        ...
    ],
]);
```

### all([$key])

获取Sms实例中的短信数据，不带参数时返回所有数据，其结构如下：
```php
[
    'type'      => ...,
    'to'        => ...,
    'templates' => [...],
    'data'      => [...], // template data
    'content'   => ...,
    'code'      => ...,   // voice code
    'files'     => [...], // voice files
    'params'    => [...],
]
```

### agent($name)

临时设置发送时使用的代理器(不会影响备用代理器的正常使用)，并返回实例，`$name`为代理器名称。
```php
$sms->agent('SmsBao');
```
> 通过该方法设置的代理器将获得绝对优先权，但只对当前短信实例有效。

### send()

请求发送短信/语音验证码。
```php
//会遵循是否使用队列
$result = $sms->send();

//忽略是否使用队列
$result = $sms->send(true);
```

> `$result`数据结构请参看[task-balancer](https://github.com/toplan/task-balancer)

# 自定义代理器

- step 1

可将配置项(如果有用到)加入到`config/phpsms.php`中键为`agents`的数组里。

```php
//example:
'Foo' => [
    'key' => 'your api key',
    ...
]
```

- step 2

新建一个继承`Toplan\PhpSms\Agent`抽象类的代理器类，建议代理器类名为`FooAgent`，建议命名空间为`Toplan\PhpSms`。

> 如果类名不为`FooAgent`或者命名空间不为`Toplan\PhpSms`，在使用该代理器时则需要指定代理器类，详见[高级调度配置](#高级调度配置)。

- step 3

实现相应的接口，可选的接口有:

| 接口           | 说明         |
| ------------- | :----------: |
| ContentSms    | 发送内容短信   |
| TemplateSms   | 发送模版短信   |
| VoiceCode     | 发送语音验证码 |
| ContentVoice  | 发送内容语音   |
| TemplateVoice | 发送模版语音   |
| FileVoice     | 发送文件语音   |

# 高级调度配置

代理器的高级调度配置可以通过配置文件(`config/phpsms.php`)中的`scheme`项目配置，也可以通过`scheme`静态方法设置。
值得注意的是，高级调度配置的值的数据结构是数组。

### 指定代理器类

如果你自定义了一个代理器，类名不为`FooAgent`或者命名空间不为`Toplan\PhpSms`，
那么你还可以在调度配置时指定你的代理器使用的类。

* 配置方式：

通过配置值中`agentClass`键来指定类名。

* 示例：
```php
Sms::scheme('agentName', [
    '10 backup',
    'agentClass' => 'My\Namespace\MyAgentClass'
]);
```

### 寄生代理器

如果你既不想使用内置的代理器，也不想创建文件写自定义代理器，那么寄生代理器或许是个好的选择，
无需定义代理器类，只需在调度配置时定义好发送短信和语音验证码的方式即可。

* 配置方式：

可以配置的发送过程有:

| 发送过程           | 参数列表                        | 说明         |
| ----------------- | :---------------------------: | :----------: |
| sendContentSms    | $agent, $to, $content         | 发送内容短信   |
| sendTemplateSms   | $agent, $to, $tmpId, $tmpData | 发送模版短信   |
| sendVoiceCode     | $agent, $to, $code            | 发送语音验证码  |
| sendContentVoice  | $agent, $to, $content         | 发送内容语音   |
| sendTemplateVoice | $agent, $to, $tmpId, $tmpData | 发送模版语音   |
| sendFileVoice     | $agent, $to, $fileId          | 发送文件语音   |

* 示例：
```php
Sms::scheme([
    'agentName' => [
        '20 backup',
        'sendContentSms' => function($agent, $to, $content){
            // 获取配置(如果设置了的话):
            $key = $agent->key;
            ...
            // 可使用的内置方法:
            $agent->curlGet($url, $params); //get
            $agent->curlPost($url, $params); //post
            ...
            // 更新发送结果:
            $agent->result(Agent::SUCCESS, true);
            $agent->result(Agent::INFO, 'some info');
            $agent->result(Agent::CODE, 'your code');
        },
        'sendVoiceCode' => function($agent, $to, $code){
            // 发送语音验证码，同上
        }
    ]
]);
```

# Todo

- [ ] 重新实现云通讯代理器，去掉`lib/CCPRestSmsSDK.php`
- [ ] 重新实现云之讯代理器，去掉`lib/Ucpaas.php`
- [ ] 升级云片接口到v2版本

# License

MIT
