<?php

use yii\db\Schema;
use yii\db\Migration;

class m160514_135802_initial extends Migration
{
    public function up()
    {
        $tableOptions = '';
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%deferred_queue}}', [
            'id' => $this->primaryKey(),
            'deferred_group_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer(),
            'initiated_date' => $this->dateTime(),
            'is_repeating_task' => $this->boolean()->defaultValue(0)->notNull(),
            'cron_expression' => $this->string(),
            'next_start' => $this->string(),
            'status' => $this->smallInteger()->defaultValue(0)->notNull(),
            'last_run_date' => $this->timestamp(),
            'console_route' => $this->string(),
            'cli_command' => $this->string(),
            'command_arguments' => $this->text(),
            'notify_initiator' => $this->boolean()->defaultValue(1)->notNull(),
            'notify_roles' => $this->string(),
            'email_notification' => $this->boolean()->defaultValue(1)->notNull(),
        ], $tableOptions);

        $this->createTable('{{%deferred_group}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'allow_parallel_run' => $this->boolean()->defaultValue(0)->notNull(),
            'run_last_command_only' => $this->boolean()->defaultValue(0)->notNull(),
            'notify_initiator' => $this->boolean()->defaultValue(1)->notNull(),
            'notify_roles' => $this->string(),
            'email_notification' => $this->boolean()->defaultValue(1)->notNull(),
            'group_notifications' => $this->boolean()->defaultValue(1)->notNull(),
        ], $tableOptions);

        $this->createIndex('by_status', '{{%deferred_queue}}', [
            'status',
            'next_start',
        ]);
    }

    public function down()
    {
        $this->dropTable('{{%deferred_queue}}');
        $this->dropTable('{{%deferred_group}}');
    }

}
