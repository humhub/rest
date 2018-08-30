<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;
use humhub\components\ActiveRecord;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\models\Content;


/**
 * Class CommentDefinitions
 * @package humhub\modules\rest\definitions
 */
class CommentDefinitions
{

    public static function getCommentsSummary(ActiveRecord $record)
    {
        $result = [];

        $model = get_class($record);
        $pk = $record->getPrimaryKey();
        if ($record instanceof Content) {
            $model = $record->object_model;
            $pk = $record->object_id;
        }

        $result['total'] = Comment::GetCommentCount($model, $pk);
        $result['latest'] = [];

        if (!empty($result['total'])) {
            foreach (Comment::GetCommentsLimited($model, $pk) as $comment) {
                $result['latest'][] = static::getComment($comment);
            }
       }

        return $result;
    }

    public static function getComment(Comment $comment)
    {
        return [
            'id' => $comment->id,
            'message' => $comment->message,
            'createdBy' => UserDefinitions::getUserShort($comment->user),
            'createdAt' => $comment->created_at,
            'likes' => LikeDefinitions::getLikesSummary($comment)
        ];
    }

}