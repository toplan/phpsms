<?php

require('./../vendor/autoload.php');

use Toplan\PhpSms\Sms;

/**
 * manual set enable agents
 */
Sms::enable([
    'Log' => '3 backup',
    'Luosimao'
]);

/**
 * before send hook
 */
Sms::beforeSend(function($task, $preReturn, $index, $handlers){
    print_r("before send : $index----------<br>");
});
Sms::beforeSend(function($task, $preReturn, $index, $handlers){
    print_r("before send : $index-----<br>");
});
/**
 * after sent hook
 */
Sms::afterSend(function($task, $result, $preReturn, $index, $handlers){
    print_r("after send : $index-----<br>");
});


/**
 * print config
 */
var_dump(Sms::getEnableAgents());
//var_dump(Sms::getAgentsConfig());

/**
 * define queue
 */
//Sms::queue(function($sms, $data){
//    var_dump('pushed to queue!');
//    return 'yes';
//});
//Sms::queue(false);

print_r('<hr>');

$sms = Sms::make();
$sms->beforeSend(function($task, $preReturn, $index, $handlers){
    print_r("before send : $index-----<br>");
});

$result = $sms->make()->to('18280345...')
          ->template([
            'YunTongXun' => 21516,
            'Submail' => 11111
          ])
          ->data(['code' => '1111', 'length' => 10])
          ->send(true);
var_dump($result);

print_r('<hr>');

$result2 = Sms::voice(111)->to(18280345349)->send();
var_dump($result2);
