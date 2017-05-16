<?php

namespace Toplan\PhpSms;

/**
 * Class ParasiticAgent
 * 寄生代理器
 */
class ParasiticAgent extends Agent implements ContentSms, TemplateSms, VoiceCode, ContentVoice, TemplateVoice, FileVoice
{
    protected static $methods;

    protected $handlers = [];

    public function __construct(array $config = [], array $handlers = [])
    {
        parent::__construct($config);

        $this->handlers = $handlers;
    }

    /**
     * Content SMS send process.
     *
     * @param string|array $to
     * @param string       $content
     */
    public function sendContentSms($to, $content)
    {
        $this->handle(__FUNCTION__, func_get_args());
    }

    /**
     * Content voice send process.
     *
     * @param string|array $to
     * @param string       $content
     */
    public function sendContentVoice($to, $content)
    {
        $this->handle(__FUNCTION__, func_get_args());
    }

    /**
     * File voice send process.
     *
     * @param string|array $to
     * @param int|string   $fileId
     */
    public function sendFileVoice($to, $fileId)
    {
        $this->handle(__FUNCTION__, func_get_args());
    }

    /**
     * Template SMS send process.
     *
     * @param string|array $to
     * @param int|string   $tempId
     * @param array        $tempData
     */
    public function sendTemplateSms($to, $tempId, array $tempData)
    {
        $this->handle(__FUNCTION__, func_get_args());
    }

    /**
     * Template voice send process.
     *
     * @param string|array $to
     * @param int|string   $tempId
     * @param array        $tempData
     */
    public function sendTemplateVoice($to, $tempId, array $tempData)
    {
        $this->handle(__FUNCTION__, func_get_args());
    }

    /**
     * Voice code send process.
     *
     * @param string|array $to
     * @param int|string   $code
     */
    public function sendVoiceCode($to, $code)
    {
        $this->handle(__FUNCTION__, func_get_args());
    }

    /**
     * Handle send process by closure.
     *
     * @param       $name
     * @param array $args
     */
    protected function handle($name, array $args = [])
    {
        if (isset($this->handlers[$name]) && is_callable($this->handlers[$name])) {
            array_unshift($args, $this);
            call_user_func_array($this->handlers[$name], $args);
        }
    }

    /**
     * Get methods name which inherit from interfaces.
     *
     * @return array
     */
    public static function methods()
    {
        if (!is_array(self::$methods)) {
            self::$methods = [];
            $interfaces = class_implements('Toplan\\PhpSms\\ParasiticAgent');
            foreach ($interfaces as $interface) {
                self::$methods = array_merge(self::$methods, get_class_methods($interface));
            }
        }

        return self::$methods;
    }
}
