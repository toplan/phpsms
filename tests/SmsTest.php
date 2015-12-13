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

    public function smsData()
    {
        return $this->sms->getData();
    }

    public function testSetTo()
    {
        $this->sms->to('18280345...');
        $smsData = $this->smsData();
        $this->assertEquals('18280345...', $smsData['to']);
    }

    public function testSetTemplate()
    {
        $this->sms->template('Luosimao', '123');
        $smsData = $this->smsData();
        $this->assertEquals([
                'Luosimao' => '123'
            ], $smsData['templates']);
        $this->sms->template([
                'Luosimao' => '1234',
                'YunTongXun' => '6789',
            ]);
        $smsData = $this->smsData();
        $this->assertEquals([
            'Luosimao' => '1234',
            'YunTongXun' => '6789',
        ], $smsData['templates']);
    }

    public function testSetData()
    {
        $this->sms->data([
                'code' => '1',
                'msg' => 'msg'
            ]);
        $smsData = $this->smsData();
        $this->assertEquals([
                'code' => '1',
                'msg' => 'msg'
            ], $smsData['templateData']);
    }

    public function testSetContent()
    {
        $this->sms->content('this is content');
        $smsData = $this->smsData();
        $this->assertEquals('this is content', $smsData['content']);
    }

    public function testSendSms()
    {
    }
}
