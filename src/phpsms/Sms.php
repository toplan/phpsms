<?php

namespace Toplan\PhpSms;

use Toplan\TaskBalance\Balancer;

/**
 * Class Sms
 */
class Sms
{
    /**
     * sms send task name
     */
    const TASK = 'PhpSms';

    /**
     * agents instance
     */
    protected static $agents;

    /**
     * agents`s name
     *
     * @var
     */
    protected static $agentsName = [];

    /**
     * agents`s config
     *
     * @var
     */
    protected static $agentsConfig = [];

    /**
     * whether to enable queue
     *
     * @var bool
     */
    protected static $enableQueue = false;

    /**
     * queue work
     *
     * @var \Closure
     */
    protected static $howToUseQueue = null;

    /**
     * sms already pushed to queue
     *
     * @var bool
     */
    protected $pushedToQueue = false;

    /**
     * hook handlers
     *
     * @var array
     */
    protected static $enableHooks = [
        'beforeRun',
        'beforeDriverRun',
        'afterDriverRun',
        'afterRun',
    ];

    /**
     * sms data
     *
     * @var array
     */
    protected $smsData = [
        'to'           => null,
        'templates'    => [],
        'content'      => '',
        'templateData' => [],
        'voiceCode'    => null,
    ];

    /**
     * first agent for send sms/voice verify
     *
     * @var string
     */
    protected $firstAgent = null;

    /**
     * construct
     *
     * @param bool $autoBoot
     */
    public function __construct($autoBoot = true)
    {
        if ($autoBoot) {
            self::bootstrap();
        }
    }

    /**
     * create sms instance and set templates
     *
     * @param mixed $agentName
     * @param mixed $tempId
     *
     * @return Sms
     */
    public static function make($agentName = null, $tempId = null)
    {
        $sms = new self();
        if (is_array($agentName)) {
            $sms->template($agentName);
        } elseif ($agentName && is_string($agentName)) {
            if ($tempId === null) {
                $sms->content($agentName);
            } elseif (is_string("$tempId")) {
                $sms->template($agentName, $tempId);
            }
        }

        return $sms;
    }

    /**
     * send voice verify
     *
     * @param $code
     *
     * @return Sms
     */
    public static function voice($code)
    {
        $sms = new self();
        $sms->smsData['voiceCode'] = $code;

        return $sms;
    }

    /**
     * set how to use queue.
     *
     * @param $enable
     * @param $handler
     *
     * @return bool
     */
    public static function queue($enable = null, $handler = null)
    {
        if ($enable === null && $handler === null) {
            return self::$enableQueue;
        }
        if (is_callable($enable)) {
            $handler = $enable;
            $enable = true;
        }
        self::$enableQueue = (bool) $enable;
        if (is_callable($handler)) {
            self::$howToUseQueue = $handler;
        }

        return self::$enableQueue;
    }

    /**
     * set the mobile number
     *
     * @param $mobile
     *
     * @return $this
     */
    public function to($mobile)
    {
        $this->smsData['to'] = $mobile;

        return $this;
    }

    /**
     * set content for content sms
     *
     * @param $content
     *
     * @return $this
     */
    public function content($content)
    {
        $this->smsData['content'] = trim((string) $content);

        return $this;
    }

    /**
     * set template id for template sms
     *
     * @param $agentName
     * @param $tempId
     *
     * @return $this
     */
    public function template($agentName, $tempId = null)
    {
        if (is_array($agentName)) {
            foreach ($agentName as $k => $v) {
                $this->template($k, $v);
            }
        } elseif ($agentName && $tempId) {
            if (!isset($this->smsData['templates']) || !is_array($this->smsData['templates'])) {
                $this->smsData['templates'] = [];
            }
            $this->smsData['templates']["$agentName"] = $tempId;
        }

        return $this;
    }

    /**
     * set data for template sms
     *
     * @param array $data
     *
     * @return $this
     */
    public function data(array $data)
    {
        $this->smsData['templateData'] = $data;

        return $this;
    }

    /**
     * set the first agent
     *
     * @param $name
     *
     * @return $this
     */
    public function agent($name)
    {
        $this->firstAgent = (string) $name;

        return $this;
    }

    /**
     * start send
     *
     * @param bool $immediately
     *
     * @return mixed
     */
    public function send($immediately = false)
    {
        $this->validator();

        // if disable push to queue,
        // send the sms immediately.
        if (!self::$enableQueue) {
            $immediately = true;
        }

        // whatever 'PhpSms' whether to enable or disable push to queue,
        // if you are already pushed sms instance to queue,
        // you can recall the method `send()` in queue job without `true` parameter.
        //
        // So this mechanism in order to make you convenient use the method `send()` in queue system.
        if ($this->pushedToQueue) {
            $immediately = true;
        }

        // whether to send sms immediately,
        // or push it to queue.
        if ($immediately) {
            $result = Balancer::run(self::TASK, [
                'data'  => $this->getData(),
                'agent' => $this->firstAgent,
            ]);
        } else {
            $result = $this->push();
        }

        return $result;
    }

    /**
     * push sms send task to queue
     *
     * @throws \Exception | PhpSmsException
     *
     * @return mixed
     */
    protected function push()
    {
        if (is_callable(self::$howToUseQueue)) {
            try {
                $this->pushedToQueue = true;

                return call_user_func_array(self::$howToUseQueue, [$this, $this->smsData]);
            } catch (\Exception $e) {
                $this->pushedToQueue = false;
                throw $e;
            }
        } else {
            throw new PhpSmsException('Please define how to use queue by method `queue($enable, $handler)`');
        }
    }

    /**
     * get sms data
     *
     * @return array
     */
    public function getData()
    {
        return $this->smsData;
    }

    /**
     * bootstrap
     */
    public static function bootstrap()
    {
        $task = self::generatorTask();
        if (!count($task->drivers)) {
            self::configuration();
            self::createDrivers($task);
        }
    }

    /**
     * generator a sms send task
     *
     * @return object
     */
    public static function generatorTask()
    {
        if (!Balancer::hasTask(self::TASK)) {
            Balancer::task(self::TASK);
        }

        return Balancer::getTask(self::TASK);
    }

    /**
     * configuration
     */
    protected static function configuration()
    {
        $config = [];
        self::generatorAgentsName($config);
        self::generatorAgentsConfig($config);
        self::configValidator();
    }

    /**
     * generate enabled agents name
     *
     * @param array $config
     */
    protected static function generatorAgentsName(&$config)
    {
        if (empty(self::$agentsName)) {
            $config = $config ?: include __DIR__ . '/../config/phpsms.php';
            $enableAgents = isset($config['enable']) ? $config['enable'] : null;
            self::enable($enableAgents);
        }
    }

    /**
     * generator agents config
     *
     * @param array $config
     */
    protected static function generatorAgentsConfig(&$config)
    {
        $diff = array_diff_key(self::$agentsName, self::$agentsConfig);
        $diff = array_keys($diff);
        if (count($diff)) {
            $config = $config ?: include __DIR__ . '/../config/phpsms.php';
            $agentsConfig = isset($config['agents']) ? $config['agents'] : [];
            foreach ($diff as $name) {
                $agentConfig = isset($agentsConfig[$name]) ? $agentsConfig[$name] : [];
                self::agents($name, $agentConfig);
            }
        }
    }

    /**
     * config value validator
     *
     * @throws PhpSmsException
     */
    protected static function configValidator()
    {
        if (!count(self::$agentsName)) {
            throw new PhpSmsException('Please set at least one enable agent in config file(config/phpsms.php) or use method enable()');
        }
    }

    /**
     * create drivers for sms send task
     *
     * @param $task
     */
    protected static function createDrivers($task)
    {
        foreach (self::$agentsName as $name => $options) {
            //获取代理器配置
            $configData = self::getAgentConfigData($name);
            //解析代理器数组模式的调度配置
            if (is_array($options)) {
                $data = self::parseAgentArrayOptions($options);
                $configData = array_merge($configData, $data);
                $options = $data['driverOpts'];
            }
            //创建任务驱动器
            $task->driver("$name $options")->data($configData)
                 ->work(function ($driver) {
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
     * 解析可用代理器的数组模式的调度配置
     *
     * @param array $options
     *
     * @return array
     */
    protected static function parseAgentArrayOptions(array $options)
    {
        $agentClass = self::pullAgentOptionByName($options, 'agentClass');
        $sendSms = self::pullAgentOptionByName($options, 'sendSms');
        $voiceVerify = self::pullAgentOptionByName($options, 'voiceVerify');
        $backup = self::pullAgentOptionByName($options, 'backup');
        $driverOpts = implode(' ', array_values($options)) . " $backup";

        return compact('agentClass', 'sendSms', 'voiceVerify', 'driverOpts');
    }

    /**
     * 从调度配置中拉取指定数据
     *
     * @param array  $options
     * @param string $name
     *
     * @return null|string
     */
    protected static function pullAgentOptionByName(array &$options, $name)
    {
        $value = isset($options[$name]) ? $options[$name] : null;
        if ($name === 'backup') {
            $value = isset($options[$name]) ? ($options[$name] ? 'backup' : '') : '';
        }
        unset($options[$name]);

        return $value;
    }

    /**
     * get agent config data by name
     *
     * @param $name
     *
     * @return array
     */
    protected static function getAgentConfigData($name)
    {
        return isset(self::$agentsConfig[$name]) ?
               (array) self::$agentsConfig[$name] : [];
    }

    /**
     * get a sms agent instance,
     * if null, will create a new agent instance
     *
     * @param       $name
     * @param array $configData
     *
     * @throws PhpSmsException
     *
     * @return mixed
     */
    public static function getSmsAgent($name, array $configData)
    {
        if (!isset(self::$agents[$name])) {
            $configData['name'] = $name;
            $className = isset($configData['agentClass']) ? $configData['agentClass'] : ('Toplan\\PhpSms\\' . $name . 'Agent');
            if ((isset($configData['sendSms']) && is_callable($configData['sendSms'])) ||
                (isset($configData['voiceVerify']) && is_callable($configData['voiceVerify']))) {
                //创建寄生代理器
                $configData['agentClass'] = '';
                self::$agents[$name] = new ParasiticAgent($configData);
            } elseif (class_exists($className)) {
                //创建新代理器
                self::$agents[$name] = new $className($configData);
            } else {
                //无代理器可用
                throw new PhpSmsException("Dose not support [$name] agent.");
            }
        }

        return self::$agents[$name];
    }

    /**
     * validate
     *
     * @throws PhpSmsException
     */
    protected function validator()
    {
        if (!$this->smsData['to']) {
            throw new PhpSmsException('Please set send sms(or voice verify) to who use `to()` method.');
        }

        return true;
    }

    /**
     * set enable agents
     *
     * @param      $agentName
     * @param null $options
     */
    public static function enable($agentName, $options = null)
    {
        if (is_array($agentName)) {
            foreach ($agentName as $name => $opt) {
                self::enable($name, $opt);
            }
        } elseif ($agentName && is_string($agentName) && $options !== null) {
            self::$agentsName["$agentName"] = is_array($options) ? $options : "$options";
        } elseif (is_int($agentName) && !is_array($options) && "$options") {
            self::$agentsName["$options"] = '1';
        } elseif ($agentName && $options === null) {
            self::$agentsName["$agentName"] = '1';
        }
    }

    /**
     * set config for available agents
     *
     * @param       $agentName
     * @param array $config
     *
     * @throws PhpSmsException
     */
    public static function agents($agentName, array $config = [])
    {
        if (is_array($agentName)) {
            foreach ($agentName as $name => $conf) {
                self::agents($name, $conf);
            }
        } elseif ($agentName && is_array($config)) {
            if (preg_match('/^[0-9]+$/', $agentName)) {
                throw new PhpSmsException("Agent name [$agentName] must be string, could not be a pure digital");
            }
            self::$agentsConfig["$agentName"] = $config;
        }
    }

    /**
     * get enable agents
     *
     * @return array
     */
    public static function getEnableAgents()
    {
        return self::$agentsName;
    }

    /**
     * get agents config info
     *
     * @return array
     */
    public static function getAgentsConfig()
    {
        return self::$agentsConfig;
    }

    /**
     * tear down enable agents
     */
    public static function cleanEnableAgents()
    {
        self::$agentsName = [];
    }

    /**
     * tear down agents config
     */
    public static function cleanAgentsConfig()
    {
        self::$agentsConfig = [];
    }

    /**
     * overload static method
     *
     * @param $name
     * @param $args
     *
     * @throws PhpSmsException
     */
    public static function __callStatic($name, $args)
    {
        $name = $name === 'beforeSend' ? 'beforeRun' : $name;
        $name = $name === 'afterSend' ? 'afterRun' : $name;
        $name = $name === 'beforeAgentSend' ? 'beforeDriverRun' : $name;
        $name = $name === 'afterAgentSend' ? 'afterDriverRun' : $name;
        if (in_array($name, self::$enableHooks)) {
            $handler = $args[0];
            $override = isset($args[1]) ? (bool) $args[1] : false;
            if (is_callable($handler)) {
                $task = self::generatorTask();
                $task->hook($name, $handler, $override);
            } else {
                throw new PhpSmsException("Please give method static $name() a callable parameter");
            }
        } else {
            throw new PhpSmsException("Do not find static method $name()");
        }
    }

    /**
     * overload method
     *
     * @param $name
     * @param $args
     *
     * @throws PhpSmsException
     * @throws \Exception
     */
    public function __call($name, $args)
    {
        try {
            $this->__callStatic($name, $args);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
