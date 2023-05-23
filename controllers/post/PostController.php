<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\post;


use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\post\models\Post;
use humhub\modules\rest\components\BaseContentController;
use humhub\modules\rest\definitions\PostDefinitions;


class PostController extends BaseContentController
{
    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return Post::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var Post $contentRecord */
        $post = Post::findOne(['id' => $contentRecord->id]);
        return PostDefinitions::getPost($post);
    }
}