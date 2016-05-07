<?php

use Toplan\PhpSms\Sms;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testClean()
    {
        Sms::cleanScheme();
        $this->assertCount(0, Sms::scheme());
        Sms::cleanConfig();
        $this->assertCount(0, Sms::config());
    }

    public function testAddEnableAgent()
    {
        Sms::scheme(['Log']);
        $this->assertCount(1, Sms::scheme());

        Sms::scheme('Log', '80 backup');
        $this->assertCount(1, Sms::scheme());
        $this->assertEquals('80 backup', Sms::scheme('Log'));

        Sms::scheme('Luosimao', 'backup');
        $this->assertCount(2, Sms::scheme());

        Sms::scheme([
                'Luosimao' => '0 backup',
                'YunPian'  => '0',
            ]);
        $this->assertCount(3, Sms::scheme());
        $this->assertEquals('0', Sms::scheme('YunPian'));
    }

    public function testAddAgentConfig()
    {
        Sms::config('Log', []);
        $this->assertCount(1, Sms::config());
        $this->assertCount(0, Sms::config('Log'));

        Sms::config('Luosimao', [
                'apikey' => '123',
            ]);
        $this->assertCount(2, Sms::config());
        $this->assertArrayHasKey('apikey', Sms::config('Luosimao'));

        Sms::config([
                'Luosimao' => [
                    'apikey' => '123',
                ],
                'YunPian' => [
                    'apikey' => '123',
                ],
            ]);
        $this->assertCount(3, Sms::config());
    }

    public function testUpdateAgentConfig()
    {
        $agent = Sms::getAgent('Luosimao');
        $this->assertEquals('123', $agent->apikey);

        Sms::config('Luosimao', [
            'apikey' => '12345',
            'data'   => 'hello world',
        ]);

        $this->assertEquals('12345', $agent->apikey);
        $this->assertEquals('hello world', $agent->data);

        Sms::cleanConfig();
        $this->assertEquals(null, $agent->apikey);
        $this->assertEquals(null, $agent->data);
    }
}
