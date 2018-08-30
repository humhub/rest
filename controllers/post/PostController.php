<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\post;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\PostDefinitions;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\post\models\Post;
use Yii;


class PostController extends BaseController
{

    public function actionIndex()
    {
        $results = [];
        $query = Post::find();

        $this->handlePagination($query);
        foreach ($query->all() as $post) {
            $results[] = PostDefinitions::getPost($post);
        }
        return $this->returnPagination($query, $results);
    }


    public function actionCreate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }

        /** @var ContentActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $post = new Post();
        $post->content->container = $container;
        $post->load(Yii::$app->request->getBodyParam('post', []), '');
        if ($post->save()) {
            return PostDefinitions::getPost($post);
        }

        return $this->returnError(400, 'Validation failed', ['post' => $post->getErrors()]);
    }


    public function actionUpdate($id)
    {
        $post = Post::findOne(['id' => $id]);
        if ($post === null) {
            return $this->returnError(404, 'Post not found!');
        }

        if ($post->load(Yii::$app->request->getBodyParam('post', []), '') && $post->save()) {
            return PostDefinitions::getPost($post);
        }

        return $this->returnError(400, 'Validation failed', ['post' => $post->getErrors()]);
    }


    public function actionView($id)
    {
        $post = Post::findOne(['id' => $id]);
        if ($post === null) {
            return $this->returnError(404, 'Post not found!');
        }

        return PostDefinitions::getPost($post);
    }

    public function actionDelete($id)
    {
        $post = Post::findOne(['id' => $id]);
        if ($post === null) {
            return $this->returnError(404, 'Post not found!');
        }

        if ($post->delete()) {
            return $this->returnSuccess('Post successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete post!');
    }

}