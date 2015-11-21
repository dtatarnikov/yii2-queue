<?php
namespace strong2much\queue;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\Inflector;
use strong2much\queue\services\IQueue;
use strong2much\queue\messages\Message;

/**
 * QueueManager is an application component that manages with message queue services
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class QueueManager extends Component
{
    /**
     * @var array Service config
     */
    public $serviceConfig;

    /**
     * @var string default namespace for messages
     */
    public $messageNamespaces = 'strong2much\\messages';

    /**
     * @var IQueue pointer to service
     */
    private $_service;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();

        $this->setService($this->serviceConfig);
    }

    /**
     * @return IQueue retrieves message queue service
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     * Set service for queue
     * @param array $config service settings
     * @throws InvalidConfigException
     */
    public function setService(array $config)
    {
        $this->serviceConfig = $config;
        $this->_service = $this->createService($this->serviceConfig);
        if(!isset($this->_service))
            throw new InvalidConfigException(Yii::t('queue', "Message queue service is not initiated"));
    }

    /**
     * Any requests to set or get attributes or call methods on this class that
     * are not found are redirected to the {@link IMessageQueue} object.
     * @param string $name the attribute name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (Exception $e) {
            if(property_exists($this->_service, $name))
                return $this->_service->$name;
            else
                throw $e;
        }
    }

    /**
     * Any requests to set or get attributes or call methods on this class that
     * are not found are redirected to the {@link IMessageQueue} object.
     * @param string $name the attribute name
     * @param mixed $value the attribute value
     * @return mixed
     * @throws Exception
     */
    public function __set($name, $value)
    {
        try {
            return parent::__set($name, $value);
        } catch (Exception $e) {
            if(property_exists($this->_service, $name))
                $this->_service->$name = $value;
            else
                throw $e;
        }
    }

    /**
     * Any requests to set or get attributes or call methods on this class that
     * are not found are redirected to the {@link IMessageQueue} object.
     * @param string $name the method name
     * @param array $parameters the method parameters
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $parameters)
    {
        try {
            return parent::__call($name, $parameters);
        } catch (Exception $e) {
            if(method_exists($this->_service, $name))
                return call_user_func_array(array($this->_service, $name), $parameters);
            else
                throw $e;
        }
    }

    /**
     * Call process function in messages
     * @param string $route message route
     * @param array $data message data
     * @return bool if processed ok
     */
    public function processData($route, array $data)
    {
        $className = Inflector::camelize(str_replace('mq_','',$route)).'Message';

        /** @var Message $object */
        $object = Yii::createObject($this->messageNamespaces.'\\'.$className);

        //Check abstract class
        if (!is_a($object, Message::className())) {
            unset($object);
            return false;
        }

        return $object->processData($data);
    }

    /**
     * Initialize message queue service
     * @param array $config service config
     * @return IQueue|null retrieves message queue model on success, otherwise null
     */
    protected function createService($config)
    {
        //Check service presence in config
        if (!isset($config)) {
            return null;
        }

        //Check presence config for service
        if(!is_array($config)) {
            return null;
        }

        //Check if class specified
        if (!isset($config['class'])) {
            return null;
        }

        return Yii::createObject($config);
    }
}

