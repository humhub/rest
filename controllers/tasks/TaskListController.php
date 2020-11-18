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
use humhub\modules\space\models\Space;
use humhub\modules\tasks\models\lists\TaskList;
use humhub\modules\user\models\User;
use Yii;
use yii\web\HttpException;

class TaskListController extends BaseController
{
    public function actionIndex($containerId)
    {
        $container = $this->getContainerById($containerId);

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

        $this->checkContainerAccess($list->getContainer());

        return TaskDefinitions::getTaskList($list);
    }

    public function actionCreate($containerId)
    {
        $container = $this->getContainerById($containerId);

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

        $this->checkContainerAccess($taskList->getContainer());

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

        $this->checkContainerAccess($list->getContainer());

        if ($list->delete()) {
            return $this->returnSuccess('Task list successfully deleted!');
        }

        return $this->returnError(500, 'Internal error while delete task list!');
    }


    /**
     * Get Container by ID
     *
     * @param integer $id
     * @return ContentContainerActiveRecord
     * @throws \yii\db\IntegrityException
     * @throws HttpException
     */
    protected function getContainerById($id)
    {
        $containerRecord = ContentContainer::findOne(['id' => $id]);
        if ($containerRecord === null) {
            throw new HttpException(404, 'Content container not found!');
        }

        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $this->checkContainerAccess($container);

        return $container;
    }


    /**
     * Check access of current User to the Container
     *
     * @param ContentContainerActiveRecord
     * @throws HttpException
     */
    protected function checkContainerAccess($container)
    {
        if (Yii::$app->user->isAdmin()) {
            return;
        }

        if ($container instanceof User && $container->id != Yii::$app->user->id) {
            throw new HttpException(401, 'You have no access to the user container!');
        }

        if ($container instanceof Space && !$container->isAdmin() && !$container->isMember(Yii::$app->user->id)) {
            throw new HttpException(401, 'You have no access to the space container!');
        }
    }
}