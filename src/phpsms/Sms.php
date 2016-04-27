<?php

namespace Toplan\PhpSms;

use SuperClosure\Serializer;
use Toplan\TaskBalance\Balancer;
use Toplan\TaskBalance\Task;

/**
 * Class Sms
 *
 *
 * @author toplan<toplan710@gmail.com>
 */
class Sms
{
    /**
     * The default name of balancing task.
     *
     * @var string
     */
    const TASK = 'PhpSms';

    /**
     * The instances of class [Toplan\PhpSms\Agent].
     *
     * @var array
     */
    protected static $agents = [];

    /**
     * The enabled agents` name.
     *
     * @var array
     */
    protected static $agentsName = [];

    /**
     * The enabled agents` configuration information.
     *
     * @var array
     */
    protected static $agentsConfig = [];

    /**
     * Whether to use the queue.
     *
     * @var bool
     */
    protected static $enableQueue = false;

    /**
     * How to use the queue.
     *
     * @var \Closure
     */
    protected static $howToUseQueue = null;

    /**
     * The enable hooks for balancing task.
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
     * An instance of class [SuperClosure\Serializer] use for serialization closures.
     *
     * @var Serializer
     */
    protected static $serializer = null;

    /**
     * SMS/voice verify data container.
     *
     * @var array
     */
    protected $smsData = [
        'to'           => null,
        'templates'    => [],
        'content'      => null,
        'templateData' => [],
        'voiceCode'    => null,
    ];

    /**
     * The name of first agent.
     *
     * @var string|null
     */
    protected $firstAgent = null;

    /**
     * Whether the current instance has already pushed to the queue system.
     *
     * @var bool
     */
    protected $pushedToQueue = false;

    /**
     * Status container,
     * store some configuration information before serialize current instance(before enqueue).
     *
     * @var array
     */
    protected $_status_before_enqueue_ = [];

    /**
     * Constructor
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
     * Boot balancing task for send SMS/voice verify.
     */
    public static function bootstrap()
    {
        $task = self::getTask();

        //注意这里不能用'empty',因为其不能检查语句,
        //而恰巧Task实例获取drivers是通过魔术方法获取的.
        if (!count($task->drivers)) {
            self::configuration();
            self::createDrivers($task);
        }
    }

    /**
     * Get or generate a balancing task instance for send SMS/voice verify.
     *
     * @return Task
     */
    public static function getTask()
    {
        if (!Balancer::hasTask(self::TASK)) {
            Balancer::task(self::TASK);
        }

        return Balancer::getTask(self::TASK);
    }

    /**
     * Configuration.
     */
    protected static function configuration()
    {
        $config = [];
        if (empty(self::$agentsName)) {
            self::initEnableAgents($config);
        }
        $diff = array_diff_key(self::$agentsName, self::$agentsConfig);
        self::initAgentsConfig(array_keys($diff), $config);
        self::validateConfig();
    }

    /**
     * Try to read enable agents` name from config file.
     *
     * @param array $config
     */
    protected static function initEnableAgents(array &$config)
    {
        $config = empty($config) ? include __DIR__ . '/../config/phpsms.php' : $config;
        $enableAgents = isset($config['enable']) ? $config['enable'] : [];
        self::enable($enableAgents);
    }

    /**
     * Try to initialize the specified agents` configuration information.
     *
     * @param array $agents
     * @param array $config
     */
    protected static function initAgentsConfig(array $agents, array &$config)
    {
        if (empty($agents)) {
            return;
        }
        $config = empty($config) ? include __DIR__ . '/../config/phpsms.php' : $config;
        $agentsConfig = isset($config['agents']) ? $config['agents'] : [];
        foreach ($agents as $name) {
            $agentConfig = isset($agentsConfig[$name]) ? $agentsConfig[$name] : [];
            self::agents($name, $agentConfig);
        }
    }

    /**
     * validate configuration.
     *
     * @throws PhpSmsException
     */
    protected static function validateConfig()
    {
        if (empty(self::$agentsName)) {
            throw new PhpSmsException('Please configure at least one agent');
        }
    }

    /**
     * Create drivers for the balancing task.
     *
     * @param Task $task
     */
    protected static function createDrivers(Task $task)
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
                     $result = $agent->result();
                     if ($result['success']) {
                         $driver->success();
                     }
                     unset($result['success']);

                     return $result;
                 });
        }
    }

    /**
     * Parsing scheduling configuration.
     * 解析代理器的数组模式的调度配置
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
        $backup = self::pullAgentOptionByName($options, 'backup') ? 'backup' : '';
        $driverOpts = implode(' ', array_values($options)) . " $backup";

        return compact('agentClass', 'sendSms', 'voiceVerify', 'driverOpts');
    }

    /**
     * Pull the value of the specified option out of the scheduling configuration.
     *
     * @param array  $options
     * @param string $name
     *
     * @return mixed
     */
    protected static function pullAgentOptionByName(array &$options, $name)
    {
        if (!isset($options[$name])) {
            return;
        }
        $value = $options[$name];
        unset($options[$name]);

        return $value;
    }

    /**
     * Get agent configuration information by name.
     *
     * @param string $name
     *
     * @return array
     */
    protected static function getAgentConfigData($name)
    {
        return isset(self::$agentsConfig[$name]) ? self::$agentsConfig[$name] : [];
    }

    /**
     * Get a sms agent instance by agent name,
     * if null, will try to create a new agent instance.
     *
     * @param string $name
     * @param array  $configData
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
                throw new PhpSmsException("Do not support [$name] agent.");
            }
        }

        return self::$agents[$name];
    }

    /**
     * Set enable agents.
     *
     * @param mixed $agentName
     * @param mixed $options
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
     * Set configuration information by agent name.
     *
     * @param array|string $agentName
     * @param array        $config
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
     * Get the enabled agents` name.
     *
     * @return array
     */
    public static function getEnableAgents()
    {
        return self::$agentsName;
    }

    /**
     * Get the enabled agents` configuration information.
     *
     * @return array
     */
    public static function getAgentsConfig()
    {
        return self::$agentsConfig;
    }

    /**
     * Tear down enable agent and prepare to create and start a new balancing task,
     * so before do it must destroy old task instance.
     */
    public static function cleanEnableAgents()
    {
        Balancer::destroy(self::TASK);
        self::$agentsName = [];
    }

    /**
     * Tear down agent config and prepare to create and start a new balancing task,
     * so before do it must destroy old task instance.
     */
    public static function cleanAgentsConfig()
    {
        Balancer::destroy(self::TASK);
        self::$agentsConfig = [];
    }

    /**
     * Create a sms instance send SMS,
     * your can also set SMS templates or content at the same time.
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
     * Create a sms instance send voice verify,
     * your can also set verify code at the same time.
     *
     * @param string|int $code
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
     * Set whether to use the queue system, and define how to use it.
     *
     * @param mixed $enable
     * @param mixed $handler
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
     * Set the recipient`s mobile number.
     *
     * @param string $mobile
     *
     * @return $this
     */
    public function to($mobile)
    {
        $this->smsData['to'] = $mobile;

        return $this;
    }

    /**
     * Set the content for content SMS.
     *
     * @param string $content
     *
     * @return $this
     */
    public function content($content)
    {
        $this->smsData['content'] = trim((string) $content);

        return $this;
    }

    /**
     * Set the template id for template SMS.
     *
     * @param mixed $agentName
     * @param mixed $tempId
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
     * Set the template data for template SMS.
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
     * Set the first agent by name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function agent($name)
    {
        $this->firstAgent = (string) $name;

        return $this;
    }

    /**
     * Start send SMS/voice verify.
     *
     * If give a true parameter, this system will immediately start request to send SMS/voice verify whatever whether to use the queue.
     * if you are already pushed sms instance to the queue, you can recall the method `send()` in queue system without `true` parameter,
     * so this mechanism in order to make you convenient use the method `send()` in queue system.
     *
     * @param bool $immediately
     *
     * @return mixed
     */
    public function send($immediately = false)
    {
        if (!self::$enableQueue || $this->pushedToQueue) {
            $immediately = true;
        }
        if ($immediately) {
            $result = Balancer::run(self::TASK, [
                'data'   => $this->getData(),
                'driver' => $this->firstAgent,
            ]);
        } else {
            $result = $this->push();
        }

        return $result;
    }

    /**
     * Push to the queue by a custom method.
     *
     * @throws \Exception | PhpSmsException
     *
     * @return mixed
     */
    public function push()
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
     * Get all the data of SMS/voice verify.
     *
     * @param null|string $name
     *
     * @return mixed
     */
    public function getData($name = null)
    {
        if (is_string($name) && isset($this->smsData["$name"])) {
            return $this->smsData[$name];
        }

        return $this->smsData;
    }

    /**
     * Overload static method.
     *
     * @param string $name
     * @param array  $args
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
                $task = self::getTask();
                $task->hook($name, $handler, $override);
            } else {
                throw new PhpSmsException("Please give method static $name() a callable parameter");
            }
        } else {
            throw new PhpSmsException("Do not find static method $name()");
        }
    }

    /**
     * Overload method.
     *
     * @param string $name
     * @param array  $args
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

    /**
     * Serialize magic method,
     * store current sms instance status.
     *
     * @return array
     */
    public function __sleep()
    {
        try {
            $this->_status_before_enqueue_['enableAgents'] = self::serializeEnableAgents();
            $this->_status_before_enqueue_['agentsConfig'] = self::getAgentsConfig();
            $this->_status_before_enqueue_['handlers'] = self::serializeHandlers();
        } catch (\Exception $e) {
            //swallow exception
        }

        return ['pushedToQueue', 'smsData', 'firstAgent', '_status_before_enqueue_'];
    }

    /**
     * Unserialize magic method,
     * note: the force bootstrap must before reinstall handlers!
     */
    public function __wakeup()
    {
        if (empty($this->_status_before_enqueue_)) {
            return;
        }
        $status = $this->_status_before_enqueue_;
        self::$agentsName = self::unserializeEnableAgents($status['enableAgents']);
        self::$agentsConfig = $status['agentsConfig'];
        Balancer::destroy(self::TASK);
        self::bootstrap();
        self::reinstallHandlers($status['handlers']);
    }

    /**
     * Get a closure serializer.
     *
     * @return Serializer
     */
    public static function getSerializer()
    {
        if (!self::$serializer) {
            self::$serializer = new Serializer();
        }

        return self::$serializer;
    }

    /**
     * Serialize enabled agents.
     *
     * @return array
     */
    protected static function serializeEnableAgents()
    {
        $enableAgents = self::getEnableAgents();
        foreach ($enableAgents as $name => &$options) {
            if (is_array($options)) {
                self::serializeClosureAndReplace($options, 'sendSms');
                self::serializeClosureAndReplace($options, 'voiceVerify');
            }
        }

        return $enableAgents;
    }

    /**
     * Unserialize enabled agents.
     *
     * @param array $serialized
     *
     * @return mixed
     */
    protected static function unserializeEnableAgents(array $serialized)
    {
        foreach ($serialized as $name => &$options) {
            if (is_array($options)) {
                self::unserializeToClosureAndReplace($options, 'sendSms');
                self::unserializeToClosureAndReplace($options, 'voiceVerify');
            }
        }

        return $serialized;
    }

    /**
     * Serialize character closure value of a array and replace origin value.
     *
     * @param array  $options
     * @param string $key
     */
    protected static function serializeClosureAndReplace(array &$options, $key)
    {
        if (isset($options["$key"]) && is_callable($options["$key"])) {
            $serializer = self::getSerializer();
            $options["$key"] = (string) $serializer->serialize($options["$key"]);
        }
    }

    /**
     * Unserialize character string of a array to closure and replace origin value.
     *
     * @param array  $options
     * @param string $key
     */
    protected static function unserializeToClosureAndReplace(array &$options, $key)
    {
        if (isset($options["$key"])) {
            $serializer = self::getSerializer();
            $options["$key"] = $serializer->unserialize($options["$key"]);
        }
    }

    /**
     * Serialize these hooks` handlers:
     * 'beforeRun','beforeDriverRun','afterDriverRun','afterRun'.
     *
     * @return array
     */
    protected static function serializeHandlers()
    {
        $hooks = [];
        $serializer = self::getSerializer();
        $task = self::getTask();
        foreach ($task->handlers as $hookName => $handlers) {
            foreach ($handlers as $handler) {
                $serialized = $serializer->serialize($handler);
                if (!isset($hooks[$hookName])) {
                    $hooks[$hookName] = [];
                }
                array_push($hooks[$hookName], $serialized);
            }
        }

        return $hooks;
    }

    /**
     * Reinstall balancing task hooks` handlers by serialized handlers.
     *
     * @param array $handlers
     */
    protected static function reinstallHandlers(array $handlers)
    {
        $serializer = self::getSerializer();
        foreach ($handlers as $hookName => $serializedHandlers) {
            foreach ($serializedHandlers as $index => $handler) {
                if (is_string($handler)) {
                    $handler = $serializer->unserialize($handler);
                }
                self::$hookName($handler, $index === 0);
            }
        }
    }
}
