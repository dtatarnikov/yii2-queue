<?php

use yii\db\Migration;

/**
 * Required migration to work with DbQueue
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class m140408_172738_create_table extends Migration
{
    public function up()
    {
        $this->createTable('{{%queue_message}}', [
            'id' => $this->primaryKey(),
            'route' => $this->string('512')->notNull(),
            'message' => $this->text(),
            'time' => $this->integer()->defaultValue(0),
        ]);

        $this->createIndex('IDX_QUEUE_MSG_ROUTE', '{{%queue_message}}', 'route');
    }

    public function down()
    {
        $this->dropIndex('IDX_QUEUE_MSG_ROUTE', '{{%queue_message}}');

        $this->dropTable('{{%queue_message}}');
    }
}