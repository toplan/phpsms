<?php

use Toplan\PhpSms\Agent;
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
        $r = $this->agent->result();
        $this->assertArrayHasKey('success', $r);
        $this->assertArrayHasKey('info', $r);
        $this->assertArrayHasKey('code', $r);
    }

    public function testSetAndGetResult()
    {
        $this->agent->result('success', true);
        $this->agent->result('info', 'info');
        $this->agent->result('code', 'code');

        $r = $this->agent->result();
        $code = $this->agent->result('code');
        $null = $this->agent->result('undefined');

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
        $this->assertFalse(isset($this->agent->notdefile));
        $this->assertTrue(empty($this->agent->notdefile));
    }

    public function testSendTemplateSms()
    {
        $this->agent->sendSms('18280111111', null, 'template_id', []);
        $r = $this->agent->result();
        $this->assertTrue($r['success']);
        $this->assertEquals('send template sms success', $r['info']);
    }

    public function testSendContentSms()
    {
        $this->agent->sendSms('18280111111', 'content', 0, []);
        $r = $this->agent->result();
        $this->assertTrue($r['success']);
        $this->assertEquals('send content sms success', $r['info']);
    }

    public function testSendVoice()
    {
        $this->agent->sendVoice('18280111111', null, 0, [], '1111');
        $r = $this->agent->result();
        $this->assertTrue($r['success']);
    }

    public function testParasitic()
    {
        $parasiticAgent = new ParasiticAgent([], [
            'sendContentSms' => function ($agent, $to, $content) {
                $agent->result(Agent::INFO, $content);
                $agent->result(Agent::CODE, $to);
                $agent->result(Agent::SUCCESS, true);
            },
            'sendVoiceCode' => function ($agent, $to, $code) {
                $agent->result(Agent::INFO, 'parasitic_voice_verify');
                $agent->result(Agent::CODE, $code);
            },
        ]);
        $parasiticAgent->sendSms('18280111111', 'parasitic_sms_content');
        $this->assertEquals('parasitic_sms_content', $parasiticAgent->result('info'));
        $this->assertEquals('18280111111', $parasiticAgent->result('code'));

        $parasiticAgent->sendVoice('18280111111', null, null, [], '2222');
        $this->assertEquals('parasitic_voice_verify', $parasiticAgent->result('info'));
        $this->assertEquals('2222', $parasiticAgent->result('code'));
    }
}
