<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\topic\models\Topic;


/**
 * Class ContentDefinitions
 * @package humhub\modules\rest\definitions
 */
class ContentDefinitions
{

    public static function getContent($content)
    {
        return [
            'id' => $content->id,
            'content-metadata' => static::getContentMetadata($content),
            'comments' => CommentDefinitions::getCommentsSummary($content),
            'likes' => LikeDefinitions::getLikesSummary($content),
            'topics' => static::getTopics($content),
            'files' => [],
        ];

    }

    public static function getContentMetadata(Content $content)
    {

        return [
            'id' => $content->id,
            'guid' => $content->guid,
            'object_model' => $content->object_model,
            'object_id' => $content->object_id,
            'created_by' => UserDefinitions::getUserShort($content->createdBy),

        ];
    }

    public static function getContentContainer(ContentContainer $container)
    {
        return [
            'id' => $container->id,
            'guid' => $container->guid,
            'objectClass' => $container->class,
            'objectPk' => $container->pk,
        ];
    }

    public static function getTopics(Content $content) {

        $topics = [];

        foreach (Topic::findByContent($content)->all() as $topic) {
            $topics[] = static::getTopic($topic);
        }

        return $topics;
    }

    public static function getTopic(Topic $topic) {
        return [
            'id' => $topic->id,
            'name' => $topic->name,
        ];
    }


}