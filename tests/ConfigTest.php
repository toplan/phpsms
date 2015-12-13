<?php

use Toplan\PhpSms\Sms;
class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testAddEnableAgent()
    {
        Sms::enable('Log');
        $this->assertEquals(1, count(Sms::getEnableAgents()));
        Sms::enable('Log', '30 backup');
        $this->assertEquals(1, count(Sms::getEnableAgents()));
        Sms::enable('Luosimao', '20 backup');
        $this->assertEquals(2, count(Sms::getEnableAgents()));
        Sms::enable([
                'Luosimao' => '20 backup',
                'YunPian' => '10 backup',
            ]);
        $this->assertEquals(3, count(Sms::getEnableAgents()));
    }

    public function testAddAgentConfig()
    {
        Sms::agents('Log', []);
        $this->assertEquals(1, count(Sms::getAgentsConfig()));
        Sms::agents('Luosimao', [
                'apikey' => '123'
            ]);
        $this->assertEquals(2, count(Sms::getAgentsConfig()));
        Sms::agents([
                'Luosimao' => [
                    'apikey' => '123',
                ],
                'YunPian' => [
                    'apikey' => '123',
                ]
            ]);
        $this->assertEquals(3, count(Sms::getAgentsConfig()));
    }
}
