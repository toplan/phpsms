<?php

use Toplan\PhpSms\Sms;

class ProtectedTest extends PHPUnit_Framework_TestCase
{
    public static function getPrivateMethod($name)
    {
        $obj = new Sms();
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testConfiguration()
    {
        $method = self::getPrivateMethod('configuration');
        $obj = new Sms();
        $method->invokeArgs($obj, []);
        $config = include __DIR__ . '/../src/config/phpsms.php';
        $this->assertCount(count($config['enable']), Sms::getEnableAgents());
        $this->assertCount(count($config['enable']), Sms::getAgentsConfig());

        Sms::enable(['Log', 'Luosimao', 'YunPian']);
        $this->assertCount(3, Sms::getEnableAgents());

        $obj2 = Sms::make();
        $this->assertCount(3, Sms::getAgentsConfig());
    }

    public function testValidator()
    {
        $method = self::getPrivateMethod('validator');
        $obj = Sms::make()->to('18280000000');
        $r = $method->invokeArgs($obj, []);
        $this->assertTrue($r);
    }
}
