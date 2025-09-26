<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\user;

use humhub\modules\admin\permissions\ManageGroups;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\User;
use Yii;

/**
 * Class GroupController
 */
class GroupController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['permissions' => [ManageGroups::class]],
        ];
    }

    public function actionIndex()
    {
        $results = [];
        $query = Group::find();

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $group) {
            $results[] = UserDefinitions::getGroup($group);
        }
        return $this->returnPagination($query, $pagination, $results);
    }


    public function actionView($id)
    {
        $group = Group::findOne(['id' => $id]);
        if ($group === null) {
            return $this->returnError(404, 'Group not found!');
        }

        return UserDefinitions::getGroup($group);
    }

    public function actionUpdate($id)
    {
        $group = Group::findOne(['id' => $id]);

        if ($group === null) {
            return $this->returnError(404, 'Group not found!');
        }
        $group->load(Yii::$app->request->getBodyParams(), '');
        $group->validate();

        if ($group->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'group' => $group->getErrors(),
            ]);
        }

        if ($group->save()) {
            return UserDefinitions::getGroup($group);
        }

        Yii::error('Could not update group.', 'rest');
        return $this->returnError(500, 'Internal error while update group!');
    }

    public function actionCreate()
    {
        $group = new Group();
        $group->load(Yii::$app->request->getBodyParams(), '');
        if ($group->validate() && $group->hasErrors()) {
            return $this->returnError(400, 'Validation failed', $group->getErrors());
        }

        if ($group->save()) {
            return $this->actionView($group->id);
        }

        Yii::error('Could not create validated group.', 'api');
        return $this->returnError(500, 'Internal error while save group!');
    }

    public function actionDelete($id)
    {
        $group = Group::findOne(['id' => $id]);
        if ($group === null) {
            return $this->returnError(404, 'Group not found!');
        }

        if ($group->delete()) {
            return $this->returnSuccess('Group successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete group!');
    }


    public function actionMembers($id)
    {
        $results = [];
        $query = GroupUser::find()->where(['group_id' => $id])->joinWith('user');

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $groupUser) {
            $results[] = UserDefinitions::getUserShort($groupUser->user);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionMemberAdd($id)
    {
        $group = Group::findOne(['id' => $id]);
        if ($group === null) {
            return $this->returnError(404, 'Group not found!');
        }

        $userId = Yii::$app->request->get('userId');
        $user = User::findOne(['id' => $userId]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        if ($group->isMember($userId)) {
            return $this->returnError(400, 'User is already a member of the group!');
        }

        if ($group->addUser($userId, !(empty(Yii::$app->request->get('isManager'))))) {
            return $this->returnSuccess('Member added!');
        }

        return $this->returnError(400, 'Could not add member!');
    }

    public function actionMemberRemove($id)
    {
        $group = Group::findOne(['id' => $id]);
        if ($group === null) {
            return $this->returnError(404, 'Group not found!');
        }

        $userId = Yii::$app->request->get('userId');
        $user = User::findOne(['id' => $userId]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        if (!$group->isMember($userId)) {
            return $this->returnError(400, 'User is not a member of the group!');
        }

        if ($group->removeUser($userId)) {
            return $this->returnSuccess('Member removed!');
        }

        return $this->returnError(400, 'Could not remove member!');
    }

}
