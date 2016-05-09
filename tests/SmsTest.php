<?php

use Toplan\PhpSms\Sms;

class SmsTest extends PHPUnit_Framework_TestCase
{
    protected static $sms = null;

    public static function setUpBeforeClass()
    {
        Sms::cleanScheme();
        Sms::scheme([
            'Log'      => '10',
            'Luosimao' => '0',
        ]);
        self::$sms = Sms::make();
    }

    public function testMakeSms()
    {
        $this->assertInstanceOf('Toplan\PhpSms\Sms', self::$sms);
    }

    public function testHasAgent()
    {
        $this->assertFalse(Sms::hasAgent('Log'));
        $this->assertFalse(Sms::hasAgent('SomeAgent'));

        Sms::getAgent('Log');
        $this->assertTrue(Sms::hasAgent('Log'));
    }

    public function testGetAgent()
    {
        $agent = Sms::getAgent('Log');
        $this->assertInstanceOf('Toplan\PhpSms\LogAgent', $agent);
        $luosimao = Sms::getAgent('Luosimao');
        $this->assertInstanceOf('Toplan\PhpSms\LuosimaoAgent', $luosimao);
    }

    public function testGetTask()
    {
        $task = Sms::getTask();
        $this->assertInstanceOf('Toplan\TaskBalance\Task', $task);
    }

    public function testGetSmsData()
    {
        $data = self::$sms->getData();
        $this->assertArrayHasKey('to', $data);
        $this->assertArrayHasKey('templates', $data);
        $this->assertArrayHasKey('templateData', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertArrayHasKey('voiceCode', $data);
        self::$sms->to('...');
        $this->assertEquals('...', self::$sms->getData('to'));
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

    public function testBeforeSend()
    {
        Sms::beforeSend(function () {
            print_r('before_');
        });
        $this->expectOutputString('before_');
        self::$sms->send();
    }

    public function testAfterSend()
    {
        self::$sms->afterSend(function () {
            print_r('after');
        });
        $this->expectOutputString('before_after');
        self::$sms->send();
    }

    public function testSetAgent()
    {
        $result = self::$sms->agent('Log')->send();
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['logs']);
        $this->assertEquals('Log', $result['logs'][0]['driver']);
    }

    public function testVoice()
    {
        $sms = Sms::voice('code');
        $data = $sms->getData();
        $this->assertEquals('code', $data['voiceCode']);
    }

    public function testUseQueue()
    {
        $status = Sms::queue();
        $this->assertFalse($status);

        //define how to use queue
        //way 1
        Sms::queue(function ($sms, $data) {
            return 'in_queue_1';
        });
        $this->assertTrue(Sms::queue());

        //define how to use queue
        //way 2
        Sms::queue(false, function ($sms, $data) {
            return 'in_queue_2';
        });
        $this->assertFalse(Sms::queue());

        //open queue
        Sms::queue(true);
        $this->assertTrue(Sms::queue());

        //push sms to queue
        $result = self::$sms->send();
        $this->assertEquals('in_queue_2', $result);

        //force send
        $result = self::$sms->send(true);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['logs']);
        $this->assertEquals('Log', $result['logs'][0]['driver']);
    }
}
