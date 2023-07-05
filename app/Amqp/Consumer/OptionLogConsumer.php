<?php


namespace App\Amqp\Consumer;

use App\Model\OptionLog;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use PhpAmqpLib\Message\AMQPMessage;

//
/**
 * @Consumer(exchange="yunyi", routingKey="option", queue="log", name ="DemoConsumer", nums=1)
 */
class OptionLogConsumer  extends ConsumerMessage
{
    protected $type = Type::DIRECT;
    public  function consumeMessage($data, AMQPMessage $message): string
    {

        echo '@****************************************************************************************************';

//        var_dump($data);
        OptionLog::insert($data);
        return Result::ACK;
    }

}