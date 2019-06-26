<?php

namespace  ESD\Plugins\HashedWheelTimer;
use ESD\Core\Plugins\Config\BaseConfig;


class hashedWheelTimerConfig extends BaseConfig{

    const key = "hashedWheelTimer";

    /**
     * 每格的时间间隔
     * @var int
     */
    protected $tickDuration = 1 * 1000;

    /**
     * @var string
     */
    protected $db = "default";

    /**
     * 一圈下来有几格
     * @var float|int
     */
    protected $ticksPerWheel = 60 * 60;

    /**
     * 队列池
     * @var
     */
    protected $wheel;


    /**
     * 协程池每次最多同时执行条数
     * @var int
     */
    protected $maxPendingTimeouts = 100;


    protected $taskProcessCount = 1;

    /**
     * @var string
     */
    protected $hashedWheelTimerStorageClass = RedisHashedWheelTimerStorage::class;

    public function __construct()
    {
        parent::__construct(self::key);
    }

    public function getWheel(){
        return $this->wheel;
    }


    public function setWheel($wheel){
        $this->wheel = $wheel;
    }

    public function getDb() : string {
        return $this->db;
    }

    public function setDb(String $db): void {
        $this->db = $db;
    }

    public function getHashedWheelTimerStorageClass(): string
    {
        return $this->hashedWheelTimerStorageClass;
    }

    public function setHashedWheelTimerStorageClass($class) : void
    {
        $this->hashedWheelTimerStorageClass = $class;
    }


    public function getTaskProcessCount(): int {
        return $this->taskProcessCount;
    }
    public function setTaskProcessCount(int $num): void {
        $this->taskProcessCount = $num;
    }

    public function getMaxPendingTimeouts(): int {
        return $this->maxPendingTimeouts;
    }

    public function setMaxPendingTimeouts(int $num): void {
        $this->maxPendingTimeouts = $num;
    }

}
