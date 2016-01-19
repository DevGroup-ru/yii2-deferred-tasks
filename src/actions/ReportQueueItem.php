<?php

namespace DevGroup\DeferredTasks\actions;

use DevGroup\DeferredTasks\helpers\DeferredHelper;
use DevGroup\DeferredTasks\models\DeferredQueue;
use DevGroup\DeferredTasks\structures\ReportingTaskResponse;
use Yii;
use yii\base\Action;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ReportQueueItem extends Action
{
    public function run($queueItemId, $lastFseekPosition = 0)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var DeferredQueue $item */
        $item = DeferredQueue::loadModel($queueItemId, false, false, 0, new NotFoundHttpException());

        if ($item->status === DeferredQueue::STATUS_SCHEDULED || $item->status === DeferredQueue::STATUS_DISABLED) {
            return new ReportingTaskResponse([
                'status' => $item->status
            ]);
        } else {
            if (empty($item->output_file)) {
                return new ReportingTaskResponse([
                    'error' => true,
                    'errorMessage' => Yii::t('deferred-tasks', 'Field output_file is empty for queue item.'),
                ]);
            }
            if (file_exists($item->output_file) && is_readable($item->output_file)) {

                $fp = fopen($item->output_file, 'r');
                $stat = fstat($fp);
                $fseekStatus = fseek($fp, $lastFseekPosition);

                if ($fseekStatus !== 0) {
                    fclose($fp);
                    return new ReportingTaskResponse([
                        'error' => true,
                        'errorMessage' => Yii::t('deferred-tasks', 'Unable to fseek file.'),
                        'lastFseekPosition' => $lastFseekPosition,
                        'newOutput' => '',
                        'status' => $item->status,
                    ]);
                }

                $bytesToRead = $stat['size'] - $lastFseekPosition;
                if ($bytesToRead > 0) {
                    $data = fread($fp, $bytesToRead);
                } else {
                    $data = '';
                }
                $lastFseekPosition += $bytesToRead;
                fclose($fp);
                //firing next task in chain if exists and if current is successfully finished
                if ($item->status == DeferredQueue::STATUS_COMPLETE && $item->next_task_id != 0) {
                    DeferredHelper::runImmediateTask($item->next_task_id);
                    $item->status = DeferredQueue::STATUS_RUNNING;
                }
                return new ReportingTaskResponse([
                    'status' => $item->status,
                    'error' => false,
                    'newOutput' => $data,
                    'lastFseekPosition' => $lastFseekPosition,
                    'taskStatusCode' => $item->exit_code,
                ]);


            } else {
                return new ReportingTaskResponse([
                    'error' => true,
                    'errorMessage' => Yii::t('deferred-tasks', 'Error accessing output file.'),
                    'lastFseekPosition' => $lastFseekPosition,
                    'newOutput' => '',
                    'status' => $item->status,
                ]);
            }
        }
    }
}
