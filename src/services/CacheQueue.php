<?php
namespace strong2much\queue\services;

use yii\base\Object;
use yii\caching\Cache;
use yii\di\Instance;

/**
 * CacheQueue represents the queue service that works via cache component
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class CacheQueue extends Object implements IQueue
{
    const CACHE_QUEUES = 'cache_queues'; //list of all active queues
    const CACHE_KEY = 'cache_queue_';

    /**
     * @var Cache|array|string the DB connection object or the application component ID of the DB connection.
     */
    public $cache = 'cache';

    /**
     * Base initiation of queue service
     */
    public function init()
    {
        $this->cache = Instance::ensure($this->cache, Cache::className());
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
        $key = self::CACHE_KEY.$route;
        $data = $this->cache->get($key);
        if($data === false) {
            $data = [];
        }
        $data[] = $message;

        if($this->cache->set($key, $data)) {
            $queues = $this->cache->get(self::CACHE_QUEUES);
            if($queues === false) {
                $queues = [];
            }
            $queues[$key] = count($data);
            $this->cache->set(self::CACHE_QUEUES, $queues);
            return true;
        }

        return false;
    }

    /**
     * Receive next message from the queue
     * @param string $route routing key or queue name
     * @param string|bool $queue queue name, if false do not use it
     * @return string|bool next message from the queue, if not found returns false
     */
    public function receiveMessage($route, $queue = false)
    {
        $key = self::CACHE_KEY.$route;
        $data = $this->cache->get($key);
        if($data === false)
            return false;

        $queues = $this->cache->get(self::CACHE_QUEUES);
        if($queues === false) {
            $queues = [];
        }

        $msgBody = array_shift($data); //delete message after receiving
        if(empty($data)) {
            $this->cache->delete($key);
            unset($queues[$key]);
        } else {
            $this->cache->set($key, $data);
            $queues[$key] = count($data);
        }
        if(empty($queues)) {
            $this->cache->delete(self::CACHE_QUEUES);
        } else {
            $this->cache->set(self::CACHE_QUEUES, $queues);
        }

        return $msgBody;
    }
} 
