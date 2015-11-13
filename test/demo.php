<?php

require('./../vendor/autoload.php');

use Toplan\PhpSms\Sms;

$result = Sms::make(123123)->content('欢迎使用phpsms')->to(1828035349)->send();
var_dump($result);