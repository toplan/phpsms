<?php

use Toplan\PhpSms\Sms;

class SmsTest extends PHPUnit_Framework_TestCase
{
    protected static $sms = null;

    public static function setUpBeforeClass()
    {
        self::$sms = Sms::make();
    }

    public function testMakeSms()
    {
        $this->assertInstanceOf('Toplan\PhpSms\Sms', self::$sms);
    }

    public function smsData()
    {
        return self::$sms->getData();
    }

    public function testSetTo()
    {
        self::$sms->to('18280345...');
        $smsData = $this->smsData();
        $this->assertEquals('18280345...', $smsData['to']);
    }

    public function testSetTemplate()
    {
        self::$sms->template('Luosimao', '123');
        $smsData = $this->smsData();
        $this->assertEquals([
                'Luosimao' => '123',
            ], $smsData['templates']);
        self::$sms->template([
                'Luosimao'   => '1234',
                'YunTongXun' => '6789',
            ]);
        $smsData = $this->smsData();
        $this->assertEquals([
            'Luosimao'   => '1234',
            'YunTongXun' => '6789',
        ], $smsData['templates']);
    }

    public function testSetData()
    {
        self::$sms->data([
                'code' => '1',
                'msg'  => 'msg',
            ]);
        $smsData = $this->smsData();
        $this->assertEquals([
                'code' => '1',
                'msg'  => 'msg',
            ], $smsData['templateData']);
    }

    public function testSetContent()
    {
        self::$sms->content('this is content');
        $smsData = $this->smsData();
        $this->assertEquals('this is content', $smsData['content']);
    }

    public function testSendSms()
    {
        $result = self::$sms->send();
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('time', $result);
        $this->assertArrayHasKey('logs', $result);
    }

    public function testSetAgent()
    {
        $result = self::$sms->agent('Log')->send();
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['logs']);
        $this->assertEquals('Log', $result['logs'][0]['driver']);
    }
}
