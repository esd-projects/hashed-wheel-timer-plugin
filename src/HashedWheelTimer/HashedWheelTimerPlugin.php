<?php

namespace  ESD\Plugins\HashedWheelTimer;

use ESD\Core\Context\Context;
use ESD\Core\PlugIn\AbstractPlugin;
use ESD\Core\PlugIn\PluginInterfaceManager;
use ESD\Core\Server\Server;
use ESD\Plugins\Aop\AopConfig;
use ESD\Plugins\Aop\AopPlugin;
use ESD\Plugins\Redis\RedisPlugin;

class HashedWheelTimerPlugin extends AbstractPlugin
{

    protected $config;
    protected $storage;
    const processName = 'HashedWheel';
    const processGroupName = 'HashedWheelGroup';

    function __construct(hashedWheelTimerConfig $hashedWheelTimerConfig = null)
    {
        parent::__construct();
        $this->atAfter(RedisPlugin::class);
        if ($hashedWheelTimerConfig == null) {
            $hashedWheelTimerConfig = new hashedWheelTimerConfig();
        }
        $this->config = $hashedWheelTimerConfig;
    }


    public function init(Context $context)
    {
        parent::init($context); // TODO: Change the autogenerated stub
        $aopConfig = DIget(AopConfig::class);
        $serverConfig = Server::$instance->getServerConfig();
        $aopConfig->addIncludePath($serverConfig->getVendorDir() . "/esd/hashed-wheel-timer-plugin/src");
        $aopConfig->addAspect(new HashedWheelMonitorAspect());
    }

    /**
     * @param PluginInterfaceManager $pluginInterfaceManager
     * @return mixed|void
     * @throws \ESD\Core\Exception
     */
    public function onAdded(PluginInterfaceManager $pluginInterfaceManager)
    {
        parent::onAdded($pluginInterfaceManager);
        $pluginInterfaceManager->addPlug(new RedisPlugin());

        $aopPlugin = $pluginInterfaceManager->getPlug(AopPlugin::class);
        if ($aopPlugin == null) {
            $aopPlugin = new AopPlugin();
            $pluginInterfaceManager->addPlug($aopPlugin);
        }
        
    }

    function getName(): string
    {
        return 'hashedWheelTimer';
    }

    /**
     * @param Context $context
     * @return mixed|void
     * @throws \ESD\Core\Plugins\Config\ConfigException
     * @throws \ReflectionException
     */
    function beforeServerStart(Context $context)
    {
        $this->config->merge();


        $class = $this->config->getHashedWheelTimerStorageClass();
        $this->storage = new $class($this->config);


        $this->setToDIContainer(HashedWheelTimer::class, new HashedWheelTimer($this->config));
        $this->setToDIContainer(HashedWheelTimerStorage::class, $this->storage);

        //添加任务进程
        foreach (HashedWheelTimer::$wheel as $name => $val){
            for ($i = 0; $i < $this->config->getTaskProcessCount(); $i++) {
                Server::$instance->addProcess($name."-$i", HashedWheelTimerProcess::class, self::processGroupName);
            }
        }
    }


    function beforeProcessStart(Context $context)
    {
        $this->ready();
    }




}
