<?php

namespace Toplan\PhpSms;

/**
 * Class ParasiticAgent
 * 寄生代理器
 *
 * @property string   $name
 * @property \Closure $sendSms
 * @property \Closure $voiceVerify
 */
class ParasiticAgent extends Agent
{
    protected $sendSmsRunning = false;

    protected $voiceVerifyRunning = false;

    public function sendSms($tempId, $to, array $tempData, $content)
    {
        if (!is_callable($this->sendSms)) {
            throw new PhpSmsException("Please give parasitic agent [$this->name] a callable param named `sendSms` by enable config.");
        }
        if (!$this->sendSmsRunning) {
            $this->sendSmsRunning = true;
            try {
                call_user_func_array($this->sendSms, [$this, $tempId, $to, $tempData, $content]);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                $this->sendSmsRunning = false;
            }
        } else {
            throw new PhpSmsException('Please do not use `$agent->sendSms()` in closure.');
        }
    }

    public function sendContentSms($to, $content)
    {
        throw new PhpSmsException('Parasitic agent does not support `sendContentSms` method.');
    }

    public function sendTemplateSms($tempId, $to, array $tempData)
    {
        throw new PhpSmsException('Parasitic agent does not support `sendTemplateSms` method.');
    }

    public function voiceVerify($to, $code)
    {
        if (!is_callable($this->voiceVerify)) {
            throw new PhpSmsException("Please give parasitic agent [$this->name] a callable param named `voiceVerify` by enable config.");
        }
        if (!$this->voiceVerifyRunning) {
            $this->voiceVerifyRunning = true;
            try {
                call_user_func_array($this->voiceVerify, [$this, $to, $code]);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                $this->voiceVerifyRunning = false;
            }
        } else {
            throw new PhpSmsException('Please do not use `$agent->voiceVerify()` in closure.');
        }
    }
}
