<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\topic\models\Topic;
use Yii;


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
            'metadata' => static::getContentMetadata($content),
            'comments' => CommentDefinitions::getCommentsSummary($content),
            'likes' => LikeDefinitions::getLikesSummary($content),
            'topics' => static::getTopics($content),
            'files' => FileDefinitions::getFiles($content),
        ];

    }

    public static function getContentMetadata(Content $content)
    {
        return [
            'id' => $content->id,
            'guid' => $content->guid,
            'object_model' => $content->object_model,
            'object_id' => $content->object_id,
            'visibility' => (int) $content->visibility,
            'archived' => (bool) $content->archived,
            'pinned' => (bool) $content->pinned,
            'locked_comments' => (bool) $content->locked_comments,
            'created_by' => UserDefinitions::getUserShort($content->createdBy),
            'created_at' => $content->created_at,
            'updated_by' => UserDefinitions::getUserShort($content->updatedBy),
            'updated_at' => $content->updated_at,
            'url' => $content->getUrl(true),
            'contentcontainer_id' => $content->contentcontainer_id,
            'stream_channel' => $content->stream_channel
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

    public static function getTopics(Content $content)
    {
        $topics = [];

        foreach (Topic::findByContent($content)->all() as $topic) {
            $topics[] = static::getTopic($topic);
        }

        return $topics;
    }

    public static function getTopic(Topic $topic)
    {
        return [
            'id' => $topic->id,
            'name' => $topic->name,
        ];
    }


    public static function handleTopicsParam(ActiveQueryContent $query, $contentContainerId)
    {
        $topicsParam = Yii::$app->request->get('topics');
        if (!empty($topicsParam)) {
            $topics = [];
            foreach (explode(',', $topicsParam) as $topicName) {
                $topic = Topic::findOne(['contentcontainer_id' => $contentContainerId, 'name' => $topicName]);
                if ($topic !== null) {
                    $topics[] = $topic;
                }
            }
            if (!empty($topics)) {
                $query->contentTag($topics, 'OR');
            }
        }
    }


}