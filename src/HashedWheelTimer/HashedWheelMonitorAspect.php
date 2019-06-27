<?php
/**
 * Created by PhpStorm.
 * User: anythink
 * Date: 2019/6/27
 * Time: 16:35
 */

namespace ESD\Plugins\HashedWheelTimer;


use ESD\Core\DI\DI;
use ESD\Core\Plugins\Logger\GetLogger;
use ESD\Core\Server\Beans\Request;
use ESD\Plugins\Actuator\ActuatorController;
use ESD\Plugins\Actuator\Aspect\ActuatorAspect;
use ESD\Plugins\Aop\OrderAspect;
use ESD\Plugins\EasyRoute\Aspect\RouteAspect;
use FastRoute\Dispatcher;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;

class HashedWheelMonitorAspect extends OrderAspect
{
    use GetLogger;
    /**
     * @var ActuatorController
     */
    private $actuatorController;
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct()
    {
        $this->atBefore(ActuatorAspect::class);
    }

    /**
     * around onHttpRequest
     *
     * @param MethodInvocation $invocation Invocation
     * @return mixed|null
     * @Around("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onHttpRequest(*))")
     */
    protected function aroundRequest(MethodInvocation $invocation)
    {

        list($request, $response) = $invocation->getArguments();



        $path = $request->getUri()->getPath();

        if($path == '/actuator/hashed-wheel-timer'){
            /**
             * @var $config hashedWheelTimerConfig
             */
            $config = DIGet(hashedWheelTimerConfig::class);

            /**
             * @var $storage RedisHashedWheelTimerStorage
             */
            $storage = DIGet(HashedWheelTimerStorage::class);


            $data = [
                'db'     => $config->getDb(),
                'class'  => $config->getHashedWheelTimerStorageClass(),
            ];
            $wheel = HashedWheelTimer::$wheel;

            foreach ($wheel as $name => &$d){
                $num = [];
                $d['total_count'] = 0;
                for($i=0; $i<$d['ticksPerWheel']; $i++){
                    $len = 0;
                    $len = $storage->llenWheel($name, $i);
                    $num[]= ['slot' => $i, 'len' => $len];
                    $d['total_len'] += $len;
                }
                $d['num'] = $num;
            }
            $data['wheel']  = $wheel;
            $data['server'] = $storage->info();

            $response->withHeader("Content-Type", "application/json; charset=utf-8");
            return $response->withContent(json_encode($data));

        }
        return $invocation->proceed();

    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "HashedWheelMonitorAspect";
    }
}