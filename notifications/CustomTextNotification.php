<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\components\NotificationCategory;
use yii\helpers\Url;

/**
 * A free text sent to a user through `POST /api/v1/notification/send-custom`, with the
 * text and the target URL in the payload.
 */
class CustomTextNotification extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public static function category(): NotificationCategory
    {
        return NotificationCategory::direct();
    }

    /**
     * @inheritdoc
     */
    protected function getMessage(array $params): string
    {
        return (string)$params['text'];
    }

    /**
     * @inheritdoc
     */
    protected function getMessageParams(): array
    {
        return ['text' => (string)($this->payload['text'] ?? '')];
    }

    /**
     * @inheritdoc
     */
    public function getUrl(bool $scheme = false): ?string
    {
        $url = (string)($this->payload['url'] ?? '');

        if ($url === '' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url ?: null;
        }

        return Url::to($url, true);
    }
}
