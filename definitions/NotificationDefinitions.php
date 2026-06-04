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
            'class' => $baseNotification::class,
            'output' => $baseNotification->html(),
            'originator' => $baseNotification->originator ? UserDefinitions::getUserShort($baseNotification->originator) : null,
            'source' => $baseNotification->source ? SourceDefinitions::getSource($baseNotification->source) : null,
            'createdAt' => $notification->created_at,
        ];
    }
}
