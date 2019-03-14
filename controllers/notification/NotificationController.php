<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\notification;

use humhub\modules\notification\models\forms\FilterForm;
use humhub\modules\notification\models\Notification;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\NotificationDefinitions;
use Yii;

class NotificationController extends BaseController
{
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
        $notification = Notification::findOne(['id' => $id]);

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
}