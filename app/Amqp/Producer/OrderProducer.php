<?php


namespace App\Amqp\Producer;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
/**
 * @Producer(exchange="yunyi", routingKey="order")
 */

class OrderProducer extends ProducerMessage
{

    protected $type = Type::DIRECT;
//    protected $routingKey = 'task221';
    public function __construct($data)
    {
        $this->payload = $data;

    }
}