<?php

namespace  ESD\Plugins\HashedWheelTimer;

use ESD\Core\Plugins\Logger\GetLogger;

abstract class  HashedWheelTimerRunnable
{

    use GetLogger;
    use GetHashedWheelTimer;



    protected $params;

    protected $point;

    protected $wheel;

    protected $delay = 0;

    public function __construct($wheel,$point,$params){
        $this->params = $params;
        $this->point = $point;
        $this->wheel = $wheel;
    }

    public function getParams(){
        return $this->params['data'];
    }

    /**
     * 设置延迟发送
     * @param $ttl
     */
    public function setDelayTTL($ttl){
        $this->delay = $ttl;
    }

    public function getDelayTTL(){
        return $this->delay;
    }





    abstract public function run();

    /**
     * 任务失败默认会进行重试，该方法返回重试次数，供用户自行处理
     * @return mixed
     */
    public function getRetryTimes(){
        return $this->params['retry'];
    }

    /**
     * 获取已经执行过的延后次数
     * @return mixed
     */
    public function getDelayTimes(){
        return $this->params['delay'];
    }

}
