<?php

require('./../vendor/autoload.php');

use Toplan\PhpSms\Sms;

/**
 * before send hook
 */
Sms::beforeSend(function($task){
});

/**
 * after sent hook
 */
Sms::afterSend(function($task, $results){
});

/**
 * manual set enable agents
 */
Sms::enable([
    'Log' => '3 backup',
    'Luosimao'
]);

/**
 * print config
 */
//var_dump(Sms::getEnableAgents());
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

$result = Sms::make([
                    'YunTongXun' => 21516
                ])
                ->to('18280345...')
                ->data(['code' => '1111', 'length' => 10])
                ->send(true);
var_dump($result);

print_r('<hr>');

$result2 = Sms::voice(111)->to(18280345349)->send();
var_dump($result2);
