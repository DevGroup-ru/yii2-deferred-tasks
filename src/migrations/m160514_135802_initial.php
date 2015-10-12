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
            'id' => Schema::TYPE_PK,
            'deferred_group_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'user_id' => Schema::TYPE_INTEGER . ' NULL',
            'initiated_date' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'is_repeating_task' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 0',
            'cron_expression' => Schema::TYPE_STRING . ' NULL',
            'next_start' => Schema::TYPE_TIMESTAMP . ' NULL',
            'status' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 0',
            'last_run_date' => Schema::TYPE_TIMESTAMP . ' NULL',
            'console_route' => Schema::TYPE_STRING . ' NULL',
            'cli_command' => Schema::TYPE_STRING . ' NULL',
            'command_arguments' => Schema::TYPE_TEXT . ' NULL',
            'notify_initiator' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
            'notify_roles' => Schema::TYPE_STRING . ' NULL',
            'email_notification' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
        ], $tableOptions);

        $this->createTable('{{%deferred_group}}', [
            'id' => Schema::TYPE_PK,
            'name' => Schema::TYPE_STRING . ' NULL',
            'allow_parallel_run' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 0',
            'run_last_command_only' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 0',
            'notify_initiator' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
            'notify_roles' => Schema::TYPE_STRING . ' NULL',
            'email_notification' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
            'group_notifications' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
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
