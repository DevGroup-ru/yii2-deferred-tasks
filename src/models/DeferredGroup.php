<?php

namespace DevGroup\DeferredTasks\models;

use devgroup\TagDependencyHelper\ActiveRecordHelper;
use Yii;
use yii\caching\TagDependency;

/**
 * This is the model class for table "{{%deferred_group}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $allow_parallel_run
 * @property integer $run_last_command_only
 * @property integer $notify_initiator
 * @property string $notify_roles
 * @property integer $email_notification
 * @property integer $group_notifications
 */
class DeferredGroup extends \yii\db\ActiveRecord
{
    public static $identity_map = [];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => ActiveRecordHelper::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deferred_group}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['allow_parallel_run', 'run_last_command_only', 'notify_initiator', 'email_notification', 'group_notifications'], 'integer'],
            [['name', 'notify_roles'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'allow_parallel_run' => Yii::t('app', 'Allow Parallel Run'),
            'run_last_command_only' => Yii::t('app', 'Run Last Command Only'),
            'notify_initiator' => Yii::t('app', 'Notify Initiator'),
            'notify_roles' => Yii::t('app', 'Notify Roles'),
            'email_notification' => Yii::t('app', 'Email Notification'),
            'group_notifications' => Yii::t('app', 'Group Notifications'),
        ];
    }

    /**
     * Finds DeferredGroup by ID with use of identity map and application cache
     * @param integer|string $id
     * @return DeferredGroup|null
     */
    public static function findById($id)
    {
        $id = intval($id);

        if (isset(static::$identity_map[$id]) === false) {
            $cacheKey = static::tableName() . ':' . $id;

            static::$identity_map[$id] = Yii::$app->cache->get($cacheKey);
            if (is_object(static::$identity_map[$id]) === false) {
                static::$identity_map[$id] = static::findOne($id);
                Yii::$app->cache->set(
                    $cacheKey,
                    static::$identity_map[$id],
                    86400,
                    new TagDependency([
                        'tags' => [
                            ActiveRecordHelper::getObjectTag(static::className(), $id)
                        ]
                    ])
                );
            }

        }
        return static::$identity_map[$id];
    }
}
