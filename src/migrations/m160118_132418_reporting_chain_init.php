<?php

use yii\db\Migration;
use DevGroup\DeferredTasks\models\DeferredQueue;

class m160118_132418_reporting_chain_init extends Migration
{
    public function up()
    {
        $this->addColumn(
            DeferredQueue::tableName(),
            'next_task_id',
            $this->integer()->notNull()->defaultValue(0)
        );
    }

    public function down()
    {
        $this->dropColumn(DeferredQueue::tableName(), 'next_task_id');
    }
}