# hashed-wheel-timer-plugin
HashedWheelTimer是采用一种定时轮的方式来管理和维护大量的Timer调度算法。
一个 HashedWheelTimer 是环形结构，类似一个时钟，分为很多槽，一个槽代表一个时间间隔，每个槽又对应一个类似Map结构的对象，使用双向链表存储定时任务，指针周期性的跳动，跳动到一个槽位，就执行该槽位的定时任务。
环形结构可以根据超时时间的 hash 值(这个 hash 值实际上就是ticks & mask)将 task 分布到不同的槽位中, 当 tick 到那个槽位时, 只需要遍历那个槽位的 task 即可知道哪些任务会超时(而使用线性结构, 你每次 tick 都需要遍历所有 task), 所以, 我们任务量大的时候, 相应的增加 wheel 的 ticksPerWheel 值, 可以减少 tick 时遍历任务的个数.

本插件模拟java HashedWheelTimer 的实现。使用redis做持久存储。

# 配置方法
配置一个环形结构池
~~~
hashedWheelTimer:
  db: default
  max_pending_timeouts: 100
  wheel:
    - {name: aaa, tick_duration: 1, ticks_per_wheel: 60 }
~~~
name  池子名称，投递任务的时候需要

tick_duration，时间间隔，1秒

ticks_per_wheel，时间槽数量，60个，代表一分钟一个轮次

max_pending_timeouts，每一个槽允许的最大协程数，默认100，如果耗时任务或者队列特别长需要适当增加时间槽数量比如3600。不适合特别精准的延时场景。

db，使用的 redis 配置，强烈建议不要使用default，会占用http服务的连接数，应该复制一份配置专用

# 使用方法

## 投递任务
~~~
  //环形池名称，执行任务的类，投递参数，延迟执行时间，秒
  $this->addTask('aaa', TimerTask::class,['a'=>'b', 'time' => time()], 60);
~~~

## 投递类
投递的类需要继承 HashedWheelTimerRunnable 
~~~
<?php
namespace app\Controller;

use ESD\Plugins\HashedWheelTimer\HashedWheelTimerRunnable;

class  TimerTask extends HashedWheelTimerRunnable{

    public function run()
    {
        /**
        //此处可根据延迟次数设置不同的延迟时间，比如支付通知失败
        if($this->getDelayTimes() <= 1){
            $this->setDelayTTL(10);
        }else if ($this->getDelayTimes() <= 2){
            $this->setDelayTTL(20);
        } else if ($this->getDelayTimes() <= 3){
            $this->setDelayTTL(30);
        } else if ($this->getDelayTimes() <= 4){
            $this->setDelayTTL(40);
        }else if ($this->getDelayTimes() <= 5){
            $this->setDelayTTL(40);
        }
         * **/


        //如果 return false 或者该类触发任意异常，系统会将此任务重新投递到下一次执行的位置。
        //如果不需要失败重试，需要 return true。可通过 getRetryTimes 获取重试次数进行判断
        //如超过5次则不再重试，直接return true。
        $this->getRetryTimes()


        //获取投递参数
        $params = $this->getParams();
        // TODO: Implement run() method.

        $this->info('run', $params);
        //如果执行 setDelayTTL ， 需要return true ，否则会被重新投递到下一次执行的位置。
        return true;
    }
}
~~~
