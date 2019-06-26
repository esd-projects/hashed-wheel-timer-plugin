<?php

namespace  ESD\Plugins\HashedWheelTimer;

use ESD\Core\Plugins\Logger\GetLogger;
use ESD\Core\Exception;

class HashedWheelTimer {

    use GetLogger;

    static $wheel = [];


    /**
     * @var HashedWheelTimer
     */
    public static $instance;

    protected  $config;
    /**
     * @var RedisHashedWheelTimerStorage
     */
    protected $storage;

    function __construct(HashedWheelTimerConfig $config)
    {
        $this->config = $config;
        foreach ($config->getWheel() as $d){
            $this->addWheel($d['name'], $d['tick_duration'], $d['ticks_per_wheel']);
        }

        $class = $this->config->getHashedWheelTimerStorageClass();
        $this->storage = new $class($this->config);
        self::$instance = $this;
    }

    /**
     * @param $name
     * @param int $tickDuration 每格的时间间隔，默认1s
     * @param int $ticksPerWheel 一圈下来有几格，默认60 一分钟
     */
    function addWheel($name, int $tickDuration = 1,int $ticksPerWheel = 60): void {

        if(isset(self::$wheel[$name] )){
            throw  new \InvalidArgumentException("Wheel isset");
        }
        if ($ticksPerWheel <= 0) {
            throw new \InvalidArgumentException(
                "ticksPerWheel must be greater than 0: " + ticksPerWheel);
        }
        if ($ticksPerWheel > 1073741824) {
            throw new \IllegalArgumentException(
                "ticksPerWheel may not be greater than 2^30: " + ticksPerWheel);
        }


        //ticksPerWheel = normalizeTicksPerWheel(ticksPerWheel);
        //HashedWheelBucket[] wheel = new HashedWheelBucket[ticksPerWheel];
        $this->info('add wheel ' . $name);
        self::$wheel[$name] = ['name' => $name, 'tickDuration' => $tickDuration, 'ticksPerWheel' => $ticksPerWheel];
    }

    //36 3600
    function addTask($wheel_name,$class, $key , $ttl){

        if(!isset(self::$wheel[$wheel_name])){
            throw  new Exception('wheel_name is undefined');
        }

        $ticks = $this->storage->getPoint($wheel_name); //获取wheel循环的指针
        $mask  = bcadd($ttl,$ticks);
        $wheel = bcdiv($mask, self::$wheel[$wheel_name]['ticksPerWheel'], 2); //获取圈数

        if($wheel < 1){
            $loop = 0;
            // 如果0圈，默认偏移即可
        }else{
            $loop = intval($wheel);
            $mask = abs($mask - bcmul(self::$wheel[$wheel_name]['ticksPerWheel'] , $loop)); //获取偏移
        }
        $this->debug('add HashedWheelTimer task ', ['key' => $key, 'loop' => $loop, 'mask' => $mask]);
        return $this->storage->pushWheel($wheel_name , $mask, [
            'wheel' => $loop,
            'class' => $class,
            'data' => $key,
            'delay' => 0,
            'retry' => 0,
        ]);
    }


}
