<?php

namespace Toplan\PhpSms;

use SuperClosure\Serializer;
use Toplan\TaskBalance\Balancer;
use Toplan\TaskBalance\Task;

/**
 * Class Sms
 *
 * @author toplan<toplan710@gmail.com>
 */
class Sms
{
    const TASK_NAME = 'PhpSms';
    const TYPE_SMS = 1;
    const TYPE_VOICE = 2;

    /**
     * The instances of Agent.
     *
     * @var array
     */
    protected static $agents = [];

    /**
     * The dispatch scheme of agent,
     * and these agents are available.
     * example:
     * [
     *   'Agent1' => '10 backup',
     *   'Agent2' => '20 backup',
     * ]
     *
     * @var array
     */
    protected static $scheme = [];

    /**
     * The configuration information of agents.
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
     * The available hooks for balancing task.
     *
     * @var array
     */
    protected static $availableHooks = [
        'beforeRun',
        'beforeDriverRun',
        'afterDriverRun',
        'afterRun',
    ];

    /**
     * An instance of class [SuperClosure\Serializer] for serialize closure objects.
     *
     * @var Serializer
     */
    protected static $serializer = null;

    /**
     * The data container of SMS/voice verify.
     *
     * @var array
     */
    protected $smsData = [
        'type'         => self::TYPE_SMS,
        'to'           => null,
        'templates'    => [],
        'templateData' => [],
        'content'      => null,
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
        if (!self::taskInitialized()) {
            self::configuration();
            self::initTask();
        }
    }

    /**
     * Whether task initialized.
     *
     * Note: 判断drivers是否为空不能用'empty',因为在TaskBalance库的中Task类的drivers属性是受保护的(不可访问),
     * 虽然通过魔术方法可以获取到其值,但在其目前版本(v0.4.2)其内部却并没有使用'__isset'魔术方法对'empty'或'isset'函数进行逻辑补救.
     *
     * @return bool
     */
    protected static function taskInitialized()
    {
        $task = self::getTask();

        return (bool) count($task->drivers);
    }

    /**
     * Get or generate a balancing task instance for send SMS/voice verify.
     *
     * @return Task
     */
    public static function getTask()
    {
        if (!Balancer::hasTask(self::TASK_NAME)) {
            Balancer::task(self::TASK_NAME);
        }

        return Balancer::getTask(self::TASK_NAME);
    }

    /**
     * Configuration.
     */
    protected static function configuration()
    {
        $config = [];
        if (!count(self::scheme())) {
            self::initScheme($config);
        }
        $diff = array_diff_key(self::scheme(), self::$agentsConfig);
        self::initAgentsConfig(array_keys($diff), $config);
        self::validateConfig();
    }

    /**
     * Try to read the dispatch scheme of agent from config file.
     *
     * @param array $config
     */
    protected static function initScheme(array &$config)
    {
        $config = empty($config) ? include __DIR__ . '/../config/phpsms.php' : $config;
        $scheme = isset($config['scheme']) ? $config['scheme'] : [];
        self::scheme($scheme);
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
            self::config($name, $agentConfig);
        }
    }

    /**
     * validate configuration.
     *
     * @throws PhpSmsException
     */
    protected static function validateConfig()
    {
        if (!count(self::scheme())) {
            throw new PhpSmsException('Please configure at least one agent');
        }
    }

    /**
     * Initialize the task.
     */
    protected static function initTask()
    {
        foreach (self::scheme() as $name => $scheme) {
            //解析代理器数组模式的调度配置
            if (is_array($scheme)) {
                $data = self::parseScheme($scheme);
                $scheme = $data['scheme'];
            }
            //创建任务驱动器
            self::getTask()->driver("$name $scheme")->work(function ($driver) {
                $agent = self::getAgent($driver->name);
                $smsData = $driver->getTaskData();
                extract($smsData);
                $template = isset($templates[$driver->name]) ? $templates[$driver->name] : 0;
                if ($type === self::TYPE_VOICE) {
                    $agent->voiceVerify($to, $voiceCode, $template, $templateData);
                } elseif ($type === self::TYPE_SMS) {
                    $agent->sendSms($to, $content, $template, $templateData);
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
     * Parsing the dispatch scheme.
     * 解析代理器的数组模式的调度配置
     *
     * @param array $options
     *
     * @return array
     */
    protected static function parseScheme(array $options)
    {
        $agentClass = Util::pullFromArrayByKey($options, 'agentClass');
        $sendSms = Util::pullFromArrayByKey($options, 'sendSms');
        $voiceVerify = Util::pullFromArrayByKey($options, 'voiceVerify');
        $backup = Util::pullFromArrayByKey($options, 'backup') ? 'backup' : '';
        $scheme = implode(' ', array_values($options)) . " $backup";

        return compact('agentClass', 'sendSms', 'voiceVerify', 'scheme');
    }

    /**
     * Get a sms agent instance by agent name,
     * if null, will try to create a new agent instance.
     *
     * @param string $name
     *
     * @throws PhpSmsException
     *
     * @return mixed
     */
    public static function getAgent($name)
    {
        if (!self::hasAgent($name)) {
            $scheme = self::scheme($name);
            $data = self::parseScheme(is_array($scheme) ? $scheme : [$scheme]);
            $data = array_merge(self::config($name), $data);
            $className = $data['agentClass'] ?: ('Toplan\\PhpSms\\' . $name . 'Agent');
            if (is_callable($data['sendSms']) || is_callable($data['voiceVerify'])) {
                self::$agents[$name] = new ParasiticAgent($data);
            } elseif (class_exists($className)) {
                self::$agents[$name] = new $className($data);
            } else {
                throw new PhpSmsException("Dont support [$name] agent.");
            }
        }

        return self::$agents[$name];
    }

    /**
     * Whether to has specified agent.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function hasAgent($name)
    {
        return isset(self::$agents[$name]);
    }

    /**
     * Set or get the dispatch scheme of agent by name.
     *
     * @param mixed $name
     * @param mixed $scheme
     *
     * @return mixed
     */
    public static function scheme($name = null, $scheme = null)
    {
        return Util::operateArray(self::$scheme, $name, $scheme, null, function ($key, $value) {
            if (is_string($key)) {
                self::modifyScheme($key, is_array($value) ? $value : "$value");
            } elseif (is_int($key)) {
                self::modifyScheme($value, '');
            }
        });
    }

    /**
     * Modify the dispatch scheme of agent by name.
     *
     * @param $key
     * @param $value
     *
     * @throws PhpSmsException
     */
    protected static function modifyScheme($key, $value)
    {
        if (self::taskInitialized()) {
            throw new PhpSmsException("Modify the dispatch scheme failed for [$key] agent, because the task system has already started.");
        }
        self::validateAgentName($key);
        self::$scheme[$key] = $value;
    }

    /**
     * Set or get configuration information by agent name.
     *
     * @param mixed $name
     * @param mixed $config
     * @param bool  $override
     *
     * @throws PhpSmsException
     *
     * @return array
     */
    public static function config($name = null, $config = null, $override = false)
    {
        if (is_array($name) && is_bool($config)) {
            $override = $config;
        }

        return Util::operateArray(self::$agentsConfig, $name, $config, [], function ($key, $value) {
            if (is_array($value)) {
                self::modifyConfig($key, $value);
            }
        }, $override, function (array $origin) {
            $nameList = array_keys($origin);
            foreach ($nameList as $name) {
                if (self::hasAgent("$name")) {
                    self::getAgent("$name")->config([], true);
                }
            }
        });
    }

    /**
     * Modify the configuration information of agent by name.
     *
     * @param string $key
     * @param array  $value
     *
     * @throws PhpSmsException
     */
    protected static function modifyConfig($key, array $value)
    {
        self::validateAgentName($key);
        self::$agentsConfig[$key] = $value;
        if (self::hasAgent($key)) {
            self::getAgent($key)->config($value);
        }
    }

    /**
     * Validate the agent name.
     * Agent name must be a string, but not be a number string
     *
     * @param string $name
     *
     * @throws PhpSmsException
     */
    protected static function validateAgentName($name)
    {
        if (!$name || !is_string($name) || preg_match('/^[0-9]+$/', $name)) {
            throw new PhpSmsException("The agent name [$name] is illegal. Agent name must be a string, but not be a number string.");
        }
    }

    /**
     * Tear down agent use scheme and prepare to create and start a new balancing task,
     * so before do it must destroy old task instance.
     */
    public static function cleanScheme()
    {
        Balancer::destroy(self::TASK_NAME);
        self::$scheme = [];
    }

    /**
     * Tear down all the configuration information of agent.
     */
    public static function cleanConfig()
    {
        self::config([], true);
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
        $sms->smsData['type'] = self::TYPE_SMS;
        if (is_array($agentName)) {
            $sms->template($agentName);
        } elseif ($agentName && is_string($agentName)) {
            if ($tempId === null) {
                $sms->content($agentName);
            } elseif (is_string($tempId) || is_int($tempId)) {
                $sms->template($agentName, "$tempId");
            }
        }

        return $sms;
    }

    /**
     * Create a sms instance send voice verify,
     * your can also set verify code at the same time.
     *
     * @param int|string|null $code
     *
     * @return Sms
     */
    public static function voice($code = null)
    {
        $sms = new self();
        $sms->smsData['type'] = self::TYPE_VOICE;
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
        $this->smsData['to'] = trim((string) $mobile);

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
     * @param mixed $name
     * @param mixed $tempId
     *
     * @return $this
     */
    public function template($name, $tempId = null)
    {
        Util::operateArray($this->smsData['templates'], $name, $tempId);

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
            $result = Balancer::run(self::TASK_NAME, [
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

                return call_user_func_array(self::$howToUseQueue, [$this, $this->getData()]);
            } catch (\Exception $e) {
                $this->pushedToQueue = false;
                throw $e;
            }
        } else {
            throw new PhpSmsException('Please define how to use queue by this static method: queue(...)');
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
        if (in_array($name, self::$availableHooks)) {
            $handler = $args[0];
            $override = isset($args[1]) ? (bool) $args[1] : false;
            if (is_callable($handler)) {
                $task = self::getTask();
                $task->hook($name, $handler, $override);
            } else {
                throw new PhpSmsException("Please give method $name() a callable parameter");
            }
        } else {
            throw new PhpSmsException("Dont find method $name()");
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
     * Serialize magic method.
     *
     * @return array
     */
    public function __sleep()
    {
        try {
            $this->_status_before_enqueue_['scheme'] = self::serializeOrDeserializeScheme(self::scheme());
            $this->_status_before_enqueue_['agentsConfig'] = self::config();
            $this->_status_before_enqueue_['handlers'] = self::serializeHandlers();
        } catch (\Exception $e) {
            //swallow exception
        }

        return ['smsData', 'firstAgent', 'pushedToQueue', '_status_before_enqueue_'];
    }

    /**
     * Deserialize magic method.
     */
    public function __wakeup()
    {
        if (empty($this->_status_before_enqueue_)) {
            return;
        }
        $status = $this->_status_before_enqueue_;
        self::$scheme = self::serializeOrDeserializeScheme($status['scheme']);
        self::$agentsConfig = $status['agentsConfig'];
        Balancer::destroy(self::TASK_NAME);
        self::bootstrap();
        self::reinstallHandlers($status['handlers']);
    }

    /**
     * Get a closure serializer.
     *
     * @return Serializer
     */
    protected static function getSerializer()
    {
        if (!self::$serializer) {
            self::$serializer = new Serializer();
        }

        return self::$serializer;
    }

    /**
     * Serialize or deserialize the agent use scheme.
     *
     * @param array $scheme
     *
     * @return array
     */
    protected static function serializeOrDeserializeScheme(array $scheme)
    {
        foreach ($scheme as $name => &$options) {
            if (is_array($options)) {
                self::serializeOrDeserializeClosureAndReplace($options, 'sendSms');
                self::serializeOrDeserializeClosureAndReplace($options, 'voiceVerify');
            }
        }

        return $scheme;
    }

    /**
     * Serialize the hooks` handlers of balancing task
     *
     * @return array
     */
    protected static function serializeHandlers()
    {
        $task = self::getTask();
        $hooks = (array) $task->handlers;
        foreach ($hooks as &$handlers) {
            foreach (array_keys($handlers) as $key) {
                self::serializeOrDeserializeClosureAndReplace($handlers, $key);
            }
        }

        return $hooks;
    }

    /**
     * Reinstall hooks` handlers for balancing task.
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

    /**
     * Serialize/deserialize the specified closure and replace the origin value.
     *
     * @param array      $options
     * @param int|string $key
     */
    protected static function serializeOrDeserializeClosureAndReplace(array &$options, $key)
    {
        if (!isset($options[$key])) {
            return;
        }
        $serializer = self::getSerializer();
        if (is_callable($options[$key])) {
            $options[$key] = (string) $serializer->serialize($options[$key]);
        } elseif (is_string($options[$key])) {
            $options[$key] = $serializer->unserialize($options[$key]);
        }
    }
}
