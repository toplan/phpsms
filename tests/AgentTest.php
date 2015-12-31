<?php

use Toplan\PhpSms\LogAgent;
use Toplan\PhpSms\ParasiticAgent;

class AgentTest extends PHPUnit_Framework_TestCase
{
    protected $agent = null;

    public function setUp()
    {
        $config = [
            'key'  => 'value',
            'key2' => 'value2',
        ];
        $this->agent = new LogAgent($config);
    }

    public function testResultOriginValue()
    {
        $r = $this->agent->getResult();
        $this->assertArrayHasKey('success', $r);
        $this->assertArrayHasKey('info', $r);
        $this->assertArrayHasKey('code', $r);
    }

    public function testSetAndGetResult()
    {
        $this->agent->result('success', true);
        $this->agent->result('info', 'info');
        $this->agent->result('code', 'code');

        $r = $this->agent->getResult();
        $code = $this->agent->getResult('code');
        $null = $this->agent->getResult('undefined');

        $this->assertTrue($r['success']);
        $this->assertEquals('info', $r['info']);
        $this->assertEquals('code', $r['code']);
        $this->assertEquals('code', $code);
        $this->assertNull($null);
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

    public function testParasitic()
    {
        $parasiticAgent = new ParasiticAgent([
            'sendSms' => function ($agent, $tempId, $to, $tempData, $content) {
                $agent->result('info', 'parasitic_sms');
                $agent->result('code', $to);
            },
            'voiceVerify' => function ($agent, $to, $code) {
                $agent->result('info', 'parasitic_voice_verify');
                $agent->result('code', $code);
            },
        ]);
        $parasiticAgent->sendSms('template id', '18280111111', [], 'content');
        $this->assertEquals('parasitic_sms', $parasiticAgent->getResult('info'));
        $this->assertEquals('18280111111', $parasiticAgent->getResult('code'));

        $parasiticAgent->voiceVerify('18280111111', '2222');
        $this->assertEquals('parasitic_voice_verify', $parasiticAgent->getResult('info'));
        $this->assertEquals('2222', $parasiticAgent->getResult('code'));
    }
}
