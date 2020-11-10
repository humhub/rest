<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\like;

use humhub\modules\content\models\Content;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\LikeDefinitions;
use humhub\modules\like\models\Like;
use Yii;


class LikeController extends BaseController
{

    public function actionFindByRecord()
    {
        $contentFilter = [
            'object_model' => Yii::$app->request->get('model'),
            'object_id' => (int)Yii::$app->request->get('pk'),
        ];
        $content = Content::findOne($contentFilter);
        if ($content === null) {
            return $this->returnError(404, 'Content not found!');
        }
        if (!$content->canView()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

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