<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\activity;

use humhub\modules\activity\models\Activity;
use humhub\modules\content\models\Content;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\ActivityDefinitions;
use Yii;

class ActivityController extends BaseController
{
    public function actionIndex()
    {
        $results = [];

        $query = Content::find()
                ->where(['object_model' => Activity::class])
                ->andWhere(['!=', 'created_by', Yii::$app->user->id])
                ->orderBy('created_at DESC');

        $pagination = $this->handlePagination($query, 10);
        foreach ($query->all() as $content) {
            $activity = $content->getPolymorphicRelation();
            if ($activity) {
                $results[] = ActivityDefinitions::getActivity($activity);
            }
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        $content = Content::find()->where(['object_model' => Activity::class, 'object_id' => $id])->one();

        if (! $content || ! $activity = $content->getPolymorphicRelation()) {
            return $this->returnError(404, 'Activity not found');
        }

        return ActivityDefinitions::getActivity($activity);
    }

    public function actionFindByContainer($containerId)
    {
        $results = [];

        $query = Content::find()
                 ->where(['object_model' => Activity::class, 'contentcontainer_id' => $containerId])
                 ->andWhere(['!=', 'created_by', Yii::$app->user->id])
                 ->orderBy('created_at DESC');

        $pagination = $this->handlePagination($query, 10);
        foreach ($query->all() as $content) {
            $activity = $content->getPolymorphicRelation();
            if ($activity) {
                $results[] = ActivityDefinitions::getActivity($activity);
            }
        }
        return $this->returnPagination($query, $pagination, $results);
    }
}