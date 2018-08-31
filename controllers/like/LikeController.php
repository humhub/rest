<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\like;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\LikeDefinitions;
use humhub\modules\like\models\Like;
use Yii;


class LikeController extends BaseController
{

    public function actionFindByRecord()
    {
        $results = [];
        $query = Like::find();
        $query->andWhere(['object_model' => Yii::$app->request->get('model'), 'object_id' => (int)Yii::$app->request->get('pk')]);
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

        return LikeDefinitions::getLike($like);
    }

    public function actionDelete($id)
    {
        $like = Like::findOne(['id' => $id]);
        if ($like === null) {
            return $this->returnError(404, 'Like not found!');
        }

        if ($like->delete()) {
            return $this->returnSuccess('Like successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete like!');
    }


}