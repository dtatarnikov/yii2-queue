<?php
namespace strong2much\queue\services;

use AMQPExchange;
use AMQPConnection;
use AMQPChannel;
use AMQPEnvelope;
use AMQPQueue;
use AMQPException;
use Yii;
use yii\base\InvalidConfigException;

/**
 * RabbitQueue represents the RabbitMQ (Message Queue) service
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class RabbitQueue implements IQueue
{
    public $host      = 'localhost';
    public $port      = '5672';
    public $vhost     = '/';

    public $login     = 'guest';
    public $password  = 'guest';

    /**
     * @var string Default exchange name
     */
    public $exchangeName;

    /**
     * @var AMQPExchange Instance of amqp exchange
     */
    protected $_exchange = null;

    /**
     * @var AMQPConnection Instance of amqp connection - client
     */
    protected $_client = null;

    /**
     * @var AMQPChannel instance of amqp channel
     */
    protected $_channel = null;

    /**
     * Base initiation of queue service
     */
    public function init()
    {
        if(!isset($this->exchangeName)) {
            throw new InvalidConfigException(Yii::t('queue', '"exchangeName" must be specified'));
        }

        //init AMQP client
        $this->_client = new AMQPConnection([
            'host'     => $this->host,
            'vhost'    => $this->vhost,
            'port'     => $this->port,
            'login'    => $this->login,
            'password' => $this->password,
        ]);

        //connect to amqp server
        if (method_exists($this->_client, 'connect') && $this->_client->isConnected()==false)
            $this->_client->connect();

        if($this->_client->isConnected()) {
            $this->_channel = new AMQPChannel($this->_client);
        }
    }

    /**
     * Send message to queue service
     * @param string $message message to publish
     * @param string $route routing key
     * @param integer $flags One or more of AMQP_MANDATORY and AMQP_IMMEDIATE.
     * @param array $attributes One of content_type, content_encoding,
     *                          message_id, user_id, app_id, delivery_mode,
     *                          priority, timestamp, expiration, type
     *                          or reply_to.
     * @return boolean true on success, otherwise false
     */
    public function sendMessage($message, $route, $flags, $attributes)
    {
        try {
            return $this->getExchange()->publish($message, $route, $flags, $attributes);
        } catch(AMQPException $e) {
            return false;
        }
    }

    /**
     * Receive next message from the queue
     * @param string $route routing key or queue name
     * @param string|bool $queue queue name, if false do not use it
     * @return string|bool next message from the queue, if not found returns false
     */
    public function receiveMessage($route, $queue = false)
    {
        $queue = $this->createQueue($queue===false ? $route : (is_string($queue) ? $queue : $route), AMQP_DURABLE, [$route]);

        /** @var AMQPEnvelope $message */
        $message = $queue->get(AMQP_AUTOACK);

        if ($message === false) {
            //No available messages
            return false;
        }

        $msgBody = $message->getBody();
        if (empty($msgBody)) {
            //Empty message
            return false;
        }

        return $msgBody;
    }

    /**
     * @return AMQPExchange the instance of exchange
     */
    protected function getExchange()
    {
        if ($this->_exchange == null) {
            $this->_exchange = new AMQPExchange($this->_channel);
            $this->_exchange->setName($this->exchangeName);
            $this->_exchange->setType(AMQP_EX_TYPE_TOPIC);
            $this->_exchange->setFlags(AMQP_DURABLE);
            if(method_exists($this->_exchange, 'declareExchange'))
                $this->_exchange->declareExchange();
            else
                $this->_exchange->declare();
        }

        return $this->_exchange;
    }

    /**
     * Declares a new Queue on the broker and binds it to the exchange
     * @param string       $name        queue name
     * @param integer      $flags       queue flags
     * @param string|array $routingKeys queue routing keys
     * @return AMQPQueue
     */
    protected function createQueue($name, $flags = null, $routingKeys = "")
    {
        $queue = new AMQPQueue($this->_channel);
        $queue->setName($name);
        $queue->setFlags($flags);
        if(method_exists($queue, 'declareQueue'))
            $queue->declareQueue();
        else
            $queue->declare();

        foreach ((array)$routingKeys as $routingKey) {
            $queue->bind($this->exchangeName, $routingKey);
        }

        return $queue;
    }
} 