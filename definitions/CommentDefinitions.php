<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\comment\models\Comment;
use humhub\modules\comment\services\CommentListService;
use humhub\modules\content\models\Content;

/**
 * Class CommentDefinitions
 * @package humhub\modules\rest\definitions
 */
class CommentDefinitions
{
    public static function getCommentsSummary(Content $content)
    {
        $commentListService = new CommentListService($content, null);

        $result = [];
        $result['total'] = $commentListService->getCount();
        $result['latest'] = [];

        if (!empty($result['total'])) {
            foreach ($commentListService->getLimited(3) as $comment) {
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
            'contentId' => $comment->content_id,
            'parentCommentId' => $comment->parent_comment_id,
            'createdBy' => UserDefinitions::getUserShort($comment->createdBy),
            'createdAt' => $comment->created_at,
            'likes' => LikeDefinitions::getLikesSummary($comment),
            'files' => FileDefinitions::getFiles($comment),
            'childCount' => $comment->getChildCount(),
        ];

        return $result;
    }
}
