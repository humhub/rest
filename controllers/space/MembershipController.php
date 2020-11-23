<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\space;

use Colors\RandomColor;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\space\modules\manage\models\AdvancedSettingsSpace;
use humhub\modules\space\permissions\CreatePrivateSpace;
use humhub\modules\space\permissions\CreatePublicSpace;
use Yii;

/**
 * Class MembershipController
 */
class MembershipController extends BaseController
{


    public function actionIndex($spaceId)
    {
        $space = Space::findOne(['id' => (int)$spaceId]);
        if ($space === null) {
            return $this->returnError(404, 'Space  not found!');
        }
        if (!$space->isAdmin()) {
            return $this->returnError(400, 'You cannot administer this space!');
        }

        $results = [];
        $query = Membership::find()->where(['space_id' => $space->id]);

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $membership) {
            /** @var Membership $membership */
            $results[] = SpaceDefinitions::getSpaceMembership($membership);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionCreate($spaceId, $userId)
    {
        $space = Space::findOne(['id' => (int)$spaceId]);
        if ($space === null) {
            return $this->returnError(404, 'Space  not found!');
        }
        if (!$space->isAdmin()) {
            return $this->returnError(400, 'You cannot administer this space!');
        }

        $space->addMember($userId, Yii::$app->request->get('canLeave', true), Yii::$app->request->get('silent', false));

        return $this->returnSuccess('Member added!');
    }

    public function actionDelete($spaceId, $userId)
    {
        $space = Space::findOne(['id' => (int)$spaceId]);
        if ($space === null) {
            return $this->returnError(404, 'Space  not found!');
        }
        if (!$space->isAdmin()) {
            return $this->returnError(400, 'You cannot administer this space!');
        }

        if ($space->removeMember($userId) !== false) {
            return $this->returnSuccess('Member deleted');
        }
        return $this->returnError(404, 'Member NOT deleted!');

    }

    public function actionRole($spaceId, $userId)
    {
        $space = Space::findOne(['id' => (int)$spaceId]);
        if ($space === null) {
            return $this->returnError(404, 'Space not found!');
        }
        if (!$space->isAdmin()) {
            return $this->returnError(400, 'You cannot administer this space!');
        }

        $membership = $space->getMembership($userId);
        if ($membership === null) {
            return $this->returnError(404, 'Membership not found!');
        }

        $newRole = Yii::$app->request->get('role');
        if (!in_array($newRole, ['admin', 'moderator', 'member'])) {
            return $this->returnError(400, 'Invalid role given!');
        }

        $membership->group_id = $newRole;
        $membership->save();

        return $this->returnSuccess('Member updated!');
    }


}