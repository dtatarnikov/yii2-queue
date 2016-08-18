<?php
namespace strong2much\queue\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%queue_message}}".
 *
 * The followings are the available columns in table '{{%queue_message}}':
 * @property integer $id
 * @property string $route - message route
 * @property string $message - message content
 * @property integer $time - unix time
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class QueueMessage extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public static function tableName()
	{
		return '{{%queue_message}}';
	}

    /**
     * @return array the behavior configurations.
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'time',
                'updatedAtAttribute' => false,
            ]
        ];
    }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return [
			[['route', 'message'], 'required'],
			['route', 'string', 'max'=>128],
			['message', 'string'],
	            	['time', 'integer'],
        	];
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'route' => Yii::t('queue', 'Route'),
			'message' => Yii::t('queue', 'Message'),
			'time' => Yii::t('queue', 'Time'),
		];
	}
}
