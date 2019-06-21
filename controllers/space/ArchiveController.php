<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\space;

use humhub\modules\rest\components\BaseController;
use humhub\modules\space\activities\SpaceArchived;
use humhub\modules\space\activities\SpaceUnArchived;
use humhub\modules\space\models\Space;
use Yii;

/**
 * Class ArchiveController
 */
class ArchiveController extends BaseController
{
    public function actionArchive($id)
    {
        $space = Space::findOne(['id' => $id]);

        if ($space === null) {
            return $this->returnError(404, 'Space not found!');
        }

        if ($space->isArchived()) {
            return $this->returnError(400, 'Space is already archived!');
        }

        if (! (Yii::$app->user->isAdmin() || $space->isSpaceOwner())) {
            return $this->returnError(401, 'You are not allowed to archive this space!');
        }

        $space->archive();

        // Create Activity when the space in archived
        SpaceArchived::instance()->from(Yii::$app->user->getIdentity())->about($space->owner)->save();

        return $this->returnSuccess('Space successfully archived!');
    }

    public function actionUnarchive($id)
    {
        $space = Space::findOne(['id' => $id]);

        if ($space === null) {
            return $this->returnError(404, 'Space not found!');
        }

        if (! $space->isArchived()) {
            return $this->returnError(400, 'Space is not archived!');
        }

        if (! (Yii::$app->user->isAdmin() || $space->isSpaceOwner())) {
            return $this->returnError(401, 'You are not allowed to unarchive this space!');
        }

        $space->unarchive();

        // Create Activity when the space in unarchived
        SpaceUnArchived::instance()->from(Yii::$app->user->getIdentity())->about($space->owner)->save();

        return $this->returnSuccess('Space successfully unarchived!');
    }
}