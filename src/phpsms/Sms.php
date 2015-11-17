<?php
namespace Toplan\PhpSms;

use Toplan\TaskBalance\Balancer;

/**
 * Class Sms
 * @package Toplan\PhpSms
 */
class Sms
{
    /**
     * sms send task name
     */
    const TASK = 'PhpSms';

    /**
     * log agent`s name
     */
    const LOG_AGENT = 'Log';

    /**
     * agents instance
     */
    protected static $agents;

    /**
     * agents`s name
     * @var
     */
    protected static $agentsName = [];

    /**
     * agents`s config
     * @var
     */
    protected static $agentsConfig = [];

    /**
     * queue work
     * @var null
     */
    protected static $howToUseQueue = null;

    /**
     * hook handlers
     * @var array
     */
    protected static $hookHandlers = [
        "beforeRun" => null,
        "afterRun"  => null,
    ];

    /**
     * sms data
     * @var array
     */
    protected $smsData = [
        'to' => null,
        'templates' => [],
        'content' => '',
        'templateData' => [],
        'voiceCode' => null,
    ];

    /**
     * construct
     */
    public function __construct()
    {
        self::init();
    }

    /**
     * create sms instance and set templates
     * @param      $agentName
     * @param null $tempId
     *
     * @return Sms
     */
    public static function make($agentName, $tempId = null)
    {
        $sms = new self;
        $sms->template($agentName, $tempId);
        return $sms;
    }

    /**
     * send voice verify
     * @param $code
     *
     * @return Sms
     */
    public static function voice($code)
    {
        $sms = new self;
        $sms->smsData['voiceCode'] = $code;
        return $sms;
    }

    /**
     * set how to use queue.
     * @param $handler
     *
     * @throws \Exception
     */
    public static function queue($handler)
    {
        if (is_callable($handler)) {
            self::$howToUseQueue = $handler;
        } else {
            throw new \Exception('Please give static method `queue()` a callable argument');
        }
    }

    /**
     * set the mobile number
     * @param $mobile
     *
     * @return $this
     */
    public function to($mobile)
    {
        if (is_array($mobile)) {
            $mobile = implode(',', $mobile);
        }
        $this->smsData['to'] = $mobile;
        return $this;
    }

    /**
     * set content for content sms
     * @param $content
     *
     * @return $this
     */
    public function content($content)
    {
        $this->smsData['content'] = (String) $content;
        return $this;
    }

    /**
     * set template id for template sms
     * @param $agentName
     * @param $tempId
     *
     * @return $this
     */
    public function template($agentName, $tempId = null)
    {
        $tempIdArray = (Array) $this->smsData['templates'];
        if ( ! is_null($tempId)) {
            $tempIdArray["$agentName"] = $tempId;
        } else {
            if (is_array($agentName)) {
                $tempIdArray = $agentName;
            } else {
                $firstAgentName = self::getFirstAgentName();
                $tempIdArray["$firstAgentName"] = $agentName;
            }
        }
        $this->smsData['templates'] = (Array) $tempIdArray;
        return $this;
    }

    /**
     * set data for template sms
     * @param array $data
     *
     * @return $this
     */
    public function data(Array $data)
    {
        $this->smsData['templateData'] = $data;
        return $this;
    }

    /**
     * start send
     * @return mixed
     * @throws \Exception
     */
    public function send()
    {
        $this->validator();
        $results = Balancer::run(self::TASK, $this->getData());
        return $results;
    }

    /**
     * push sms send task to queue
     * @return mixed
     * @throws \Exception
     */
    public function push()
    {
        if (is_callable(self::$howToUseQueue)) {
            return call_user_func(self::$howToUseQueue, $this->smsData);
        } else {
            throw new \Exception('Please define how to use queue by static method `queue($handler)`');
        }
    }

    /**
     * get data:
     * if this is a voice verify, will gt voice data.
     * if this is a sms, will get sms data.
     * @return array
     */
    public function getData()
    {
        return $this->smsData;
    }

    /**
     * get first agent`s name
     * @return int|null|string
     */
    public static function getFirstAgentName()
    {
        foreach (self::$agentsName as $name => $options) {
            return $name;
        }
    }

    /**
     * init
     */
    protected static function init()
    {
        self::generatorTask();
    }

    /**
     * generator a sms send task
     * @return null
     */
    public static function generatorTask()
    {
        if (!Balancer::getTask(self::TASK)) {
            Balancer::task(self::TASK, function($task){
                // create drivers
                self::createAgents($task);
                // set hooks handler
                foreach (self::$hookHandlers as $hook => $handler) {
                    if (is_callable($handler)) {
                        $task->hook($hook, $handler);
                    }
                }
            });
        }
        return Balancer::getTask(self::TASK);
    }

    /**
     * read agents` name from config file
     * @return mixed
     * @throws \Exception
     */
    protected static function getAgentsName()
    {
        if (!self::$agentsName) {
            $config = include(__DIR__ . '/config/phpsms.php');
            if (isset($config['agents'])) {
                if (!count($config['agents'])) {
                    throw new \Exception('please set one agent in config file(phpsms.php) at least');
                }
                self::$agentsName = $config['agents'];
            } else {
                throw new \Exception('please set agents value in config file(phpsms.php)');
            }
        }
        return self::$agentsName;
    }

    /**
     * read agents` config form config file
     * @return mixed
     * @throws \Exception
     */
    protected static function getAgentsConfig()
    {
        if (!self::$agentsConfig) {
            $config = include(__DIR__ . '/config/agents.php');
            $enableAgentsName = self::getAgentsName();
            $config[self::LOG_AGENT] = [];//default config for log agent.
            foreach ($enableAgentsName as $agentName => $options) {
                if (!isset($config[$agentName])) {
                    throw new \Exception("please configuration $agentName agent in config file(agents.php)");
                }
            }
            self::$agentsConfig = $config;
        }
        return self::$agentsConfig;
    }

    /**
     * create drivers for sms send task
     * @param $task
     *
     * @throws \Exception
     */
    protected static function createAgents($task)
    {
        $agentsName = self::getAgentsName();
        $agentsConfig = self::getAgentsConfig();
        foreach ($agentsName as $name => $options) {
            $configData = (Array) $agentsConfig[$name];
            $task->driver("$name $options")
                 ->data($configData)
                 ->work(function($driver, $data){
                     $configData = $driver->getDriverData();
                     $agent = self::getSmsAgent($driver->name, $configData);
                     $smsData = $driver->getTaskData();
                     extract($smsData);
                     if (isset($smsData['voiceCode']) && $smsData['voiceCode']) {
                         $result = $agent->voiceVerify($to, $voiceCode);
                     } else {
                         $template = isset($templates[$driver->name]) ? $templates[$driver->name] : 0;
                         $result = $agent->sms($template, $to, $templateData, $content);
                     }
                     if ($result['success']) {
                         $driver->success();
                     }
                     unset($result['success']);
                     return $result;
                 });
        }
    }

    /**
     * get a sms agent instance,
     * if null, will create a new agent instance
     * @param       $name
     * @param array $configData
     *
     * @return mixed
     */
    protected static function getSmsAgent($name, Array $configData)
    {
        if (!isset(self::$agents[$name])) {
            $className = 'Toplan\\PhpSms\\' . $name . 'Agent';
            if (class_exists($className)) {
                self::$agents[$name] = new $className($configData);
            } else {
                throw new \InvalidArgumentException("Agent [$name] not support.");
            }
        }
        return self::$agents[$name];
    }

    /**
     * validate
     * @throws \Exception
     */
    protected function validator()
    {
        if (!$this->smsData['to']) {
            throw new \Exception("please set sms or voice verify to who use to() method.");
        }
        return true;
    }

    /**
     * set available agents
     * @param      $agents
     * @param null $options
     */
    public static function agents($agents, $options = null)
    {
        if ($options && !is_array($agents)) {
            self::$agentsName[$agents] = $options;
        } elseif (is_array($agents)) {
            self::$agentsName = array_merge(self::$agentsName, $agents);
        }
    }

    /**
     * set config for available agents
     * @param      $configs
     * @param null $options
     */
    public static function config($configs, $options = null)
    {
        if ($options && !is_array($configs)) {
            self::$agentsConfig[$configs] = $options;
        } elseif (is_array($configs)) {
            self::$agentsConfig = array_merge(self::$agentsConfig, $configs);
        }
    }

    /**
     * overload static method
     * @param $name
     * @param $args
     *
     * @throws \Exception
     */
    public static function __callStatic($name, $args) {
        $name = $name == 'beforeSend' ? 'beforeRun' : $name;
        $name = $name == 'afterSend' ? 'afterRun' : $name;
        if (array_key_exists($name, self::$hookHandlers)) {
            $handler = $args[0];
            if ($handler && is_callable($handler)) {
                self::$hookHandlers[$name] = $handler;
            } else {
                throw new \Exception("Please give static method $name() a callable argument");
            }
        } else {
            throw new \Exception("Do not find static method $name()");
        }
    }
}
