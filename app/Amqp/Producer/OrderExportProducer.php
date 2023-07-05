<?php


namespace App\Amqp\Producer;


use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
/**
 * @Producer(exchange="yunyi", routingKey="excel")
 */

class OrderExportProducer extends ProducerMessage
{
    protected $type = Type::DIRECT;
//    protected $routingKey = 'task221';
    public function __construct($data)
    {
        $this->payload = $data;

    }
}