<?php

namespace DevGroup\DeferredTasks\models;

use \Cron\CronExpression;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Symfony\Component\Process\Process;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%deferred_queue}}".
 *
 * @property integer $id
 * @property integer $deferred_group_id
 * @property integer $user_id
 * @property string $initiated_date
 * @property boolean $is_repeating_task
 * @property string $cron_expression
 * @property string $next_start
 * @property integer $status
 * @property string $last_run_date
 * @property string $console_route
 * @property string $cli_command
 * @property string $command_arguments
 * @property integer $notify_initiator
 * @property string $notify_roles
 * @property integer $email_notification
 * @property string $output_file
 * @property integer $exit_code
 * @property boolean $delete_after_run
 * @property integer $next_task_id
 */
class DeferredQueue extends ActiveRecord
{
    const STATUS_DISABLED = 0;
    const STATUS_SCHEDULED = 1;
    const STATUS_RUNNING = 2;
    const STATUS_FAILED = 3;
    const STATUS_COMPLETE = 4;
    const STATUS_SUCCESS_AND_NEXT = 5;

    /** @var Process  */
    private $process = null;

    use TagDependencyTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deferred_queue}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['deferred_group_id', 'user_id', 'status', 'next_task_id'], 'integer'],
            [['initiated_date', 'next_start', 'last_run_date', 'exit_code'], 'safe'],
            [['cron_expression', 'console_route', 'cli_command', 'notify_roles'], 'string', 'max' => 255],
            [['command_arguments', 'output_file'], 'string'],
            [
                [
                    'is_repeating_task',
                    'notify_initiator',
                    'email_notification',
                    'delete_after_run'
                ],
                'filter',
                'filter'=>'boolval'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('deferred-tasks', 'ID'),
            'deferred_group_id' => Yii::t('deferred-tasks', 'Deferred group ID'),
            'user_id' => Yii::t('deferred-tasks', 'User ID'),
            'initiated_date' => Yii::t('deferred-tasks', 'Initiated date'),
            'is_repeating_task' => Yii::t('deferred-tasks', 'Is repeating task'),
            'cron_expression' => Yii::t('deferred-tasks', 'Cron expression'),
            'next_start' => Yii::t('deferred-tasks', 'Next start date'),
            'status' => Yii::t('deferred-tasks', 'Status'),
            'last_run_date' => Yii::t('deferred-tasks', 'Last run date'),
            'console_route' => Yii::t('deferred-tasks', 'Console route'),
            'cli_command' => Yii::t('deferred-tasks', 'Cli command'),
            'command_arguments' => Yii::t('deferred-tasks', 'Command arguments'),
            'notify_initiator' => Yii::t('deferred-tasks', 'Notify initiator'),
            'notify_roles' => Yii::t('deferred-tasks', 'Notify roles'),
            'email_notification' => Yii::t('deferred-tasks', 'Email notification'),
            'output_file' => Yii::t('deferred-tasks', 'Output file'),
            'exit_code' => Yii::t('deferred-tasks', 'Exit code'),
            'delete_after_run' => Yii::t('deferred-tasks', 'Delete after run'),
        ];
    }

    /**
     * Performs some afterFind stuff like casting variables to correct type
     */
    public function afterFind ()
    {
        parent::afterFind();
        $boolArguments = [
            'is_repeating_task',
            'notify_initiator',
            'email_notification',
            'delete_after_run',
        ];
        foreach ($boolArguments as $argument) {
            $this->$argument = boolval($this->$argument);
        }
    }

    /**
     * Returns corresponding DeferredGroup model instance
     * @return DeferredGroup|null
     */
    public function getGroup()
    {
        return
            isset($this->deferred_group_id)
            ? DeferredGroup::findById($this->deferred_group_id)
            : null;
    }

    /**
     * @return null|Process Returns process object
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param Process $process sets process object
     */
    public function setProcess(&$process)
    {
        $this->process = $process;
    }

    /**
     * Sets next_start field to the next future timstamp when we should run again repeating task.
     * If task is non-repeating and cron_expression is empty - false returned
     */
    public function planNextRun()
    {
        if ($this->is_repeating_task && !empty($this->cron_expression)) {
            $this->status = DeferredQueue::STATUS_SCHEDULED;
            $cron = CronExpression::factory($this->cron_expression);
            $this->next_start = date('Y-m-d H:i:s', $cron->getNextRunDate()->getTimestamp());
            return true;
        } else {
            return false;
        }
    }

    /**
     * Schedules next launch or deletes queue item if it is not repeatable
     *
     * @return bool Result of completing
     * @throws \Exception
     */
    public function complete()
    {
        $this->last_run_date = date('Y-m-d H:i:s', time());
        if (0 != $this->next_task_id) {
            $this->status = DeferredQueue::STATUS_SUCCESS_AND_NEXT;
        } else {
            $this->status = DeferredQueue::STATUS_COMPLETE;
        }

        if ($this->planNextRun() === true) {
            return $this->save();
        } else {
            if ($this->delete_after_run) {
                return $this->delete() !== false;
            } else {
                return $this->save();
            }
        }
    }

    /**
     * @return bool returns true if command was successfully executed
     */
    public function isSuccess()
    {
        if (isset($this->process)) {
            return $this->process->getExitCode() === 0;
        } else {
            return false;
        }
    }

    /**
     * @param integer $currentTime
     * @param array   $ids Filter by ids
     * @return DeferredQueue[]
     */
    public static function getNextTasks($currentTime, $ids = null)
    {
        $currentTime = date("Y-m-d H:i:s", $currentTime);

        $query = DeferredQueue::find()
            ->where(['status' => DeferredQueue::STATUS_SCHEDULED]);

        if ($ids !== null) {
            $query = $query->andWhere(['in', 'id', (array) $ids]);
        } else {
            $query = $query->andWhere('next_start <= :next_start', [':next_start' => $currentTime]);
        }

        return $query
            ->orderBy(['id'=>SORT_ASC])
            ->all();
    }
}
