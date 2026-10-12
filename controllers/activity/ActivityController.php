<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\activity;

use humhub\modules\activity\models\Activity;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\ActivityDefinitions;
use Yii;

class ActivityController extends BaseController
{
    public function actionIndex()
    {
        $results = [];

        $query = Activity::find()
            ->where(['!=', Activity::tableName() . '.created_by', Yii::$app->user->id])
            ->orderBy([Activity::tableName() . '.created_at' => SORT_DESC])
            ->visible()
            ->subscribedContentContainers(Yii::$app->user->identity);

        $pagination = $this->handlePagination($query, 10);
        foreach ($query->all() as $activity) {
            $results[] = ActivityDefinitions::getActivity($activity);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        $activity = Activity::find()
            ->where([Activity::tableName() . '.id' => $id])
            ->visible()
            ->subscribedContentContainers(Yii::$app->user->identity)
            ->one();

        if (!$activity instanceof Activity) {
            return $this->returnError(404, 'Activity not found');
        }

        return ActivityDefinitions::getActivity($activity);
    }

    public function actionFindByContainer($containerId)
    {
        $results = [];

        $contentContainer = ContentContainer::findOne(['id' => $containerId]);
        if ($contentContainer === null) {
            return $this->returnError(404, 'Content container not found!');
        }

        $query = Activity::find()
            ->andWhere(['!=', Activity::tableName() . '.created_by', Yii::$app->user->id])
            ->orderBy([Activity::tableName() . '.created_at' => SORT_DESC])
            ->visible()
            ->contentContainer($contentContainer, Yii::$app->user->identity);

        $pagination = $this->handlePagination($query, 10);
        foreach ($query->all() as $activity) {
            $results[] = ActivityDefinitions::getActivity($activity);
        }
        return $this->returnPagination($query, $pagination, $results);
    }
}
