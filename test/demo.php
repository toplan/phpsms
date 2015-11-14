<?php

require('./../vendor/autoload.php');

use Toplan\PhpSms\Sms;

$result = Sms::make([
                    'YunTongXun' => 21516
                ])
                ->to('18280345...')
                ->data(['code' => '1111', 'length' => 10])
                ->send();
var_dump($result);
print_r('<hr>');

$result2 = Sms::voice(123)->to(18280345349)->send();
var_dump($result2);
