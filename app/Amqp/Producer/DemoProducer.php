<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

///**
// * @Producer()
// */
/**
 * @Producer(exchange="test9", routingKey="son")
 */
class DemoProducer extends ProducerMessage
{
//    protected $exchange = 'task221';
    protected $type = Type::DIRECT;
//    protected $routingKey = 'task221';
    public function __construct($data)
    {
        $this->payload = $data;
    }
}
