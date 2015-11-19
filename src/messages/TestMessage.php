<?php
namespace strong2much\messages;

use Yii;
use yii\helpers\Json;

/**
 * TestMessage is an test message model to show basic principal of configuration.
 * All classes should named as {name}Message. It is important!
 * DO NOT use it on your real application.
 *
 * @package  App.models.messages
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class TestMessage extends Message
{
    public $id;
    public $message;
    public $timestamp;

    /**
     * Returns the validation rules for attributes.
     * @return array  validation rules to be applied when {@link validate()} is called.
     */
    public function rules()
    {
        return [
            [['id', 'message'], 'required'],
            ['id', 'integer'],
            ['timestamp', 'default', 'value' => time()],
        ];
    }

    /**
     * Processing action for message.
     * Add here any code that should be processing after message was read.
     * @param array $params message properties as array
     * @return bool is request processed
     */
    public function processData(array $params)
    {
        Yii::info(Json::encode($params));

        return true;
    }
}
