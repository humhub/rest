<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\notification;

use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\content\models\Content;
use humhub\modules\notification\components\ActiveQueryNotification;
use humhub\modules\notification\components\NotificationManager;
use humhub\modules\notification\models\Notification;
use humhub\modules\notification\services\NotificationListService;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\NotificationDefinitions;
use humhub\modules\rest\notifications\CustomTextNotification;
use humhub\modules\user\models\User;
use Yii;
use yii\base\DynamicModel;

class NotificationController extends BaseController
{
    protected function getAccessRules()
    {
        return [
            ['permissions' => [ManageUsers::class], 'actions' => ['send-custom']],
        ];
    }

    public function actionIndex()
    {
        $query = $this->createListQuery();

        // notification category ids, e.g. `social` or a module id, as a list or comma-separated
        $excludeFilters = Yii::$app->request->get('excludeFilters', []);
        if (is_string($excludeFilters)) {
            $excludeFilters = explode(',', $excludeFilters);
        }
        if (!empty($excludeFilters)) {
            $classes = (new NotificationListService())->classesOfCategories(array_map('trim', (array)$excludeFilters));
            if ($classes !== []) {
                $query->andWhere(['NOT IN', 'notification.class', $classes]);
            }
        }

        $pagination = $this->handlePagination($query, 10);
        $results = NotificationDefinitions::getNotifications($query->all());

        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        $notification = Notification::find()
            ->forUser(Yii::$app->user->id)
            ->andWhere(['notification.id' => $id])
            ->one();

        if (! $notification) {
            return $this->returnError(404, 'Notification not found');
        }

        return NotificationDefinitions::getNotification($notification);
    }

    public function actionUnseen()
    {
        $query = $this->createListQuery()->unseen();

        $pagination = $this->handlePagination($query, 10);
        $results = NotificationDefinitions::getNotifications($query->all());

        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionMarkAsSeen()
    {
        NotificationManager::markAllSeen(Yii::$app->user->getIdentity());

        return $this->returnSuccess('All notifications successfully marked as seen');
    }

    public function actionSendCustom()
    {
        $model = DynamicModel::validateData([
            'userId' => Yii::$app->request->post('userId'),
            'text' => Yii::$app->request->post('text'),
            'url' => Yii::$app->request->post('url'),
        ], [
            [['text', 'url'], 'trim'],
            [['text', 'userId', 'url'], 'required'],
            [
                ['userId'],
                'exist',
                'targetClass' => User::class,
                'targetAttribute' => ['userId' => 'id'],
                'filter' => function ($query): void {
                    $query->active();
                },
            ],
        ]);

        if ($model->hasErrors()) {
            return $this->returnError(422, array_values($model->getFirstErrors())[0]);
        }

        $receiver = User::find()->where(['id' => $model->userId])->active()->one();

        CustomTextNotification::send(
            $receiver,
            originator: Yii::$app->user->getIdentity(),
            payload: [
                'text' => $model->text,
                'url' => $model->url,
            ],
        );

        return $this->returnSuccess('Notification successfully sent');
    }

    /**
     * The caller's notifications as the web list shows them: one entry per group, newest
     * first, without those about unpublished content.
     */
    private function createListQuery(): ActiveQueryNotification
    {
        return Notification::find()
            ->forUser(Yii::$app->user->id)
            ->listed()
            ->grouped()
            ->leftJoin('content', 'content.id = notification.content_id')
            ->andWhere(['OR',
                ['content.id' => null],
                ['content.state' => Content::STATE_PUBLISHED],
            ]);
    }
}
