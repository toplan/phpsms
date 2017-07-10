<?php

namespace Toplan\PhpSms;

use Toplan\TaskBalance\Driver;
use Toplan\TaskBalance\Task;

/**
 * Class Sms
 *
 * @author toplan<toplan710@gmail.com>
 */
class Sms
{
    const TYPE_SMS = 1;
    const TYPE_VOICE = 2;

    /**
     * Task instance.
     *
     * @var Task
     */
    protected static $task = null;

    /**
     * Agent instances.
     *
     * @var Agent[]
     */
    protected static $agents = [];

    /**
     * Dispatch scheme of agents.
     *
     * @var array
     */
    protected static $scheme = [];

    /**
     * Configuration information of agents.
     *
     * @var array
     */
    protected static $agentsConfig = [];

    /**
     * Whether to use the queue system.
     *
     * @var bool
     */
    protected static $enableQueue = false;

    /**
     * How to use the queue system.
     *
     * @var \Closure
     */
    protected static $howToUseQueue = null;

    /**
     * Available hooks.
     *
     * @var string[]
     */
    protected static $availableHooks = [
        'beforeRun',
        'beforeDriverRun',
        'afterDriverRun',
        'afterRun',
    ];

    /**
     * Data container.
     *
     * @var array
     */
    protected $smsData = [
        'type'      => self::TYPE_SMS,
        'to'        => null,
        'templates' => [],
        'data'      => [],
        'content'   => null,
        'code'      => null,
        'files'     => [],
        'params'    => [],
    ];

    /**
     * The name of first agent.
     *
     * @var string
     */
    protected $firstAgent = null;

    /**
     * Whether pushed to the queue system.
     *
     * @var bool
     */
    protected $pushedToQueue = false;

    /**
     * State container.
     *
     * @var array
     */
    protected $state = [];

    /**
     * Constructor
     *
     * @param bool $autoBoot
     */
    public function __construct($autoBoot = true)
    {
        if ($autoBoot) {
            self::bootTask();
        }
    }

    /**
     * Bootstrap the task.
     */
    public static function bootTask()
    {
        if (!self::isTaskBooted()) {
            self::configure();
            foreach (self::scheme() as $name => $scheme) {
                self::registerDriver($name, $scheme);
            }
        }
    }

    /**
     * Is task has been booted.
     *
     * @return bool
     */
    protected static function isTaskBooted()
    {
        return !empty(self::getTask()->drivers);
    }

    /**
     * Get the task instance.
     *
     * @return Task
     */
    public static function getTask()
    {
        if (empty(self::$task)) {
            self::$task = new Task();
        }

        return self::$task;
    }

    /**
     * Configure.
     *
     * @throws PhpSmsException
     */
    protected static function configure()
    {
        $config = [];
        if (!count(self::scheme())) {
            self::initScheme($config);
        }
        $diff = array_diff_key(self::scheme(), self::$agentsConfig);
        self::initAgentsConfig(array_keys($diff), $config);
        if (!count(self::scheme())) {
            throw new PhpSmsException('Expected at least one agent in scheme.');
        }
    }

    /**
     * Initialize the dispatch scheme.
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
     * Initialize the configuration information.
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
     * register driver.
     *
     * @param string       $name
     * @param string|array $scheme
     */
    protected static function registerDriver($name, $scheme)
    {
        // parse higher-order scheme
        $settings = [];
        if (is_array($scheme)) {
            $settings = self::parseScheme($scheme);
            $scheme = $settings['scheme'];
        }
        // register
        self::getTask()->driver("$name $scheme")->work(function (Driver $driver) use ($settings) {
            $agent = self::getAgent($driver->name, $settings);
            extract($driver->getTaskData());
            $template = isset($templates[$driver->name]) ? $templates[$driver->name] : null;
            $file = isset($files[$driver->name]) ? $files[$driver->name] : null;
            $params = isset($params[$driver->name]) ? $params[$driver->name] : [];
            if ($type === self::TYPE_VOICE) {
                $agent->sendVoice($to, $content, $template, $data, $code, $file, $params);
            } elseif ($type === self::TYPE_SMS) {
                $agent->sendSms($to, $content, $template, $data, $params);
            }
            $result = $agent->result();
            if ($result['success']) {
                $driver->success();
            }
            unset($result['success']);

            return $result;
        });
    }

    /**
     * Parse the higher-order dispatch scheme.
     *
     * @param array $options
     *
     * @return array
     */
    protected static function parseScheme(array $options)
    {
        $weight = Util::pullFromArray($options, 'weight');
        $backup = Util::pullFromArray($options, 'backup') ? 'backup' : '';
        $props = array_filter(array_values($options), function ($prop) {
            return is_numeric($prop) || is_string($prop);
        });

        $options['scheme'] = implode(' ', $props) . " $weight $backup";

        return $options;
    }

    /**
     * Get the agent instance by name.
     *
     * @param string $name
     * @param array  $options
     *
     * @throws PhpSmsException
     *
     * @return Agent
     */
    public static function getAgent($name, array $options = [])
    {
        if (!self::hasAgent($name)) {
            $scheme = self::scheme($name);
            $config = self::config($name);
            if (is_array($scheme) && empty($options)) {
                $options = self::parseScheme($scheme);
            }
            if (isset($options['scheme'])) {
                unset($options['scheme']);
            }
            $className = "Toplan\\PhpSms\\{$name}Agent";
            if (isset($options['agentClass'])) {
                $className = $options['agentClass'];
                unset($options['agentClass']);
            }
            if (!empty($options)) {
                self::$agents[$name] = new ParasiticAgent($config, $options);
            } elseif (class_exists($className)) {
                self::$agents[$name] = new $className($config);
            } else {
                throw new PhpSmsException("Not support agent `$name`.");
            }
        }

        return self::$agents[$name];
    }

    /**
     * Whether has the specified agent.
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
     * Set or get the dispatch scheme.
     *
     * @param string|array|null      $name
     * @param string|array|bool|null $scheme
     * @param bool                   $override
     *
     * @return mixed
     */
    public static function scheme($name = null, $scheme = null, $override = false)
    {
        if (is_array($name) && is_bool($scheme)) {
            $override = $scheme;
        }

        return Util::operateArray(self::$scheme, $name, $scheme, null, function ($key, $value) {
            if (is_string($key)) {
                self::modifyScheme($key, is_array($value) ? $value : "$value");
            } elseif (is_int($key)) {
                self::modifyScheme($value, '');
            }
        }, $override, function (array $origin) {
            if (self::isTaskBooted()) {
                foreach (array_keys($origin) as $name) {
                    self::getTask()->removeDriver($name);
                }
            }
        });
    }

    /**
     * Modify the dispatch scheme of agent.
     *
     * @param string       $name
     * @param string|array $scheme
     *
     * @throws PhpSmsException
     */
    protected static function modifyScheme($name, $scheme)
    {
        self::validateAgentName($name);
        self::$scheme[$name] = $scheme;
        if (self::isTaskBooted()) {
            $driver = self::getTask()->getDriver($name);
            if ($driver) {
                if (is_array($scheme)) {
                    $higherOrderScheme = self::parseScheme($scheme);
                    $scheme = $higherOrderScheme['scheme'];
                }
                $driver->reset($scheme);
            } else {
                self::registerDriver($name, $scheme);
            }
        }
    }

    /**
     * Set or get the configuration information.
     *
     * @param string|array|null $name
     * @param array|bool|null   $config
     * @param bool              $override
     *
     * @throws PhpSmsException
     *
     * @return array
     */
    public static function config($name = null, $config = null, $override = false)
    {
        $overrideAll = (is_array($name) && is_bool($config)) ? $config : false;

        return Util::operateArray(self::$agentsConfig, $name, $config, [], function ($name, array $config) use ($override) {
            self::modifyConfig($name, $config, $override);
        }, $overrideAll, function (array $origin) {
            foreach (array_keys($origin) as $name) {
                if (self::hasAgent("$name")) {
                    self::getAgent("$name")->config([], true);
                }
            }
        });
    }

    /**
     * Modify the configuration information.
     *
     * @param string $name
     * @param array  $config
     * @param bool   $override
     *
     * @throws PhpSmsException
     */
    protected static function modifyConfig($name, array $config, $override = false)
    {
        self::validateAgentName($name);
        if (!isset(self::$agentsConfig[$name])) {
            self::$agentsConfig[$name] = [];
        }
        $target = &self::$agentsConfig[$name];
        if ($override) {
            $target = $config;
        } else {
            $target = array_merge($target, $config);
        }
        if (self::hasAgent($name)) {
            self::getAgent($name)->config($target);
        }
    }

    /**
     * Validate the name of agent.
     *
     * @param string $name
     *
     * @throws PhpSmsException
     */
    protected static function validateAgentName($name)
    {
        if (empty($name) || !is_string($name) || preg_match('/^[0-9]+$/', $name)) {
            throw new PhpSmsException('Expected the name of agent to be a string which except the digital string.');
        }
    }

    /**
     * Tear down scheme.
     */
    public static function cleanScheme()
    {
        self::scheme([], true);
    }

    /**
     * Tear down config information.
     */
    public static function cleanConfig()
    {
        self::config([], true);
    }

    /**
     * Create a instance for send sms.
     *
     * @param mixed $agentName
     * @param mixed $tempId
     *
     * @return Sms
     */
    public static function make($agentName = null, $tempId = null)
    {
        $sms = new self();
        $sms->type(self::TYPE_SMS);
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
     * Create a instance for send voice.
     *
     * @param int|string|null $code
     *
     * @return Sms
     */
    public static function voice($code = null)
    {
        $sms = new self();
        $sms->type(self::TYPE_VOICE);
        $sms->code($code);

        return $sms;
    }

    /**
     * Set whether to use the queue system,
     * and define how to use it.
     *
     * @param bool|\Closure|null $enable
     * @param \Closure|null      $handler
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
     * Set the type of Sms instance.
     *
     * @param $type
     *
     * @throws PhpSmsException
     *
     * @return $this
     */
    public function type($type)
    {
        if ($type !== self::TYPE_SMS && $type !== self::TYPE_VOICE) {
            throw new PhpSmsException('Expected the parameter equals to `Sms::TYPE_SMS` or `Sms::TYPE_VOICE`.');
        }
        $this->smsData['type'] = $type;

        return $this;
    }

    /**
     * Set the recipient`s mobile number.
     *
     * @param string|array $mobile
     *
     * @return $this
     */
    public function to($mobile)
    {
        if (is_string($mobile)) {
            $mobile = trim($mobile);
        }
        $this->smsData['to'] = $mobile;

        return $this;
    }

    /**
     * Set the sms content.
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
     * Set the template ids.
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
     * Set the template data.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function data($key, $value = null)
    {
        Util::operateArray($this->smsData['data'], $key, $value);

        return $this;
    }

    /**
     * Set the voice code.
     *
     * @param string|int $code
     *
     * @return $this
     */
    public function code($code)
    {
        $this->smsData['code'] = $code;

        return $this;
    }

    /**
     * Set voice file.
     *
     * @param string|array $name
     * @param string|int   $id
     *
     * @return $this
     */
    public function file($name, $id = null)
    {
        Util::operateArray($this->smsData['files'], $name, $id);

        return $this;
    }

    /**
     * Set params of agent.
     *
     * @param string|array    $name
     * @param array|bool|null $params
     * @param bool            $override
     *
     * @return $this
     */
    public function params($name, $params = null, $override = false)
    {
        $overrideAll = (is_array($name) && is_bool($params)) ? $params : false;
        Util::operateArray($this->smsData['params'], $name, $params, [], function ($name, array $params) use ($override) {
            if (!isset($this->smsData['params'][$name])) {
                $this->smsData['params'][$name] = [];
            }
            $target = &$this->smsData['params'][$name];
            if ($override) {
                $target = $params;
            } else {
                $target = array_merge($target, $params);
            }
        }, $overrideAll);

        return $this;
    }

    /**
     * Set the first agent.
     *
     * @param string $name
     *
     * @throws PhpSmsException
     *
     * @return $this
     */
    public function agent($name)
    {
        if (!is_string($name) || empty($name)) {
            throw new PhpSmsException('Expected the parameter to be non-empty string.');
        }
        $this->firstAgent = $name;

        return $this;
    }

    /**
     * Start send.
     *
     * If call with a `true` parameter, this system will immediately start request to send sms whatever whether to use the queue.
     * if the current instance has pushed to the queue, you can recall this method in queue system without any parameter,
     * so this mechanism in order to make you convenient to use this method in queue system.
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
            return self::$task->data($this->all())->run($this->firstAgent);
        }

        return $this->push();
    }

    /**
     * Push to the queue system.
     *
     * @throws \Exception | PhpSmsException
     *
     * @return mixed
     */
    public function push()
    {
        if (!is_callable(self::$howToUseQueue)) {
            throw new PhpSmsException('Expected define how to use the queue system by methods `queue`.');
        }
        try {
            $this->pushedToQueue = true;

            return call_user_func_array(self::$howToUseQueue, [$this, $this->all()]);
        } catch (\Exception $e) {
            $this->pushedToQueue = false;
            throw $e;
        }
    }

    /**
     * Get all of the data.
     *
     * @param null|string $key
     *
     * @return mixed
     */
    public function all($key = null)
    {
        if ($key !== null) {
            return isset($this->smsData[$key]) ? $this->smsData[$key] : null;
        }

        return $this->smsData;
    }

    /**
     * Define the static hook methods by overload static method.
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
        if (!in_array($name, self::$availableHooks)) {
            throw new PhpSmsException("Not found methods `$name`.");
        }
        $handler = $args[0];
        $override = isset($args[1]) ? (bool) $args[1] : false;
        self::getTask()->hook($name, $handler, $override);
    }

    /**
     * Define the hook methods by overload method.
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
            $this->state['scheme'] = self::toggleSerializeScheme(self::scheme());
            $this->state['agentsConfig'] = self::config();
            $this->state['handlers'] = self::serializeHandlers();
        } catch (\Exception $e) {
            //swallow exception
        }

        return ['smsData', 'firstAgent', 'pushedToQueue', 'state'];
    }

    /**
     * Deserialize magic method.
     */
    public function __wakeup()
    {
        if (empty($this->state)) {
            return;
        }
        self::$scheme = self::toggleSerializeScheme($this->state['scheme']);
        self::$agentsConfig = $this->state['agentsConfig'];
        self::reinstallHandlers($this->state['handlers']);
    }

    /**
     * Serialize or deserialize the scheme.
     *
     * @param array $scheme
     *
     * @return array
     */
    protected static function toggleSerializeScheme(array $scheme)
    {
        foreach ($scheme as $name => &$options) {
            if (is_array($options)) {
                foreach (ParasiticAgent::methods() as $method) {
                    self::toggleSerializeClosure($options, $method);
                }
            }
        }

        return $scheme;
    }

    /**
     * Serialize the hooks' handlers.
     *
     * @return array
     */
    protected static function serializeHandlers()
    {
        $hooks = (array) self::getTask()->handlers;
        foreach ($hooks as &$handlers) {
            foreach (array_keys($handlers) as $key) {
                self::toggleSerializeClosure($handlers, $key);
            }
        }

        return $hooks;
    }

    /**
     * Reinstall hooks' handlers.
     *
     * @param array $handlers
     */
    protected static function reinstallHandlers(array $handlers)
    {
        $serializer = Util::getClosureSerializer();
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
     * Serialize or deserialize the specified closure and then replace the original value.
     *
     * @param array      $options
     * @param int|string $key
     */
    protected static function toggleSerializeClosure(array &$options, $key)
    {
        if (!isset($options[$key])) {
            return;
        }
        $serializer = Util::getClosureSerializer();
        if (is_callable($options[$key])) {
            $options[$key] = (string) $serializer->serialize($options[$key]);
        } elseif (is_string($options[$key])) {
            $options[$key] = $serializer->unserialize($options[$key]);
        }
    }
}
