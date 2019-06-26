<?php

namespace  ESD\Plugins\HashedWheelTimer;

use ESD\Core\Message\Message;
use ESD\Core\Plugins\Logger\GetLogger;
use ESD\Core\Server\Process\Process;
use ESD\Plugins\Redis\GetRedis;

class HashedWheelTimerProcess  extends Process {

    use GetLogger;
    use GetRedis;


    /**
     * @var RedisHashedWheelTimerStorage
     */
    protected $storage;

    /**
     * @var HashedWheelTimerConfig
     */
    protected $config;


    protected  $wheel;
    /**
     * 在onProcessStart之前，用于初始化成员变量
     * @return mixed
     */
    public function init()
    {
        $this->storage = DIGet(RedisHashedWheelTimerStorage::class);
        $this->config = DIGet(HashedWheelTimerConfig::class);

        list($name, $_num) = explode('-',$this->getProcessName());
        $this->wheel = HashedWheelTimer::$wheel[$name];
        unset($_num);
    }

    public function onProcessStart()
    {
        // yes
        $wheel = $this->wheel;
        $this->info('onProcessStart');

        addTimerTick($wheel['tickDuration'] * 1000,function() use($wheel){

            $point = $this->storage->getPoint($wheel['name']);
            //$this->info('tick '. $point);

            goWithContext(function () use($wheel, $point){
                while ($row = $this->storage->popWheel($wheel['name'],$point)){
                    print_r($row);
                    $this->debug('popWheel',$row);
                    if($row['wheel'] > 0){
                        $row['wheel']--;
                        $this->storage->pushWheelTmp($wheel['name'], $point, $row);
                    }else{
                        goWithContext(function () use ($row, $point){
                            $this->run($row, $point);
                        });
                    }
                }
                //循环结束之后，将错误的数据或者时间没到的数据放回去。
                while ($row2 = $this->storage->popWheelTmp($wheel['name'], $point)){
                    $this->storage->pushWheel($wheel['name'], $point, $row2);
                }
            });

            if($point+1 >= $wheel['ticksPerWheel']){
                $this->storage->set($wheel['name'], 0);
            }else{
                $this->storage->updatePoint($wheel['name']);
            }

        });
    }


    protected function run($row, $point){
        try{
            /**
             * @var $class HashedWheelTimerRunnable
             */
            $class = new $row['class']($this->wheel, $point, $row);

            if($class->run()) {
                if($ttl = $class->getDelayTTL()){
                    $this->delay($point, $ttl, $row);
                }
            }else{
                $row['retry']++;
                $this->storage->pushWheelTmp($this->wheel['name'], $point, $row);
            }

        }catch (\Exception $e){
            $row['retry']++;
            $this->storage->pushWheelTmp($this->wheel['name'], $point, $row);
            $this->debug('do run Exception' . $e->getMessage(), $row);
        }
    }

    public function delay($point, $ttl, $row){
        $row['delay'] ++;

        $mask  = bcadd($ttl,$point);
        $wheel = bcdiv($mask, HashedWheelTimer::$wheel[$this->wheel['name']]['ticksPerWheel'], 2); //获取圈数

        if($wheel < 1){
            $loop = 0;
            // 如果0圈，默认偏移即可
        }else{
            $loop = intval($wheel);
            $mask = abs($mask - bcmul(HashedWheelTimer::$wheel[$this->wheel['name']]['ticksPerWheel'] , $loop)); //获取偏移
        }

        $this->debug('delay HashedWheelTimer task ', ['key' => $row['data'], 'loop' => $loop, 'mask' => $mask]);
        $this->storage->pushWheel($this->wheel['name'], $mask, $row);
        $this->debug('delay' . $ttl, $row);
    }

    public function onProcessStop()
    {
        $point = $this->storage->getPoint($this->wheel['name']);
        while ($row2 = $this->storage->popWheelTmp($this->wheel['name'], $point)){
            $this->storage->pushWheel($this->wheel['name'], $point, $row2);
        }
    }

    public function onPipeMessage(Message $message, Process $fromProcess)
    {

    }

}
