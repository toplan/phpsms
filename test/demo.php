<?php

require('./../vendor/autoload.php');

use Toplan\PhpSms\Sms;
Sms::beforeSend(function($task){
});
Sms::afterSend(function($task, $results){
});
$result = Sms::make([
                    'YunTongXun' => 21516
                ])
                ->to('18280345...')
                ->data(['code' => '1111', 'length' => 10])
                ->send();
var_dump($result);
print_r('<hr>');

$sms = new Sms();
$result2 = $sms->voice(111)->to(18280345349)->send();
var_dump($result2);
