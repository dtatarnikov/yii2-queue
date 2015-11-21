<?php
namespace strong2much\queue\messages;

use Yii;
use yii\base\Model;
use yii\di\Instance;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Json;
use ReflectionClass;
use strong2much\queue\services\DummyQueue;
use strong2much\queue\services\IQueue;
use strong2much\queue\QueueManager;

/**
 * Message is an abstract model for working with messages for queue service.
 * All messages should be inherited from this abstract class.
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
abstract class Message extends Model
{
    /**
     * @var array attribute names
     */
    private static $_names = [];
    /**
     * @var QueueManager pointer to queue manager
     */
    private $_manager;

    /**
     * @return string name for queue manager
     */
    public static function managerName()
    {
        return 'queue';
    }

    /**
     * Initializes the model.
     * This method will set [[manager]] to be the 'queue' application component, if it is `null`.
     */
    public function init()
    {
        parent::init();

        $this->_manager = Instance::ensure(self::managerName(), QueueManager::className());
    }

    /**
     * @return array list of attribute names. Defaults to all public properties of the class.
     */
    public function attributeNames()
    {
        $className = get_class($this);
        if (!isset(self::$_names[$className])) {
            $class = new ReflectionClass(get_class($this));
            $names = [];
            foreach ($class->getProperties() as $property) {
                $name = $property->getName();
                if ($property->isPublic() && !$property->isStatic())
                    $names[] = $name;
            }
            return self::$_names[$className] = $names;
        }
        else
            return self::$_names[$className];
    }

    /**
     * @return QueueManager queue manager
     */
    public function getManager()
    {
        return $this->_manager;
    }

    /**
     * @return IQueue the instance of queue service
     */
    public function getService()
    {
        return $this->getManager()->getService();
    }

    /**
     * Returns the routing key of message (or it can be a queue name)
     * By default this method generate routing key based on the class name.
     * Example: class 'TestReasonMessage' --> routing key 'mq_test_reason'
     * @return string
     */
    public function routingKey()
    {
        return 'mq_' . Inflector::underscore(str_replace('Message', '', StringHelper::basename(get_class($this))));
    }

    /**
     * @return int the flags of the message
     */
    public function messageFlags()
    {
        return 0;
    }

    /**
     * @return array the attributes of the message
     */
    public function messageAttributes()
    {
        return ['delivery_mode' => 2, 'content_type' => 'application/json'];
    }

    /**
     * Makes json string from the attributes
     * @param array $attributes list of attributes to use, use all if null
     * @return string
     */
    public function getBody($attributes = null)
    {
        return Json::encode($this->getAttributes($attributes));
    }

    /**
     * Send message
     * @param boolean $runValidation whether to perform validation before send.
     * @param array   $attributes    list of attributes that need to be send. Defaults to null,
     * meaning all attributes will be send.
     * @return bool
     */
    public function send($runValidation=true, $attributes=null)
    {
        if (!$runValidation || $this->validate($attributes)) {
            if($this->getService()->sendMessage($this->getBody($attributes), $this->routingKey(), $this->messageFlags(), $this->messageAttributes())) {
                $this->afterSend();
                return true;
            }
        }

        return false;
    }

    /**
     * Calls after success sending
     */
    protected function afterSend()
    {
        if ($this->getService() instanceof DummyQueue) {
            $this->processData($this->getAttributes());
        }
    }

    /**
     * Processing action for message.
     * Add here any code that should be processing after message was read.
     * @param array $params message properties as array
     * @return bool is request processed
     */
    abstract public function processData(array $params);
}
