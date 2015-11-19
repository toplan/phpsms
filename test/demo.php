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
//Sms::enable([
//    'Log' => '1 backup',
//    'Luosimao' => '3 backup'
//]);

/**
 * print config
 */
//var_dump(Sms::getAgents());
//var_dump(Sms::getConfig());

/**
 * define queue
 */
//Sms::queue(function(){
//    var_dump('pushed to queue!');
//    return 'yes';
//});
//Sms::queue(true);

print_r('<hr>');

$result = Sms::make([
                    'YunTongXun' => 21516
                ])
                ->to('18280345...')
                ->data(['code' => '1111', 'length' => 10])
                ->send(true);
var_dump($result);

print_r('<hr>');

$sms = new Sms();
$result2 = $sms->voice(111)->to(18280345349)->send();
var_dump($result2);

