<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\tasks;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseContentController;
use humhub\modules\rest\definitions\TaskDefinitions;
use humhub\modules\tasks\models\forms\TaskForm;
use humhub\modules\tasks\models\Task;
use humhub\modules\tasks\permissions\CreateTask;
use humhub\modules\tasks\permissions\ManageTasks;
use Yii;

class TasksController extends BaseContentController
{
    public static $moduleId = 'tasks';

    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return Task::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var Task $contentRecord */
        return TaskDefinitions::getTask($contentRecord);
    }

    public function actionCreate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        if (! in_array(get_class($container), Yii::$app->getModule('tasks')->getContentContainerTypes()) ||
            ! $container->permissionManager->can([CreateTask::class, ManageTasks::class])) {
            return $this->returnError(403, 'You are not allowed to create task!');
        }

        $taskParams = Yii::$app->request->post('Task', []);

        $taskForm = new TaskForm([
            'cal' => isset($taskParams['cal_mode']) ? $taskParams['cal_mode'] : null,
            'taskListId' => isset($taskParams['task_list_id']) ? $taskParams['task_list_id'] : null
        ]);
        $taskForm->createNew($container);

        if (! $taskForm->task->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to edit this task!');
        }

        $requestParams = $this->prepareRequestParams(Yii::$app->request->getBodyParams(), 'TaskForm', 'Task');
        if ($taskForm->load($requestParams) && $taskForm->save()) {
            return TaskDefinitions::getTask($taskForm->task);
        }

        if ($taskForm->hasErrors() || $taskForm->task->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'taskForm' => $taskForm->getErrors(),
                'task' => $taskForm->task->getErrors(),
            ]);
        } else {
            Yii::error('Could not create validated task.', 'api');
            return $this->returnError(500, 'Internal error while save task!');
        }
    }

    public function actionUpdate($id)
    {
        $task = Task::findOne(['id' => $id]);
        if (! $task) {
            return $this->returnError(404, 'Task not found!');
        }

        $taskForm = new TaskForm(['task' => $task]);

        if(! $taskForm->task->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to update this task!');
        }
        
        $requestParams = $this->prepareRequestParams(Yii::$app->request->getBodyParams(), 'TaskForm', 'Task');
        if ($taskForm->load($requestParams) && $taskForm->save()) {
            return TaskDefinitions::getTask($taskForm->task);
        }

        if ($taskForm->hasErrors() || $taskForm->task->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'taskForm' => $taskForm->getErrors(),
                'task' => $taskForm->task->getErrors(),
            ]);
        } else {
            Yii::error('Could not update validated task.', 'api');
            return $this->returnError(500, 'Internal error while save task!');
        }
    }

    public function actionProcessed($id)
    {
        $task = Task::findOne(['id' => $id]);
        if (! $task) {
            return $this->returnError(404, 'Task not found!');
        }

        $status = Yii::$app->request->post('status', null);

        if(!$task->state->canProceed($status)) {
            return $this->returnError(403, 'You are not allowed to change status of this task!');
        }

        if ($task->state->proceed($status)) {
            return $this->returnSuccess('Status successfully changed.');
        } else {
            return $this->returnError(400, 'Invalid status!');
        }
    }

    public function actionRevert($id)
    {
        $task = Task::findOne(['id' => $id]);
        if (! $task) {
            return $this->returnError(404, 'Task not found!');
        }

        if(!$task->state->canRevert(Task::STATUS_PENDING)) {
            return $this->returnError(403, 'You are not allowed to revert this task!');
        }

        if ($task->state->revert(Task::STATUS_PENDING)) {
            return $this->returnSuccess('Task successfully reverted.');
        } else {
            return $this->returnError(400, 'Invalid status!');
        }
    }
}