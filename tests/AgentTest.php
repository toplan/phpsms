<?php

use Toplan\PhpSms\LogAgent;

class AgentTest extends PHPUnit_Framework_TestCase
{
    protected $agent = null;

    public function setUp()
    {
        $config = [
            'key'  => 'value',
            'key2' => 'value2'
        ];
        $this->agent = new LogAgent($config);
    }
    public function testResult()
    {
        $r = $this->agent->getResult();
        $this->assertArrayHasKey('success', $r);
        $this->assertArrayHasKey('info', $r);
        $this->assertArrayHasKey('code', $r);
    }

    public function testGetConfig()
    {
        //get config value
        $value = $this->agent->__get('key');
        $value2 = $this->agent->key2;
        $this->assertEquals('value', $value);
        $this->assertEquals('value2', $value2);
        //get not define
        $value = $this->agent->notdefine;
        $this->assertNull($value);
    }

    public function testSendTemplateSms()
    {
        $this->agent->sendSms('template id', '18280111111', [], null);
        $r = $this->agent->getResult();
        $this->assertTrue($r['success']);
        $this->assertEquals('send template sms success', $r['info']);
    }

    public function testSendContentSms()
    {
        $this->agent->sendSms('template id', '18280111111', [], 'content');
        $r = $this->agent->getResult();
        $this->assertTrue($r['success']);
        $this->assertEquals('send content sms success', $r['info']);
    }

    public function testSendVoiceVerify()
    {
        $this->agent->voiceVerify('18280111111', '1111');
        $r = $this->agent->getResult();
        $this->assertTrue($r['success']);
    }
}