<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\content\models\Content;
use humhub\modules\notification\components\NotificationManager;
use humhub\modules\notification\models\Notification;
use Throwable;
use Yii;

class NotificationDefinitions
{
    public static function getNotification(Notification $notification)
    {
        $baseNotification = NotificationManager::load($notification);

        $source = $baseNotification->sourceRecord;
        if ($source instanceof Content) {
            $source = $source->getPolymorphicRelation();
        }

        return [
            'id' => $baseNotification->record->id,
            'class' => $baseNotification::class,
            'output' => $baseNotification->asWeb(),
            'originator' => UserDefinitions::getUserShort($baseNotification->originator),
            'source' => $source ? SourceDefinitions::getSource($source) : null,
            'createdAt' => $baseNotification->record->created_at,
        ];
    }

    /**
     * Definitions of the given notifications, leaving out those that cannot be loaded any
     * more (e.g. their source record is gone or their module is disabled).
     *
     * @param Notification[] $notifications
     */
    public static function getNotifications(array $notifications): array
    {
        $results = [];

        foreach ($notifications as $notification) {
            try {
                $results[] = static::getNotification($notification);
            } catch (Throwable $e) {
                Yii::warning('Could not load notification #' . $notification->id . ': ' . $e->getMessage(), 'rest');
            }
        }

        return $results;
    }
}
