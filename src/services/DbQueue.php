<?php
namespace strong2much\queue\services;

use strong2much\queue\models\QueueMessage;

/**
 * DbQueue represents the queue service that works with db table {{queue_message}} as one big queue with multiple routes
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class DbQueue implements IQueue
{
    /**
     * Base initiation of queue service
     */
    public function init()
    {

    }

    /**
     * Send message to queue
     * @param string $message message to publish
     * @param string $route optional routing key
     * @param integer $flags message flags
     * @param array $attributes message attributes
     * @return boolean true on success, otherwise false
     */
    public function sendMessage($message, $route, $flags, $attributes)
    {
        $msg = new QueueMessage();
        $msg->route = $route;
        $msg->message = $message;
        return $msg->save();
    }

    /**
     * Receive next message from the queue
     * @param string $route routing key or queue name
     * @param string|bool $queue queue name, if false do not use it
     * @return string|bool next message from the queue, if not found returns false
     */
    public function receiveMessage($route, $queue = false)
    {
        /** @var QueueMessage $message */
        $message = QueueMessage::find()->where(['route'=>$route])->orderBy('time')->one();
        if(isset($message)) {
            $msgBody = $message->message;
            $message->delete(); //delete message after receiving
            return $msgBody;
        }

        return false;
    }
} 
