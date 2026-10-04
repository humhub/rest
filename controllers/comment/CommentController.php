<?php

namespace humhub\modules\rest\controllers\comment;

use humhub\modules\comment\Module;
use humhub\modules\comment\services\CommentListService;
use humhub\modules\content\models\Content;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\CommentDefinitions;
use humhub\modules\comment\models\Comment;
use Yii;
use yii\web\ForbiddenHttpException;

class CommentController extends BaseController
{
    public function actionCreate(int $contentId, ?int $parentCommentId = null)
    {
        $content = Content::findOne(['id' => $contentId]);
        if (!$this->getCommentModule()->canComment($content)) {
            throw new ForbiddenHttpException();
        }

        $parentComment = ($parentCommentId)
            ? Comment::findOne(['id' => $parentCommentId, 'content_id' => $contentId]) : null;

        $model = new Comment();
        $model->content_id = $content->id;
        $model->parent_comment_id = $parentComment?->id;

        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
            return CommentDefinitions::getComment($model);
        }

        return $this->returnError(400, 'Validation failed', ['comment' => $model->getErrors()]);
    }

    public function actionView($id)
    {
        $comment = Comment::findOne(['id' => $id]);
        if ($comment === null) {
            return $this->returnError(404, 'Comment not found!');
        }
        if (!$comment->content->canView()) {
            return $this->returnError(403, 'You cannot view this comment!');
        }

        return CommentDefinitions::getComment($comment);
    }

    public function actionUpdate($id)
    {
        $comment = Comment::findOne(['id' => $id]);
        if ($comment === null) {
            return $this->returnError(404, 'Comment not found!');
        }
        if (!$comment->canEdit()) {
            return $this->returnError(403, 'You cannot update this comment!');
        }

        if ($comment->load(Yii::$app->request->post(), '') && $comment->save()) {
            return CommentDefinitions::getComment($comment);
        }

        return $this->returnError(400, 'Validation failed', ['comment' => $comment->getErrors()]);
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

    public function actionFindByContent($id)
    {
        $content = Content::findOne(['id' => $id]);
        return $this->getPagedComments($content);
    }

    public function actionFindByParent($id)
    {
        $parentComment = Comment::findOne(['id' => $id]);
        if ($parentComment === null) {
            return $this->returnError(404, 'Comment not found!');
        }
        return $this->getPagedComments($parentComment->content, $parentComment);
    }

    private function getPagedComments(?Content $content, ?Comment $parentComment = null): array
    {
        if ($content === null) {
            return $this->returnError(404, 'Content not found!');
        }
        if (!$content->canView()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        $commentListService = new CommentListService($content, $parentComment);
        $query = $commentListService->getQuery();

        $results = [];

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $comment) {
            $results[] = CommentDefinitions::getComment($comment);
        }

        return $this->returnPagination($query, $pagination, $results);
    }

    private function getCommentModule(): Module
    {
        return Yii::$app->getModule('comment');
    }
}
