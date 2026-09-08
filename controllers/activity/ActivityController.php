<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\activity;

use humhub\modules\activity\models\Activity;
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
            ->visible();

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
            ->one();

        if (!$activity instanceof Activity) {
            return $this->returnError(404, 'Activity not found');
        }

        return ActivityDefinitions::getActivity($activity);
    }

    public function actionFindByContainer($containerId)
    {
        $results = [];

        $query = Activity::find()
            ->where([Activity::tableName() . '.contentcontainer_id' => $containerId])
            ->andWhere(['!=', Activity::tableName() . '.created_by', Yii::$app->user->id])
            ->orderBy([Activity::tableName() . '.created_at' => SORT_DESC])
            ->visible();

        $pagination = $this->handlePagination($query, 10);
        foreach ($query->all() as $activity) {
            $results[] = ActivityDefinitions::getActivity($activity);
        }
        return $this->returnPagination($query, $pagination, $results);
    }
}
