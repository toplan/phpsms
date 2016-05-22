# PhpSms
[![StyleCI](https://styleci.io/repos/44543599/shield)](https://styleci.io/repos/44543599)
[![Build Status](https://travis-ci.org/toplan/phpsms.svg?branch=master)](https://travis-ci.org/toplan/phpsms)
[![Code Coverage](https://scrutinizer-ci.com/g/toplan/phpsms/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/toplan/phpsms/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/toplan/phpsms.svg)](https://packagist.org/packages/toplan/phpsms)
[![Total Downloads](https://img.shields.io/packagist/dt/toplan/phpsms.svg)](https://packagist.org/packages/toplan/phpsms)

可能是目前最聪明、优雅的php短信发送库了。从此不再为各种原因造成的个别短信发送失败而烦忧！

> phpsms的任务均衡调度功能由[toplan/task-balancer](https://github.com/toplan/task-balancer)提供。

# 特点
1. 支持短信/语音发送均衡调度，可按代理器权重值均衡选择服务商发送。
2. 支持一个或多个备用代理器(服务商)。
3. 允许推入队列，并自定义队列实现逻辑(与队列系统松散耦合)。
4. 支持语音验证码。
5. 短信/语音验证码发送前后钩子。
6. 支持国内[主流短信服务商](#服务商)。
7. [自定义代理器](#自定义代理器)和[寄生代理器](#寄生代理器)。

# 服务商

| 服务商 | 模板短信 | 内容短信 | 语音验证码 | 最低消费  |  最低消费单价 |
| ----- | :-----: | :-----: | :------: | :-------: | :-----: |
| [Luosimao](http://luosimao.com)        | × | √ | √ | ￥850(1万条) | ￥0.085/条 |
| [云片网络](http://www.yunpian.com)      | √ | √ | √ | ￥55(1千条) | ￥0.055/条 |
| [容联·云通讯](http://www.yuntongxun.com) | √ | × | √ | 充值￥500   | ￥0.055/条 |
| [SUBMAIL](http://submail.cn)           | √ | × | × | ￥100(1千条) | ￥0.100/条 |
| [云之讯](http://www.ucpaas.com/)        | √ | × | √ | -- | ￥0.050/条 |
| [聚合数据](https://www.juhe.cn/)        | √ | × | √ | -- | ￥0.035/条 |
| [阿里大鱼](https://www.alidayu.com/)    | √ | × | √ | -- | ￥0.045/条 |

# 安装

```php
composer require 'toplan/phpsms:~1.5.3'
```

安装开发中版本:
```php
composer require 'toplan/phpsms:dev-master'
```

# 快速上手

###1. 配置

- 配置代理器所需参数

为你需要用到的短信服务商(即代理器)配置必要的参数。可以在`config\phpsms.php`中键为`agents`的数组中配置，也可以手动在程序中设置，示例如下：

```php
//example:
Sms::config([
    'Luosimao' => [
        //短信API key
        'apikey' => 'your api key',
        //语音验证API key
        'voiceApikey' => 'your voice api key',
    ],
    'YunPian'  => [
        //用户唯一标识，必须
        'apikey' => 'your api key',
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
    'YunTongXun' => '0 backup',
]);
```
> **调度方案解析：**
> 如果按照以上配置，那么系统首次会尝试使用`Luosimao`或`YunPian`发送短信，且它们被使用的概率分别为`2/3`和`1/3`。
> 如果使用其中一个代理器发送失败，那么会启用备用代理器，按照配置可知备用代理器有`YunPian`和`YunTongXun`，那么会依次调用直到发送成功或无备用代理器可用。
> 值得注意的是，如果首次尝试的是`YunPian`，那么备用代理器将会只使用`YunTongXun`，也就是会排除使用过的代理器。

###2. Enjoy it!

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

// 只希望使用模板方式发送短信,可以不设置content(如:云通讯、Submail、Ucpaas)
Sms::make()->to($to)->template($templates)->data($tempData)->send();

// 只希望使用内容方式放送,可以不设置模板id和模板data(如:云片、luosimao)
Sms::make()->to($to)->content($content)->send();

// 同时确保能通过模板和内容方式发送,这样做的好处是,可以兼顾到各种类型服务商
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
    ->to($to)->send();
```

###3. 在laravel中使用

如果你只想单纯的在laravel中使用phpsms的功能可以按如下步骤操作，
当然也为你准备了基于phpsms开发的[laravel-sms](https://github.com/toplan/laravel-sms)

* 在config/app.php中引入服务提供器

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

> 调度配置在调度系统启动后(创建`Sms`实例时会自动启动)就不能修改。

- 设置

手动设置代理器调度方案(优先级高于配置文件)，如：
```php
Sms::scheme([
    'Luosimao' => '80 backup'
    'YunPian' => '100 backup'
]);
//或
Sms::scheme('Luosimao', '80 backup');
Sms::scheme('YunPian', '100 backup');
```
- 获取

通过该方法还能获取所有或指定代理器的调度方案，如：
```php
//获取所有的调度方案:
$scheme = Sms::scheme();

//获取指定代理器的调度方案:
$scheme['Luosimao'] = Sms::scheme('Luosimao');
```

> `scheme`静态方法的更多使用方法见[高级调度配置](#高级调度配置)

### Sms::config([$name[, $config][, $override]]);

设置/获取代理器的配置数据。

> 代理器参数配置在应用系统的整个运行过程中都是能修改的，这点和调度配置有所不同。

- 设置

手动设置代理器的配置数据(优先级高于配置文件)，如：
```php
Sms::config([
   'YunPian' => [
       'apikey' => ...,
   ]
]);
//或
Sms::config('YunPian', [
   'apikey' => ...,
]);
```
- 获取

通过该方法还能获取所有或指定代理器的配置参数，如：
```php
//获取所有的配置:
$config = Sms::config();

//获取指定代理器的配置:
$config['Luosimao'] = Sms::config('Luosimao');
```

### Sms::cleanScheme()

清空所有代理器的调度方案，请谨慎使用该接口。

### Sms::cleanConfig()

清空所有代理器的配置数据，请谨慎使用该接口。

### Sms::beforeSend($handler[, $override]);

发送前钩子，示例：
```php
Sms::beforeSend(function($task, $prev, $index, $handlers){
    //获取短信数据
    $smsData = $task->data;
    ...
    //如果返回false会终止发送任务
    return true;
});
```
> 更多细节请查看[task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle)的“beforeRun”钩子

### Sms::beforeAgentSend($handler [, $override]);

代理器发送前钩子，示例：
```php
Sms::beforeAgentSend(function($task, $driver, $prev, $index, $handlers){
    //短信数据:
    $smsData = $task->data;
    //当前使用的代理器名称:
    $agentName = $driver->name;
    //如果返回false会停止使用当前代理器
    return true;
});
```
> 更多细节请查看[task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle)的“beforeDriverRun”钩子

### Sms::afterAgentSend($handler [, $override]);

代理器发送后钩子，示例：
```php
Sms::afterAgentSend(function($task, $result, $prev, $index, $handlers){
     //$result为代理器的发送结果数据
     $agentName = $result['driver'];
     ...
});
```
> 更多细节请查看[task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle)的“afterDriverRun”钩子

### Sms::afterSend($handler [, $override]);

发送后钩子，示例：
```php
Sms::afterSend(function($task, $result, $prev, $index, $handlers){
    //$result为发送后获得的结果数组
    $success = $result['success'];
    ...
});
```
> 更多细节请查看[task-balancer](https://github.com/toplan/task-balancer#2-task-lifecycle)的“afterRun”钩子

### Sms::queue($enable, $handler)

该方法可以设置是否启用队列以及定义如何推送到队列。

`$handler`匿名函数可使用的参数:
+ `$sms` : Sms实例
+ `$data` : Sms实例中的短信数据，等同于`$sms->getData()`

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

//创建实例的同时设置验证码/语音文件ID
$sms = Sms::voice($code);
```

> - 如果你使用`Luosimao`语音验证码，还需用在配置文件中`Luosimao`选项中设置`voiceApikey`。
> - **语音文件ID**即是在服务商配置的语音文件的唯一编号，比如阿里大鱼[语音通知](http://open.taobao.com/doc2/apiDetail.htm?spm=a219a.7395905.0.0.oORhh9&apiId=25445)的`voice_code`。
> - **模版语音**是另一种语音请求方式，它是通过模版ID和模版数据进行的语音请求，比如阿里大鱼的[文本转语音通知](http://open.taobao.com/doc2/apiDetail.htm?spm=a219a.7395905.0.0.f04PJ3&apiId=25444)。

### $sms->to($mobile)

设置发送给谁，并返回实例。
```php
$sms->to('1828*******');
```

### $sms->template($templates)

指定代理器设置模版id或批量设置，并返回实例。
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

### $sms->data($data)

设置模板短信的模板数据，并返回实例对象，`$data`必须为数组。
```php
$sms->data([
    'code' => $code,
    'minutes' => $minutes
]);
```

> 通过`template`和`data`方法的组合除了可以实现模版短信的数据填充，还可以实现模版语音的数据填充。

### $sms->content($text)

设置内容短信的内容，并返回实例对象。一些内置的代理器(如YunPian,Luosimao)使用的是内容短信(即直接发送短信内容)，那么就需要为它们设置短信内容。
```php
$sms->content('【签名】这是短信内容...');
```

### $sms->getData([$key])

获取Sms实例中的短信数据，不带参数时返回所有数据，其结构如下：
```php
[
    'type'         => ...,
    'to'           => ...,
    'templates'    => [...],
    'content'      => ...,
    'templateData' => [...],
    'voiceCode'    => ...,
]
```

### $sms->agent($name)

临时设置发送时使用的代理器(不会影响备用代理器的正常使用)，并返回实例，`$name`为代理器名称。
```php
$sms->agent('YunPian');
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

# 高级调度配置

代理器的高级调度配置可以通过配置文件(config/phpsms.php)中的`scheme`项目配置，也可以通过`scheme`静态方法设置。
值得注意的是，高级调度配置的值的数据结构是数组。

### 指定代理器类

> 如果你自定义了一个代理器，类名不为`FooAgent`或者命名空间不为`Toplan\PhpSms`，那么你还可以在调度配置时指定你的代理器使用的类。

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

> 如果你既不想使用内置的代理器，也不想创建文件写自定义代理器，那么寄生代理器或许是个好的选择，无需定义代理器类，只需在调度配置时定义好发送短信和语音验证码的方式即可。

* 配置方式：

通过配置值中`sendSms`和`voiceVerify`键来设置发送短信和语音验证码的方式。

* 示例：
```php
Sms::scheme([
    'agentName' => [
        '20 backup',
        'sendSms' => function($agent, $to, $content, $tempId, $tempData){
            //获取配置(如果设置了的话):
            $key = $agent->key;
            ...
            //内置方法:
            Agent::sockPost(...);
            Agent::curl(...);
            ...
            //更新发送结果:
            $agent->result(Agent::SUCCESS, true);
            $agent->result(Agent::INFO, 'some info');
            $agent->result(Agent::CODE, 'your code');
        },
        'voiceVerify' => function($agent, $to, $code, $tempId, $tempData){
            //发送语音验证码，同上
        }
    ]
]);
```

# 自定义代理器

- step 1

配置项加入到config/phpsms.php中键为`agents`的数组里。
```php
//example:
'Foo' => [
    'key' => 'your api key',
    ...
]
```

- step 2

新建一个继承`Toplan\PhpSms\Agent`抽象类的代理器类，建议代理器类名为`FooAgent`，建议命名空间为`Toplan\PhpSms`。
如果类名不为`FooAgent`或者命名空间不为`Toplan\PhpSms`，在使用该代理器时则需要指定代理器类，详见[高级调度配置](#高级调度配置)。

# Change logs

### v1.4.0

该系列版本相较与之前版本在api的设计上有些变动，具体如下：

- 修改原`enable`静态方法为`scheme`

- 修改原`agents`静态方法为`config`

- 修改原`cleanEnableAgents`静态方法为`cleanScheme`

- 修改原`cleanAgentsConfig`静态方法为`cleanConfig`

- 去掉`getEnableAgents`和`getAgentsConfig`静态方法

### v1.5.0

- 改进语音信息的发送接口以适应阿里大鱼的通过文本转语音和语音文件id两个接口的需求
- 新加阿里大鱼(Alidayu)代理器

# 公告

1. 如果在使用队列相关功能时出现如下错误:

```php
Fatal error：Maximum function nesting level of ‘100′ reached, aborting!
```
可在`/etc/php5/mods-available/xdebug.ini`(Linux)中新加`xdebug.max_nesting_level=500`

# Todo list

- [ ] 可用代理器分组配置功能；短信发送时选择分组进行发送的功能。

# Encourage

hi, guys! 如果喜欢或者要收藏，欢迎star。如果要提供意见和bug，欢迎issue或提交pr。

# License

MIT
