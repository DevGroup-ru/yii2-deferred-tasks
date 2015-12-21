<?php

use yii\db\Migration;

class m151221_105000_reporting_tasks extends Migration
{
    public function up()
    {
        $this->addColumn('{{%deferred_queue}}', 'output_file', $this->string());
        $this->addColumn('{{%deferred_queue}}', 'exit_code', $this->integer()->defaultValue(null));
        $this->addColumn('{{%deferred_queue}}', 'delete_after_run', $this->boolean()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn('{{%deferred_queue}}', 'output_file');
        $this->dropColumn('{{%deferred_queue}}', 'exit_code');
        $this->dropColumn('{{%deferred_queue}}', 'delete_after_run');
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
