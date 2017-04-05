<?php

namespace Toplan\PhpSms;

/**
 * Class ParasiticAgent
 * 寄生代理器
 *
 * @property \Closure $sendSms
 * @property \Closure $voiceVerify
 */
class ParasiticAgent extends Agent
{
    protected $sendSmsRunning = false;

    protected $voiceVerifyRunning = false;

    public function sendSms($to, $content, $tempId, array $data)
    {
        if (!is_callable($this->sendSms)) {
            throw new PhpSmsException('Expected the higher-order scheme option `sendSms` to be a closure.');
        }
        if ($this->sendSmsRunning) {
            throw new PhpSmsException('Do not call `$agent->sendSms()` in the closure.');
        }
        $this->sendSmsRunning = true;
        try {
            call_user_func_array($this->sendSms, [$this, $to, $content, $tempId, $data]);
            $this->sendSmsRunning = false;
        } catch (\Exception $e) {
            $this->sendSmsRunning = false;

            throw $e;
        }
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        if (!is_callable($this->voiceVerify)) {
            throw new PhpSmsException('Expected the higher-order scheme option `voiceVerify` to be a closure.');
        }
        if ($this->voiceVerifyRunning) {
            throw new PhpSmsException('Do not call `$agent->voiceVerify()` in the closure.');
        }
        $this->voiceVerifyRunning = true;
        try {
            call_user_func_array($this->voiceVerify, [$this, $to, $code, $tempId, $data]);
            $this->voiceVerifyRunning = false;
        } catch (\Exception $e) {
            $this->voiceVerifyRunning = false;

            throw $e;
        }
    }

    public function sendContentSms($to, $content)
    {
        throw new PhpSmsException('Parasitic agent does not support `sendContentSms` method.');
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
        throw new PhpSmsException('Parasitic agent does not support `sendTemplateSms` method.');
    }
}
