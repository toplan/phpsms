<?php

use Toplan\PhpSms\Sms;
class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testAddEnableAgent()
    {
        Sms::enable('Log');
        $this->assertCount(1, Sms::getEnableAgents());
        Sms::enable('Log', '30 backup');
        $this->assertCount(1, Sms::getEnableAgents());
        Sms::enable('Luosimao', '20 backup');
        $this->assertCount(2, Sms::getEnableAgents());
        Sms::enable([
                'Luosimao' => '20 backup',
                'YunPian' => '10 backup',
            ]);
        $this->assertCount(3, Sms::getEnableAgents());
    }

    public function testAddAgentConfig()
    {
        Sms::agents('Log', []);
        $this->assertCount(1, Sms::getAgentsConfig());
        Sms::agents('Luosimao', [
                'apikey' => '123'
            ]);
        $this->assertCount(2, Sms::getAgentsConfig());
        Sms::agents([
                'Luosimao' => [
                    'apikey' => '123',
                ],
                'YunPian' => [
                    'apikey' => '123',
                ]
            ]);
        $this->assertCount(3, Sms::getAgentsConfig());
    }
}
