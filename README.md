# yii2-queue

This extension is using for works with different message queue services.

Installation
------------

Install package by composer
```composer
{
    "require": {
       "strong2much/yii2-queue": "dev-master"
    }
}

Or

$ composer require strong2much/yii2-queue "dev-master"
```

Use the following code in your configuration file. You can use different services
```php
'queue' => [
    'class' => 'strong2much\queue\managers\QueueManager'
    'service' => [
        'class' => 'strong2much\queue\services\DummyQueue',
    ]
]
```

Then you can send messages like so:
```php
$msg = new \strong2much\queue\messages\TestMessage();
$msg->id = 1;
$msg->message = "Some text";
if($msg->send()) {
    echo "OK";
}
```


Then you can use manager to handle your messages
```php
//To receive messages
$route = 'mq_test'; //just for example
$message = Yii::$app->queue->receiveMessage($route); //this will get first  one message from the queue

//if you specify some processing function in message model,
//then you can call it like so
if($message !== false) {
	Yii::app()->queue->processData($route, \yii\base\helpers\Json::decode($message));
}
```

In order to use DbQueue as your service, you will need to apply the provided migrations.
