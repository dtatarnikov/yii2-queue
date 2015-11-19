<?php
namespace strong2much\queue\services;

/**
 * DummyQueue represents the dummy queue service
 *
 * @package  App.components.queue
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class DummyQueue implements IQueue
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
        return true;
    }

    /**
     * Receive next message from the queue
     * @param string $route routing key or queue name
     * @param string|bool $queue queue name, if false do not use it
     * @return string|bool next message from the queue, if not found returns false
     */
    public function receiveMessage($route, $queue = false)
    {
        return false;
    }
} 