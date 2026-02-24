<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\notification;

use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\notification\models\forms\FilterForm;
use humhub\modules\notification\models\Notification;
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
        $results = [];

        $filterForm = new FilterForm();
        $excludeFilters = Yii::$app->request->get('excludeFilters', []);

        if (! empty($excludeFilters)) {
            $filterForm->categoryFilter = array_diff($filterForm->categoryFilter, $excludeFilters);
        }

        $query = $filterForm->createQuery();

        $pagination = $this->handlePagination($query, 10);
        foreach ($query->all() as $notification) {
            $results[] = NotificationDefinitions::getNotification($notification);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        $notification = Notification::find()
            ->where(['id' => $id])
            ->andWhere(['user_id' => Yii::$app->user->id])
            ->one();

        if (! $notification) {
            return $this->returnError(404, 'Notification not found');
        }

        return NotificationDefinitions::getNotification($notification);
    }

    public function actionUnseen()
    {
        $results = [];

        $query = Notification::findUnseen();

        $pagination = $this->handlePagination($query, 10);
        foreach ($query->all() as $notification) {
            $results[] = NotificationDefinitions::getNotification($notification);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionMarkAsSeen()
    {
        Notification::updateAll(['seen' => 1], ['user_id' => Yii::$app->user->id]);

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
                'filter' => function ($query) {
                    $query->active();
                },
            ],
        ]);

        if ($model->hasErrors()) {
            return $this->returnError(422, array_values($model->getFirstErrors())[0]);
        }

        $receiver = User::find()->where(['id' => $model->userId])->active()->one();

        CustomTextNotification::instance()
            ->from(Yii::$app->user->identity)
            ->payload([
                'text' => $model->text,
                'url' => $model->url,
            ])
            ->send($receiver);

        return $this->returnSuccess('Notification successfully sent');
    }
}
