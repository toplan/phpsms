<?php

use Toplan\PhpSms\Sms;
class SmsTest extends PHPUnit_Framework_TestCase
{
    protected $sms = null;

    public function setup()
    {
        $this->sms = Sms::make();
    }

    public function testMakeSms()
    {
        $this->assertInstanceOf('Toplan\PhpSms\Sms', $this->sms);
    }

}
