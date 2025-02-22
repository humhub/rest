<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\comment;

use humhub\libs\Helpers;
use humhub\modules\comment\models\forms\CommentForm;
use humhub\modules\comment\Module;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\CommentDefinitions;
use humhub\modules\comment\models\Comment;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class CommentController
 * @package humhub\modules\rest\controllers\comment
 *
 * @property-read Module $commentModule
 * @property-read null|ContentActiveRecord|Comment $object
 */
class CommentController extends BaseController
{
    /**
     * @var null|ContentActiveRecord|Comment Cached object
     */
    private $_object;

    /**
     * Creates a comment
     *
     * @return array
     */
    public function actionCreate()
    {
        if (!$this->commentModule->canComment($this->object)) {
            throw new ForbiddenHttpException('You cannot comment the content!');
        }

        return Comment::getDb()->transaction(function () {
            $form = new CommentForm($this->object);

            if ($form->load(Yii::$app->request->post()) && $form->save()) {
                return CommentDefinitions::getComment($form->comment);
            }

            return $this->returnError(400, 'Validation failed', ['comment' => $form->comment->getErrors()]);
        });
    }

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

    public function actionUpdate($id)
    {
        $comment = Comment::findOne(['id' => $id]);
        if ($comment === null) {
            return $this->returnError(404, 'Comment not found!');
        }
        if (!$comment->canEdit()) {
            return $this->returnError(403, 'You cannot update this comment!');
        }

        $form = new CommentForm($comment->getSource(), $comment);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            return CommentDefinitions::getComment($form->comment);
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

    public function actionFindByObject($objectModel, $objectId)
    {
        $content = Content::findOne([
            'object_model' => $objectModel,
            'object_id' => $objectId,
        ]);

        return $this->getPagedComments($content);
    }

    public function actionFindByContent($id)
    {
        $content = Content::findOne(['id' => $id]);

        return $this->getPagedComments($content);
    }

    private function getPagedComments(?Content $content): array
    {
        if ($content === null) {
            return $this->returnError(404, 'Content not found!');
        }
        if (!$content->canView()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        $query = Comment::find()
            ->where(['object_model' => $content->object_model])
            ->andWhere(['object_id' => $content->object_id])
            ->orderBy(['created_at' => SORT_ASC]);

        $results = [];

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $comment) {
            $results[] = CommentDefinitions::getComment($comment);
        }

        return $this->returnPagination($query, $pagination, $results);
    }

    public function getCommentModule(): Module
    {
        /* @var Module $commentModule */
        $commentModule = Yii::$app->getModule('comment');
        return $commentModule;
    }

    /**
     * @return null|ContentActiveRecord|Comment
     */
    public function getObject()
    {
        if (!isset($this->_object)) {
            $modelClass = Yii::$app->request->get('objectModel', Yii::$app->request->post('objectModel'));
            $modelPk = (int)Yii::$app->request->get('objectId', Yii::$app->request->post('objectId'));

            Helpers::CheckClassType($modelClass, [Comment::class, ContentActiveRecord::class]);
            $this->_object = $modelClass::findOne(['id' => $modelPk]);

            if (!$this->_object) {
                throw new NotFoundHttpException('Could not find underlying content or content addon record!');
            }

            if (!$this->_object->content->canView()) {
                throw new ForbiddenHttpException('You cannot view the content record!');
            }
        }

        return $this->_object;
    }

}
