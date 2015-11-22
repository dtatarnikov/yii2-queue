<?php
namespace strong2much\queue\services;

/**
 * Interface for all message queue services
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
interface IQueue
{
    /**
     * Send message to queue
     * @param string $message message to publish
     * @param string $route routing key or queue name
     * @param integer $flags message flags
     * @param array $attributes message attributes
     * @return boolean true on success, otherwise false
     */
    function sendMessage($message, $route, $flags, $attributes);

    /**
     * Receive next message from the queue
     * @param string $route routing key or queue name
     * @param string|bool $queue queue name, if false do not use it
     * @return string|bool next message from the queue, if not found returns false
     */
    function receiveMessage($route, $queue = false);
} 