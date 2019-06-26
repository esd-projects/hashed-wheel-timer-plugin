<?php

namespace  ESD\Plugins\HashedWheelTimer;


interface HashedWheelTimerStorage
{
    public function get(string $id);

    public function set(string $id, string $data);

    public function remove(string $id);
}