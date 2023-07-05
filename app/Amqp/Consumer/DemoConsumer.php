<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use PhpAmqpLib\Message\AMQPMessage;

//
/**
 * @Consumer(exchange="test9", routingKey="son", queue="hyperf", name ="DemoConsumer", nums=1)
 */

///**
// * @Consumer()
// */
class DemoConsumer extends ConsumerMessage
{
//    protected $exchange = 'task221';
    protected $type = Type::DIRECT;
//    protected $type = Type::TOPIC;


//    protected $queue = 'task221';
//    public function consumeMessage($data, AMQPMessage $message): string
//    {
//        var_dump($data);
//        echo  '****************************************************************************************************';
//        return Result::ACK;
//    }
//    public function consume($data): string
    public  function consumeMessage($data, AMQPMessage $message): string
    {

        echo '@****************************************************************************************************';

        var_dump($data);
        return Result::ACK;
    }

}
