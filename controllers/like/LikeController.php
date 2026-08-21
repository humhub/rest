<?php

namespace humhub\modules\rest\controllers\like;

use humhub\components\behaviors\PolymorphicRelation;
use humhub\models\RecordMap;
use humhub\modules\content\interfaces\ContentProvider;
use humhub\modules\like\services\LikeService;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\LikeDefinitions;
use humhub\modules\like\models\Like;
use humhub\modules\user\models\User;
use humhub\modules\user\services\UserJsonService;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class LikeController extends BaseController
{
    /**
     * @inheritdoc
     *
     * Mirrors the core `LikeController`'s `guestAllowedActions = ['info']`: guests may read
     * the like state of content they can view, everything else stays logged-in only.
     */
    protected array $guestAllowedActions = ['info'];
    public function actionFindByObject()
    {
        $object = RecordMap::getByModelAndPk(
            Yii::$app->request->get('model'),
            (int)Yii::$app->request->get('pk'),
            ContentProvider::class,
        );

        if ($object === null) {
            return $this->returnError(404, 'Object model not found!');
        }

        $likeService = new LikeService($object);

        if (!$likeService->canLike()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        $query = Like::find();
        $likeService->addScopeQueryCondition($query);
        $query->orderBy(['created_at' => SORT_DESC]);

        $pagination = $this->handlePagination($query);

        foreach ($query->all() as $like) {
            $results[] = LikeDefinitions::getLike($like);
        }

        return $this->returnPagination($query, $pagination, $results);
    }


    public function actionView($id)
    {
        $like = Like::findOne(['id' => $id]);
        if ($like === null) {
            return $this->returnError(404, 'Like not found!');
        }
        if (!$like->canView()) {
            return $this->returnError(403, 'You cannot read this content!');
        }

        return LikeDefinitions::getLike($like);
    }

    public function actionDelete($id)
    {
        $like = Like::findOne(['id' => $id]);
        if ($like === null) {
            return $this->returnError(404, 'Like not found!');
        }
        if (!$like->canDelete()) {
            return $this->returnError(403, 'You cannot delete this content!');
        }

        if ($like->delete()) {
            return $this->returnSuccess('Like successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete like!');
    }

    /**
     * Returns the current like state of the record — a 1:1 mirror of the core
     * `like/like/info` action consumed by the like Vue island (`LikeButton.vue`).
     *
     * UNSTABLE / UI-COUPLED: this and the actions below serve the HumHub frontend and
     * follow the shape the core islands consume — they are not part of the stable public
     * REST contract and may change together with core (see `docs/vue-session-api.md`).
     * They deliberately throw HTTP exceptions (same status codes as core) instead of the
     * module's usual `{"code", "message"}` envelope.
     *
     * @since 0.13
     */
    public function actionInfo()
    {
        $likeService = $this->getLikeServiceByRecordId();

        return [
            'currentUserLiked' => $likeService->hasLiked(),
            'likeCounter' => $likeService->getCount(),
        ];
    }

    /**
     * Likes the record given by `recordId` — mirror of the core `like/like/like` action.
     *
     * @since 0.13
     */
    public function actionLike()
    {
        $likeService = $this->getLikeServiceByRecordId();

        if (!$likeService->canLike()) {
            throw new ForbiddenHttpException();
        }

        $likeService->like();

        return [
            'currentUserLiked' => $likeService->hasLiked(),
            'likeCounter' => $likeService->getCount(),
        ];
    }

    /**
     * Unlikes the record given by `recordId` — mirror of the core `like/like/unlike` action.
     *
     * @since 0.13
     */
    public function actionUnlike()
    {
        $likeService = $this->getLikeServiceByRecordId();

        $likeService->unlike();

        return [
            'currentUserLiked' => $likeService->hasLiked(),
            'likeCounter' => $likeService->getCount(),
        ];
    }

    /**
     * Returns a page of the users who liked the record, in the `{total, users, hasMore,
     * nextPage}` shape of the core `like/like/user-list` action (rows serialized by
     * {@see UserJsonService}), including its `limit` clamp to `[1, userListPaginationSize]`.
     *
     * @since 0.13
     */
    public function actionUserList()
    {
        $likeService = $this->getLikeServiceByRecordId();

        $defaultLimit = Yii::$app->getModule('user')->userListPaginationSize;
        $limit = max(1, min((int)Yii::$app->request->get('limit', $defaultLimit), $defaultLimit));
        $page = max(1, (int)Yii::$app->request->get('page', 1));

        $query = $likeService->getUserQuery();
        $total = (clone $query)->count();
        $users = $query->offset(($page - 1) * $limit)->limit($limit)->all();
        $hasMore = ($page * $limit) < $total;

        $userJsonService = new UserJsonService();

        return [
            'total' => $total,
            'users' => array_map(fn(User $user) => $userJsonService->serialize($user), $users),
            'hasMore' => $hasMore,
            'nextPage' => $hasMore ? $page + 1 : null,
        ];
    }

    /**
     * Resolves the like target from the `recordId` request parameter, exactly like the
     * core `LikeController::beforeAction()` (404 for an unknown record, 403 when the
     * viewer cannot see the record's content).
     */
    private function getLikeServiceByRecordId(): LikeService
    {
        $recordId = (int)Yii::$app->request->get('recordId');
        $target = RecordMap::getById($recordId, ContentProvider::class);

        if (!$target) {
            throw new NotFoundHttpException();
        }

        if (!$target->content->canView()) {
            throw new ForbiddenHttpException();
        }

        return new LikeService($target);
    }
}
