<?php
namespace strong2much\queue\services;

use Yii;
use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use strong2much\aws\Aws;

/**
 * AmazonQueue represents the Amazon Simple Queue Service (SQS)
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class AmazonQueue implements IQueue
{
    /**
     * @var array configuration for amazon sqs
     * [
     *  'region' => '',
     *  'version' => 'latest',
     *  'credentials' => [
     *      'key' => '',
     *      'secret' => ''
     *  ]
     * ]
     */
    public $config = [];

    /**
     * @var SqsClient sqs client
     */
    private $_sqs;

    /**
     * Base initiation of queue service
     */
    public function init()
    {
        $aws = new Aws();
        $aws->setConfig($this->config);

        $sdk = $aws->getSdk();

        $this->_sqs =$sdk->createSqs();
    }

    /**
     * Send message to queue
     * @param string $message message to publish
     * @param string $route queue name
     * @param integer $flags message flags
     * @param array $attributes message attributes
     * @return boolean true on success, otherwise false
     */
    public function sendMessage($message, $route, $flags, $attributes)
    {
        try {
            $queue = $this->_sqs->getQueueUrl(['QueueName'=>$route]);
        } catch(SqsException $e) {
            //TODO check this error
            if($e->getAwsErrorCode()=='AWS.SimpleQueueService.NonExistentQueue') {
                $queue = $this->_sqs->createQueue(['QueueName'=>$route]);
            }
        }

        if(isset($queue)) {
            $queueUrl = $queue->get('QueueUrl');

            try {
                $this->_sqs->sendMessage(['QueueUrl'=>$queueUrl, 'MessageBody'=>$message]);
                return true;
            } catch(SqsException $e) {
                return false;
            }
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
        try {
            $queue = $this->_sqs->getQueueUrl(['QueueName'=>$route]);
        } catch(SqsException $e) {
            //Not found queue
            return false;
        }

        if(isset($queue)) {
            $queueUrl = $queue->get('QueueUrl');

            try {
                $result = $this->_sqs->receiveMessage(['QueueUrl'=>$queueUrl,'MaxNumberOfMessages'=>1]);
                $messages = $result->search('Messages');
                if(isset($messages) && count($messages)>0) {
                    $entries = [];
                    foreach ($messages as $message) {
                        $msgBody = $message['Body'];

                        $entry = [];
                        $entry['Id'] = $message['MessageId'];
                        $entry['ReceiptHandle'] = $message['ReceiptHandle'];
                        $entries[] = $entry;
                    }

                    $this->_sqs->deleteMessageBatch(['QueueUrl'=>$queueUrl,'Entries'=>$entries]);

                    if (empty($msgBody)) {
                        //Empty message
                        return false;
                    }

                    return $msgBody;
                } else {
                    //Not found message
                    return false;
                }
            } catch(SqsException $e) {
                return false;
            }
        }

        return false;
    }
} 