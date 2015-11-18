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
     * @param null $agentName
     * @param null $tempId
     *
     * @return Sms
     */
    public static function make($agentName = null, $tempId = null)
    {
        $sms = new self;
        if ($agentName) {
            $sms->template($agentName, $tempId);
        }
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
            throw new \Exception('Please give method `queue()` a callable parameter');
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
            return call_user_func_array(self::$howToUseQueue, [$this, $this->smsData]);
        } else {
            throw new \Exception('Please define how to use queue by method `queue($handler)`');
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
        self::configuration();
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
     * configuration
     * @throws \Exception
     */
    protected static function configuration()
    {
        if (!self::$agentsName) {
            $config = include(__DIR__ . '/config/phpsms.php');
            self::generatorAgentsName($config);
        }
        if (!self::$agentsConfig) {
            $config = include(__DIR__ . '/config/phpsms.php');
            self::generatorAgentsConfig($config);
        }
        self::configValidator();
    }

    /**
     * generate enabled agents name
     * @param array $config
     *
     * @throws \Exception
     */
    protected static function generatorAgentsName($config)
    {
        $config = isset($config['enable']) ? $config['enable'] : null;
        if ($config) {
            self::enable($config);
        }
    }

    /**
     * generator agents config
     * @param array $config
     *
     * @throws \Exception
     */
    protected static function generatorAgentsConfig($config)
    {
        $config = isset($config['agents']) ? $config['agents'] : [];
        self::agents($config);
    }

    /**
     * config value validator
     * @throws \Exception
     */
    protected static function configValidator()
    {
        if (!count(self::$agentsName)) {
            throw new \Exception('Please set at least one enable agent in config file(config/phpsms.php) or use method enable()');
        }
        foreach (self::$agentsName as $agentName => $options) {
            if ($agentName == self::LOG_AGENT) {
                continue;
            }
            if (!isset(self::$agentsConfig[$agentName])) {
                throw new \Exception("Please configuration [$agentName] agent in config file(config/phpsms.php) or use method agents()");
            }
        }
    }

    /**
     * create drivers for sms send task
     * @param $task
     *
     * @throws \Exception
     */
    protected static function createAgents($task)
    {
        foreach (self::$agentsName as $name => $options) {
            $configData = self::getAgentConfigData($name);
            $task->driver("$name $options")
                 ->data($configData)
                 ->work(function($driver, $data){
                     $configData = $driver->getDriverData();
                     $agent = self::getSmsAgent($driver->name, $configData);
                     $smsData = $driver->getTaskData();
                     extract($smsData);
                     if (isset($smsData['voiceCode']) && $smsData['voiceCode']) {
                         $agent->voiceVerify($to, $voiceCode);
                     } else {
                         $template = isset($templates[$driver->name]) ? $templates[$driver->name] : 0;
                         $agent->sendSms($template, $to, $templateData, $content);
                     }
                     $result = $agent->getResult();
                     if ($result['success']) {
                         $driver->success();
                     }
                     unset($result['success']);
                     return $result;
                 });
        }
    }

    /**
     * get agent config data by name
     * @param $name
     *
     * @return array
     */
    protected static function getAgentConfigData($name)
    {
        return isset(self::$agentsConfig[$name]) ?
               (Array) self::$agentsConfig[$name] : [];
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
     * set enable agents
     * @param      $agentName
     * @param null $options
     */
    public static function enable($agentName, $options = null)
    {
        if (is_string($agentName) && $options) {
            self::$agentsName[$agentName] = $options;
        } elseif (is_array($agentName)) {
            foreach ($agentName as $name => $opt) {
                self::enable($name, $opt);
            }
        }
    }

    /**
     * set config for available agents
     * @param      $agentName
     * @param Array $config
     */
    public static function agents($agentName, Array $config = [])
    {
        if (is_string($agentName) && is_array($config)){
            self::$agentsConfig[$agentName] = $config;
        } elseif (is_array($agentName)) {
            foreach ($agentName as $name => $conf) {
                self::agents($name, $conf);
            }
        }
    }

    /**
     * get enable agents
     * @return array
     */
    public static function getAgents()
    {
        return self::$agentsName;
    }

    /**
     * get agents config info
     * @return array
     */
    public static function getConfig()
    {
        return self::$agentsConfig;
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
                throw new \Exception("Please give static method $name() a callable parameter");
            }
        } else {
            throw new \Exception("Do not find static method $name()");
        }
    }
}
