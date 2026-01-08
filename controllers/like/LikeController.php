<?php

namespace humhub\modules\rest\controllers\like;

use humhub\components\behaviors\PolymorphicRelation;
use humhub\models\RecordMap;
use humhub\modules\content\interfaces\ContentProvider;
use humhub\modules\like\services\LikeService;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\LikeDefinitions;
use humhub\modules\like\models\Like;
use Yii;

class LikeController extends BaseController
{
    public function actionFindByObject()
    {
        $object = RecordMap::getByModelAndPk(
            Yii::$app->request->get('model'),
            (int)Yii::$app->request->get('pk'),
            ContentProvider::class,
        );

        if ($object === null) {
            return $this->returnError(404, 'Object model not found!');
        }

        $likeService = new LikeService($object);

        if (!$likeService->canLike()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        $query = Like::find();
        $likeService->addScopeQueryCondition($query);
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
