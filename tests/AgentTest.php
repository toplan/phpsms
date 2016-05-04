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

    public function testSendVoiceVerify()
    {
        $this->agent->voiceVerify('18280111111', '1111', 0, []);
        $r = $this->agent->result();
        $this->assertTrue($r['success']);
    }

    public function testParasitic()
    {
        $parasiticAgent = new ParasiticAgent([
            'sendSms' => function ($agent, $to, $content, $tempId, $tempData) {
                $agent->result('info', 'parasitic_sms');
                $agent->result('code', $to);
            },
            'voiceVerify' => function ($agent, $to, $code, $tempId, $tempData) {
                $agent->result('info', 'parasitic_voice_verify');
                $agent->result('code', $code);
            },
        ]);
        $parasiticAgent->sendSms('18280111111', 'content', 'template_id', []);
        $this->assertEquals('parasitic_sms', $parasiticAgent->result('info'));
        $this->assertEquals('18280111111', $parasiticAgent->result('code'));

        $parasiticAgent->voiceVerify('18280111111', '2222', 'template_id', []);
        $this->assertEquals('parasitic_voice_verify', $parasiticAgent->result('info'));
        $this->assertEquals('2222', $parasiticAgent->result('code'));
    }
}
