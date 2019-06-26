<?php
namespace  ESD\Plugins\HashedWheelTimer;

use ESD\Plugins\Redis\GetRedis;

class RedisHashedWheelTimerStorage implements HashedWheelTimerStorage {



    use GetRedis;
    /**
     * @var hashedWheelTimerConfig
     */
    private $config;

    const prefix = "HASH_WHEEL_";

    public function __construct(hashedWheelTimerConfig $sessionConfig)
    {
        $this->config = $sessionConfig;
    }


    function getPoint($key){
        if($point = $this->redis($this->config->getDb())->get( self::prefix.$key)){
            return $point;
        }else{
            return 0;
        }
    }

    function updatePoint($key){
        return $this->redis($this->config->getDb())->incr(self::prefix.$key);
    }


    /**
     * 将延迟任务插入队列
     * @param $key
     * @param $mask
     * @param $val
     * @return bool|int
     * @throws \ESD\Plugins\Redis\RedisException
     */
    function pushWheel($key,$mask, $val){
        return $this->redis($this->config->getDb())->lPush(self::prefix.$key.$mask, serverSerialize($val));
    }

    /**
     * 将时间没到的数据移入临时队列
     * @param $key
     * @param $mask
     * @param $val
     * @throws \ESD\Plugins\Redis\RedisException
     */
    function pushWheelTmp($key, $mask, $val){
        $this->redis($this->config->getDb())->lPush(self::prefix.$key.$mask.'_tmp', serverSerialize($val));
    }





    /**
     * 取出当前秒数的延迟队列 并备份到_tmp 中
     * @param $key
     * @param $mask
     * @return mixed
     * @throws \ESD\Plugins\Redis\RedisException
     */
    function popWheel($key,$mask){
        return serverUnSerialize($this->redis($this->config->getDb())->rPop(self::prefix.$key.$mask));
    }

    /**
     * 取出备份的队列，进行恢复。
     * @param $key
     * @param $mask
     * @return mixed
     * @throws \ESD\Plugins\Redis\RedisException
     */
    function popWheelTmp($key,$mask){
        return serverUnSerialize($this->redis($this->config->getDb())->rPop(self::prefix.$key.$mask.'_tmp'));
    }

    function remTWheelTask($key, $mask, $val){
        return $this->redis($this->config->getDb())->lRem(self::prefix.$key.$mask, serverSerialize($val), 0);
    }








    function  set(string $id, string $data)
    {
        return $this->redis($this->config->getDb())->set(self::prefix.$id,$data);
        // TODO: Implement set() method.
    }

    function get(string $id)
    {
        return $this->redis($this->config->getDb())->get(self::prefix.$id);
    }

    function remove(string $id)
    {
        return $this->redis($this->config->getDb())->del(self::prefix.$id);
    }
}