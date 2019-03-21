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
use humhub\modules\space\models\Space;
use humhub\modules\space\modules\manage\models\AdvancedSettingsSpace;
use humhub\modules\space\permissions\CreatePrivateSpace;
use humhub\modules\space\permissions\CreatePublicSpace;
use Yii;

/**
 * Class SpaceController
 */
class SpaceController extends BaseController
{

    public function actionIndex()
    {
        $results = [];
        $query = Space::find();

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $space) {
            $results[] = SpaceDefinitions::getSpace($space);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        $space = Space::findOne(['id' => $id]);

        if ($space === null) {
            return $this->returnError(404, 'Space not found!');
        }

        return SpaceDefinitions::getSpace($space);
    }

    public function actionCreate()
    {
        if (! Yii::$app->user->permissionmanager->can(new CreatePublicSpace) && ! Yii::$app->user->permissionmanager->can(new CreatePrivateSpace)) {
            return $this->returnError(401, 'You are not allowed to create spaces!');
        }

        $module = Yii::$app->getModule('space');

        $space = new Space();
        $space->scenario = Space::SCENARIO_CREATE;
        $space->visibility = $module->settings->get('defaultVisibility', Space::VISIBILITY_REGISTERED_ONLY);
        $space->join_policy = $module->settings->get('defaultJoinPolicy', Space::JOIN_POLICY_APPLICATION);
        $space->color = RandomColor::one(['luminosity' => 'dark']);

        $space->load(Yii::$app->request->getBodyParams(), '');
        $space->validate();

        if($space->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'space' => $space->getErrors(),
            ]);
        }

        if ($space->save()) {
            return SpaceDefinitions::getSpace($space);
        }

        Yii::error('Could not create validated space.', 'api');
        return $this->returnError(500, 'Internal error while save space!');
    }

    public function actionUpdate($id)
    {
        $space = AdvancedSettingsSpace::findOne(['id' => $id]);

        if ($space === null) {
            return $this->returnError(404, 'Space not found!');
        }

        if (! $space->isAdmin()) {
            return $this->returnError(401, 'You are not allowed to manage this space!');
        }

        $space->scenario = 'edit';
        $space->load(Yii::$app->request->getBodyParams(), '');
        $space->validate();

        if($space->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'space' => $space->getErrors(),
            ]);
        }

        if ($space->save()) {
            return SpaceDefinitions::getSpace($space);
        }

        Yii::error('Could not update space.', 'api');
        return $this->returnError(500, 'Internal error while update space!');
    }

    public function actionDelete($id)
    {
        $space = Space::findOne(['id' => $id]);
        if ($space === null) {
            return $this->returnError(404, 'Space not found!');
        }

        if (! $space->canDelete()) {
            return $this->returnError(401, 'You are not allowed to delete this space!');
        }

        if ($space->delete()) {
            return $this->returnSuccess('Space successfully deleted!');
        }

        return $this->returnError(500, 'Internal error while delete space!');
    }
}