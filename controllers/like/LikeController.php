<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\like;

use humhub\components\behaviors\PolymorphicRelation;
use humhub\helpers\DataTypeHelper;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentAddonActiveRecord;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\LikeDefinitions;
use humhub\modules\like\models\Like;
use Yii;

class LikeController extends BaseController
{
    public function actionFindByObject()
    {
        $model = Yii::$app->request->get('model');
        $pk = (int)Yii::$app->request->get('pk');

        if (DataTypeHelper::matchClassType($model, [ContentActiveRecord::class, ContentAddonActiveRecord::class]) === null) {
            return $this->returnError(400, 'Invalid object model!');
        }

        $object = $model::findOne(['id' => $pk]);
        if ($object === null) {
            return $this->returnError(404, 'Object model not found!');
        }

        if (!$object->content->canView()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        $contentFilter = [
            'object_model' => PolymorphicRelation::getObjectModel($object),
            'object_id' => $object->getPrimaryKey(),
        ];

        $results = [];
        $query = Like::find();
        $query->andWhere($contentFilter);
        $query->orderBy(['created_at' => SORT_DESC]);

        $pagination = $this->handlePagination($query);

        foreach ($query->all() as $like) {
            $results[] = LikeDefinitions::getLike($like);
        }

        return $this->returnPagination($query, $pagination, $results);
    }


    public function actionView($id)
    {
        $like = Like::findOne(['id' => $id]);
        if ($like === null) {
            return $this->returnError(404, 'Like not found!');
        }
        if (!$like->canRead()) {
            return $this->returnError(403, 'You cannot read this content!');
        }

        return LikeDefinitions::getLike($like);
    }

    public function actionDelete($id)
    {
        $like = Like::findOne(['id' => $id]);
        if ($like === null) {
            return $this->returnError(404, 'Like not found!');
        }
        if (!$like->canDelete()) {
            return $this->returnError(403, 'You cannot delete this content!');
        }

        if ($like->delete()) {
            return $this->returnSuccess('Like successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete like!');
    }


}
