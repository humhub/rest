<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\notification\models\Notification;

class NotificationDefinitions
{
    public static function getNotification(Notification $notification)
    {
        $baseNotification = $notification->getBaseModel();

        return [
            'id' => $notification->id,
            'class' => get_class($baseNotification),
            'output' => $baseNotification->html(),
            'originator' => UserDefinitions::getUserShort($baseNotification->originator),
            'source' => SourceDefinitions::getSource($baseNotification->source),
            'createdAt' => $notification->created_at
        ];
    }
}