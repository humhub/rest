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

        $model = $record::class;
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
        $result = [
            'id' => $comment->id,
            'message' => $comment->message,
            'objectModel' => $comment->object_model,
            'objectId' => $comment->object_id,
            'createdBy' => UserDefinitions::getUserShort($comment->user),
            'createdAt' => $comment->created_at,
            'likes' => LikeDefinitions::getLikesSummary($comment),
            'files' => FileDefinitions::getFiles($comment),
        ];

        $subComments = static::getSubComments($comment);
        $subCommentsCount = count($subComments);
        if ($subCommentsCount) {
            $result['commentsCount'] = $subCommentsCount;
            $result['comments'] = $subComments;
        }

        return $result;
    }

    /**
     * @param Comment $comment
     * @return Comment[]
     */
    public static function getSubComments(Comment $comment): array
    {
        $comments = [];

        if (Comment::isSubComment($comment)) {
            // Sub-comment doesn't have sub-comments with level 2
            return $comments;
        }

        $query = Comment::find()
            ->where(['object_model' => Comment::class])
            ->andWhere(['object_id' => $comment->id])
            ->orderBy(['created_at' => SORT_ASC]);

        foreach ($query->all() as $comment) {
            $comments[] = static::getComment($comment);
        }

        return $comments;
    }

}
