<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\tasks;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\TaskDefinitions;
use humhub\modules\tasks\models\lists\TaskList;
use Yii;

class TaskListController extends BaseController
{
    public function actionIndex($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();
        $results = [];
        $query = TaskList::findOverviewLists($container);

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $list) {
            $results[] = TaskDefinitions::getTaskList($list);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        $list = TaskList::findOne(['id' => $id]);

        if ($list === null) {
            return $this->returnError(404, 'Task list not found!');
        }

        return TaskDefinitions::getTaskList($list);
    }

    public function actionCreate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $taskList = new TaskList($container);

        if($taskList->load(Yii::$app->request->post()) && $taskList->save()) {
            return TaskDefinitions::getTaskList($taskList);
        }

        if ($taskList->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'errors' => $taskList->getErrors(),
            ]);
        } else {
            Yii::error('Could not create validated task list.', 'api');
            return $this->returnError(500, 'Internal error while save task list!');
        }
    }

    public function actionUpdate($id)
    {
        $taskList = TaskList::findOne(['id' => $id]);

        if ($taskList === null) {
            return $this->returnError(404, 'Task list not found!');
        }

        if($taskList->load(Yii::$app->request->post()) && $taskList->save()) {
            return TaskDefinitions::getTaskList($taskList);
        }

        if ($taskList->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'errors' => $taskList->getErrors(),
            ]);
        } else {
            Yii::error('Could not update validated task list.', 'api');
            return $this->returnError(500, 'Internal error while update task list!');
        }
    }

    public function actionDelete($id)
    {
        $list = TaskList::findOne(['id' => $id]);
        if ($list === null) {
            return $this->returnError(404, 'Task list not found!');
        }

        if ($list->delete()) {
            return $this->returnSuccess('Task list successfully deleted!');
        }

        return $this->returnError(500, 'Internal error while delete task list!');
    }
}