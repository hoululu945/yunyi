<?php


namespace App\Amqp\Consumer;

use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use PhpAmqpLib\Message\AMQPMessage;

//
/**
 * @Consumer(exchange="test9", routingKey="son", queue="dui", name ="DemoConsumer", nums=1)
 */

class TestConsumer extends ConsumerMessage
{
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

        echo '*^^^^^^^^^^^^^^^^^^^*^^^^^^^^^^^^^^^^^^^*^^^^^^^^^^^^^^^^^^^*^^^^^^^^^^^^^^^^^^^*^^^^^^^^^^^^^^^^^^^*^^^^^^^^^^^^^^^^^^^9';

        var_dump($data);
        return Result::ACK;
    }
}