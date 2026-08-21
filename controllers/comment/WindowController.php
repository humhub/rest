<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\comment;

use humhub\modules\comment\models\AdminDeleteCommentForm;
use humhub\modules\comment\models\Comment;
use humhub\modules\comment\Module;
use humhub\modules\comment\notifications\CommentDeleted;
use humhub\modules\comment\services\CommentJsonService;
use humhub\modules\content\models\Content;
use humhub\modules\rest\components\BaseController;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Comment endpoints in the JSON shape consumed by the core comment Vue island
 * (`CommentSection.vue` and friends) — a 1:1 mirror of the core JSON controller
 * `humhub\modules\comment\controllers\CommentController`, delegating to
 * {@see CommentJsonService} for all serialization, viewer-context, blocked-author
 * masking and `guestHideComments` semantics.
 *
 * UNSTABLE / UI-COUPLED: these endpoints serve the HumHub frontend and follow the
 * shape the core islands consume — they are not part of the stable public REST
 * contract and may change together with core (see `docs/vue-session-api.md`).
 * The stable comment CRUD endpoints live in {@see CommentController}.
 *
 * Deliberate contract differences to the rest of this module: errors are thrown
 * as HTTP exceptions (same status codes and JSON error body as the core
 * controller) and validation failures return `422 {"errors": {attribute: [...]}}`
 * instead of the module's usual `400 {"code", "message"}` envelope.
 *
 * @since 0.13
 */
class WindowController extends BaseController
{
    public ?Comment $comment = null;
    private ?Content $content = null;

    public ?Comment $parentComment = null;

    /**
     * @inheritdoc
     *
     * The core controller allows guests on its read actions (subject to
     * `Content::canView()` and the `guestHideComments` gate, both enforced below /
     * in the service); mutations are for logged-in users only.
     */
    protected array $guestAllowedActions = ['index', 'view'];

    /**
     * Resolves the target comment / parent comment / content exactly like the core
     * controller's `beforeAction()`: by `id`, `parentCommentId` or `contentId`
     * (query or body param).
     *
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $commentId = (int)Yii::$app->request->get('id', Yii::$app->request->post('id'));
        $parentCommentId = (int)Yii::$app->request->get(
            'parentCommentId',
            Yii::$app->request->post('parentCommentId'),
        );
        $contentId = (int)Yii::$app->request->get('contentId', Yii::$app->request->post('contentId'));

        if ($commentId) {
            $this->comment = Comment::findOne(['id' => $commentId]);
            $this->content = $this->comment?->content;
            $this->parentComment = $this->comment?->parentComment;
        } elseif ($parentCommentId) {
            $this->parentComment = Comment::findOne(['id' => $parentCommentId]);
            $this->content = $this->parentComment?->content;
        } elseif ($contentId) {
            $this->content = Content::findOne(['id' => $contentId]);
        }

        if (!$this->content) {
            throw new NotFoundHttpException();
        }

        if (!$this->content->canView()) {
            throw new ForbiddenHttpException();
        }

        return true;
    }

    /**
     * Returns a window of comments (cursor pagination, or an anchored permalink window
     * when no `direction` is given). Mirrors the core `comment/comment/list` action.
     *
     * @see CommentJsonService::serializeWindow()
     */
    public function actionIndex()
    {
        $direction = Yii::$app->request->get('direction');
        $commentId = Yii::$app->request->get('commentId');
        $pageSize = Yii::$app->request->get('pageSize');

        $service = CommentJsonService::create($this->parentComment ?? $this->content);

        return $service->serializeWindow(
            $commentId !== null ? (int)$commentId : null,
            $direction,
            $pageSize !== null ? (int)$pageSize : null,
        );
    }

    /**
     * Returns a single comment in the island shape. `showBlocked=1` lifts the
     * blocked-author mask only. Mirrors the core `comment/comment/info` action.
     */
    public function actionView()
    {
        if ($this->comment === null) {
            throw new NotFoundHttpException();
        }

        if (!$this->comment->canView()) {
            throw new ForbiddenHttpException();
        }

        $showBlocked = (bool)Yii::$app->request->get('showBlocked');

        return CommentJsonService::create($this->comment)->serializeComment($this->comment, $showBlocked);
    }

    /**
     * Creates a comment from a JSON payload (`message`, `fileList`, `parentCommentId`),
     * enforcing at most one nesting level. Mirrors the core `comment/comment/create`
     * action, including the `422 {"errors": ...}` validation contract.
     */
    public function actionCreate()
    {
        if (!$this->getCommentModule()->canComment($this->content)) {
            throw new ForbiddenHttpException();
        }

        if ($this->parentComment !== null && $this->parentComment->parent_comment_id !== null) {
            Yii::$app->response->statusCode = 422;

            return [
                'errors' => [
                    'parentCommentId' => [Yii::t('CommentModule.base', 'Comments can only be nested one level deep.')],
                ],
            ];
        }

        $model = new Comment();
        $model->content_id = $this->content->id;
        $model->parent_comment_id = $this->parentComment?->id;

        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
            return CommentJsonService::create($model)->serializeComment($model);
        }

        Yii::$app->response->statusCode = 422;

        return ['errors' => $model->errors];
    }

    /**
     * Returns the raw markdown message of an editable comment for the editor —
     * the core `comment/comment/update` GET mode.
     */
    public function actionEdit()
    {
        if ($this->comment === null) {
            throw new NotFoundHttpException();
        }

        if (!$this->comment->canEdit()) {
            throw new ForbiddenHttpException();
        }

        return ['message' => $this->comment->message];
    }

    /**
     * Saves a comment from a JSON payload (`message`, `fileList`) and returns the
     * updated comment in the island shape — the core `comment/comment/update` POST
     * mode, including the `422 {"errors": ...}` validation contract.
     */
    public function actionUpdate()
    {
        if ($this->comment === null) {
            throw new NotFoundHttpException();
        }

        if (!$this->comment->canEdit()) {
            throw new ForbiddenHttpException();
        }

        if ($this->comment->load(Yii::$app->request->post(), '') && $this->comment->save()) {
            return CommentJsonService::create($this->comment)->serializeComment($this->comment);
        }

        Yii::$app->response->statusCode = 422;

        return ['errors' => $this->comment->errors];
    }

    /**
     * Deletes a comment, optionally notifying the author with a reason
     * (`AdminDeleteCommentForm[notify]` / `AdminDeleteCommentForm[message]` body
     * params — the fields of the core admin-delete modal). Mirrors the core
     * `comment/comment/delete` action, returning `{"success": bool}`.
     *
     * The notification block is copied from the core action verbatim — core has not
     * extracted it into a service yet; when the core controller is removed in favor
     * of these endpoints, it should move into one (tracked in `docs/vue-session-api.md`).
     */
    public function actionDelete()
    {
        if ($this->comment === null) {
            throw new NotFoundHttpException();
        }

        if (!$this->comment->canDelete()) {
            throw new ForbiddenHttpException();
        }

        $form = new AdminDeleteCommentForm();

        if ($form->load(Yii::$app->request->post()) && $form->validate() && $form->notify) {
            $commentDeleted = CommentDeleted::instance()
                ->from(Yii::$app->user->getIdentity())
                ->about($this->comment->content->getPolymorphicRelation())
                ->payload(
                    [
                        'commentText' => (new CommentDeleted())->getContentPreview($this->comment, 30),
                        'reason' => $form->message,
                    ],
                );
            $commentDeleted->saveRecord($this->comment->createdBy);

            $commentDeleted->record->updateAttributes([
                'send_web_notifications' => 1,
            ]);
        }

        return ['success' => $this->comment->delete()];
    }

    private function getCommentModule(): Module
    {
        return Yii::$app->getModule('comment');
    }
}
