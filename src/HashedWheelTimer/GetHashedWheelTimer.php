<?php

namespace  ESD\Plugins\HashedWheelTimer;

trait GetHashedWheelTimer {

    public function addTask($wheel_name, $class, $params, $ttl){
        return HashedWheelTimer::$instance->addTask($wheel_name,$class, $params , $ttl);
    }

}
