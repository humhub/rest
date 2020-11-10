<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\comment;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\CommentDefinitions;
use humhub\modules\comment\models\Comment;


class CommentController extends BaseController
{

    public function actionView($id)
    {
        $comment = Comment::findOne(['id' => $id]);
        if ($comment === null) {
            return $this->returnError(404, 'Comment not found!');
        }
        if (!$comment->canRead()) {
            return $this->returnError(403, 'You cannot view this comment!');
        }

        return CommentDefinitions::getComment($comment);
    }

    public function actionDelete($id)
    {
        $comment = Comment::findOne(['id' => $id]);
        if ($comment === null) {
            return $this->returnError(404, 'Comment not found!');
        }
        if (!$comment->canDelete()) {
            return $this->returnError(403, 'You cannot delete this comment!');
        }

        if ($comment->delete()) {
            return $this->returnSuccess('Comment successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete comment!');
    }


}